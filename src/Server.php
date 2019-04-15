<?php

namespace AS2;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Server
{
    /**
     * @var Management
     */
    protected $manager;

    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Server constructor.
     *
     * @param Management $management
     * @param StorageInterface $storage
     */
    public function __construct(Management $management, StorageInterface $storage)
    {
        $this->manager = $management;
        $this->storage = $storage;
    }

    /**
     * Function receives AS2 requests from partner.
     * Checks whether it's an AS2 message or an MDN and acts accordingly.
     *
     * @param ServerRequestInterface|null $request
     * @return Response
     * @throws \RuntimeException|\InvalidArgumentException
     */
    public function execute(ServerRequestInterface $request = null)
    {
        if (! $request) {
            $request = ServerRequest::fromGlobals();
        }

        $responseStatus = 200;
        $responseHeaders = [];
        $responseBody = null;

        try {

            if ($request->getMethod() !== 'POST') {
                throw new \RuntimeException('To submit an AS2 message, you must POST the message to this URL.');
            }

            foreach (['message-id', 'as2-from', 'as2-to'] as $header) {
                if (! $request->hasHeader($header)) {
                    throw new \InvalidArgumentException(sprintf('Missing "%s" header', $header));
                }
            }

            // Process the posted AS2 message

            $messageId = trim($request->getHeaderLine('message-id'), '<>');
            $as2from = $request->getHeaderLine('as2-from');
            $as2to = $request->getHeaderLine('as2-to');

            $this->getLogger()->debug(sprintf('Inbound transmission is a AS2 message [%s]', $as2to));

            // Get the message sender and receiver AS2 IDs
            $sender = $this->storage->getPartner($as2from);
            if (! $sender) {
                throw new \InvalidArgumentException(sprintf('Unknown AS2 Sender "%s"', $as2from));
            }
            $receiver = $this->storage->getPartner($as2to);
            if (! $receiver) {
                throw new \InvalidArgumentException(sprintf('Unknown AS2 Receiver "%s"', $as2to));
            }

            $body = $request->getBody()->getContents();

            $encoding = $request->getHeaderLine('content-transfer-encoding');
            if (! $encoding) {
                $encoding = $request->getHeaderLine('content-encoding');
                if (! $encoding) {
                    $encoding = $sender->getContentTransferEncoding();
                }
            }
            // Force encode binary data to base64, because openssl_pkcs7 doesn't work with binary data
            if ($encoding != 'base64') {
                $request = $request->withHeader('Content-Transfer-Encoding', 'base64');
                $body = Utils::encodeBase64($body);
            }

            $payload = new MimePart($request->getHeaders(), $body);

            // Initialize New Message
            $message = $this->storage->initMessage();
            $message->setMessageId($messageId);
            $message->setDirection(MessageInterface::DIR_INBOUND);
            $message->setHeaders($payload->getHeaderLines());
            $message->setSender($sender);
            $message->setReceiver($receiver);

            // Check if message from this partner are expected to be encrypted
            //            if ($receiver->getEncryptionAlgorithm() && !$payload->isEncrypted()) {
            //                throw new \InvalidArgumentException('Incoming message from AS2 partner are defined to be encrypted');
            //            }

            $micalg = $payload->getParsedHeader('Disposition-Notification-Options', 2, 0);

            // Check if payload is encrypted and if so decrypt it
            if ($payload->isEncrypted()) {
                $this->getLogger()->debug('Inbound AS2 message is encrypted.');
                // TODO: check passKey
                $payload = CryptoHelper::decrypt($payload, $receiver->getCertificate(), [
                    $receiver->getPrivateKey(),
                    $receiver->getPrivateKeyPassPhrase(),
                ]);
                $this->getLogger()->debug('The inbound AS2 message data has been decrypted.');
                $message->setEncrypted();
            }

            // Check if message from this partner are expected to be signed
            //            if ($receiver->getSignatureAlgorithm() && !$payload->isSigned()) {
            //                throw new \InvalidArgumentException('Incoming message from AS2 partner are defined to be signed');
            //            }

            // Check if message is signed and if so verify it
            if ($payload->isSigned()) {

                if (! $micalg) {
                    $micalg = $payload->getParsedHeader('content-type', 0, 'micalg');
                }

                $this->getLogger()->debug('Inbound AS2 message is signed.');
                $this->getLogger()->debug(
                    sprintf('The sender used the algorithm "%s" to sign the inbound AS2 message.', $micalg)
                );
                $this->getLogger()->debug('Using certificate to verify inbound AS2 message signature.');

                if (! CryptoHelper::verify($payload, $sender->getCertificate())) {
                    throw new \RuntimeException('Signature Verification Failed');
                }

                $this->getLogger()->debug('Digital signature of inbound AS2 message has been verified successful.');
                $this->getLogger()->debug(sprintf('Found %s payload attachments in the inbound AS2 message.',
                    $payload->getCountParts() - 1));

                foreach ($payload->getParts() as $part) {
                    if (! $part->isPkc7Signature()) {
                        $payload = $part;
                    }
                }
                // TODO: AS2-Version: 1.1 multiple attachments
                // Saving the message mic for sending it in the MDN
                $message->setMic(CryptoHelper::calculateMIC($payload, $micalg));
                $message->setSigned();
            }

            // Check if the message has been compressed and if so decompress it
            if ($payload->isCompressed()) {
                $this->getLogger()->debug('Decompressing the payload');
                $payload = CryptoHelper::decompress($payload);
                $message->setCompressed();
            }

            //  If this is a MDN, get the Message-Id and check if it exists
            if ($payload->isReport()) {
                // Get Original Message-Id
                $messageId = null;
                foreach ($payload->getParts() as $part) {
                    if ($part->getParsedHeader('content-type', 0, 0) == 'message/disposition-notification') {
                        $bodyPayload = MimePart::fromString($part->getBody());
                        $messageId = trim($bodyPayload->getParsedHeader('original-message-id', 0, 0), '<>');
                    }
                }
                $this->getLogger()->debug('Asynchronous MDN received for AS2 message', [$messageId]);
                $message = $this->storage->getMessage($messageId);
                if (! $message) {
                    throw new \InvalidArgumentException('Unknown AS2 MDN received. Will not be processed');
                }
                $this->manager->processMdn($message, $payload);
                $this->storage->saveMessage($message);
                $responseBody = 'AS2 ASYNC MDN has been received';
            } else {
                $message->setPayload((string) $payload);
                $message->setStatus(MessageInterface::STATUS_SUCCESS);
                // $this->manager->processMessage($message, $payload);

                // if MDN enabled than send notification
                if ($mdnMode = $receiver->getMdnMode()) {
                    $mdn = $this->manager->buildMdn($message);
                    $message->setMdnStatus(MessageInterface::MDN_STATUS_SENT);
                    if ($mdnMode == PartnerInterface::MDN_MODE_SYNC) {
                        $this->getLogger()->debug(sprintf('Synchronous MDN sent as answer to message "%s".',
                            $messageId));
                        $responseHeaders = $mdn->getHeaders();
                        $responseBody = $mdn->getBody();
                    } else {
                        // TODO: cron, queue, new thread
                        $this->getLogger()->debug(sprintf('Asynchronous MDN sent as answer to message "%s".',
                            $messageId));
                        $this->manager->sendMdn($message);
                    }
                }

                $this->storage->saveMessage($message);
                $this->getLogger()->debug('AS2 communication successful, message has been saved.', [$messageId]);
            }

        } catch (\Exception $e) {

            // $mdn = $this->manager->buildMdn($message, null, $e->getMessage());

            $this->getLogger()->critical($e->getMessage());
            $responseStatus = 500;
            $responseBody = $e->getMessage();
        }

        return new Response($responseStatus, $responseHeaders, $responseBody);
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        if (! $this->logger) {
            $this->logger = $this->manager->getLogger();
        }

        if (! $this->logger) {
            $this->logger = new NullLogger();
        }

        return $this->logger;
    }

    /**
     * @param LoggerInterface $logger
     * @return $this
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }
}
