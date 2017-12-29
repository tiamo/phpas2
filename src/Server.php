<?php

namespace AS2;

use AS2\Tests\Mock\FileStorage;
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
     * @var FileStorage
     */
    protected $storage;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Server constructor.
     * @param Management $management
     * @param StorageInterface $storage
     * @param LoggerInterface|null $logger
     */
    public function __construct(Management $management, StorageInterface $storage, LoggerInterface $logger = null)
    {
        $this->manager = $management;
        $this->storage = $storage;
        $this->logger = $logger;
    }

    /**
     * Function receives AS2 requests from partner.
     * Checks whether it's an AS2 message or an MDN and acts accordingly.
     *
     * @param ServerRequestInterface|null $request
     * @return Response
     * @throws \Exception
     */
    public function execute(ServerRequestInterface $request = null)
    {
        if (!$request) {
            $request = ServerRequest::fromGlobals();
        }

        $this->validate($request, ['message-id', 'as2-from', 'as2-to']);

        // Process the posted AS2 message

        $serverParams = $request->getServerParams();
        $messageId = trim($request->getHeaderLine('message-id'), '<>');
        $as2from = $request->getHeaderLine('as2-from');
        $as2to = $request->getHeaderLine('as2-to');

        $this->getLogger()->debug('Incoming AS2 message transmission.', [
            'ip' => isset($serverParams['REMOTE_ADDR']) ? $serverParams['REMOTE_ADDR'] : null,
            'message_id' => $messageId,
            'as2from' => $as2from,
            'as2to' => $as2to,
        ]);

        $responseStatus = 200;
        $responseHeaders = [];
        $responseBody = null;

        try {

            $this->getLogger()->debug('Check payload to see if it\'s a AS2 Message or ASYNC MDN.');

            // Get the message sender and receiver AS2 IDs
            $sender = $this->storage->getPartner($as2from);
            if (!$sender) {
                throw new \InvalidArgumentException(sprintf('Unknown AS2 Sender "%s"', $as2from));
            }
            $receiver = $this->storage->getPartner($as2to);
            if (!$receiver) {
                throw new \InvalidArgumentException(sprintf('Unknown AS2 Receiver "%s"', $as2to));
            }

            $payload = MimePart::fromRequest($request, true);

            // Check if this is an MDN message
            $mdn = null;
            if ($payload->isReport()) {
                $mdn = $payload;
            } elseif ($payload->isSigned()) {
                foreach ($payload->getParts() as $part) {
                    if ($part->isReport()) {
                        $mdn = $part;
                    }
                }
            }

            // TODO: check signature

            //  If this is a MDN, get the message ID and check if it exists
            if ($mdn) {
                $messageId = null;
                foreach ($mdn->getParts() as $part) {
                    if ($part->getParsedHeader('content-type', 0, 0) == 'message/disposition-notification') {
                        $bodyPayload = MimePart::fromString($part->getBody());
                        $messageId = trim($bodyPayload->getParsedHeader('original-message-id', 0, 0), '<>');
                    }
                }
                if (!empty($messageId)) {
                    $this->getLogger()->debug('Asynchronous MDN received for AS2 message', [$messageId]);
                    $message = $this->storage->getMessage($messageId);
                    if (!$message) {
                        throw new \InvalidArgumentException('Unknown AS2 MDN received. Will not be processed');
                    }
                    $this->manager->processMdn($message, $payload);
                    $this->storage->saveMessage($message);
                    $responseBody = 'AS2 ASYNC MDN has been received';
                }
            } else {
                $this->getLogger()->debug('Received an AS2 message', [$messageId]);

                // Initialize Message
                $message = $this->storage->initMessage([
                    'direction' => MessageInterface::DIR_INBOUND
                ]);
                $message->setMessageId($messageId);
                $message->setSender($sender);
                $message->setReceiver($receiver);

                $this->manager->processMessage($message, $payload);
                $this->storage->saveMessage($message);

                // Send MDN
                if ($mdnMode = $receiver->getMdnMode()) {
                    $this->getLogger()->debug('Send MDN', [$messageId, $mdnMode]);
                    $mdn = $this->manager->buildMdn($message);
                    if ($mdnMode == PartnerInterface::MDN_MODE_SYNC) {
                        $responseHeaders = $mdn->getHeaders();
                        $responseBody = $mdn->getBody();
                    } else {
                        // ASYNC send MDN
                        // TODO: parallel
                        $this->manager->sendMdn($message);
                        $responseBody = 'AS2 ASYNC MDN has been sent';
                    }
                }
            }

        } catch (\InvalidArgumentException $e) {
            $this->getLogger()->error($e->getMessage());
            $responseStatus = 400;
            $responseBody = $e->getMessage();
        } catch (\Exception $e) {
            $this->getLogger()->critical($e->getMessage());
            $responseStatus = 500;
            $responseBody = $e->getMessage();
        }

        return new Response($responseStatus, $responseHeaders, $responseBody);
    }

    /**
     * @param ServerRequestInterface $request
     * @param array $requiredHeaders
     * @throws \Exception
     */
    public function validate(ServerRequestInterface $request, $requiredHeaders = [])
    {
        if ($request->getMethod() !== 'POST') {
            throw new \Exception('To submit an AS2 message, you must POST the message to this URL.');
        }

        if (!$request->getBody()->getSize()) {
            throw new \Exception('An empty AS2 message was received');
        }

        foreach ($requiredHeaders as $header) {
            if (!$request->hasHeader($header)) {
                throw new \Exception(sprintf('Missing "%s" header', $header));
            }
        }
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

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        if (!$this->logger) {
            $this->logger = new NullLogger();
        }
        return $this->logger;
    }

}