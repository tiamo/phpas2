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
     * @param  Management  $management
     * @param  StorageInterface  $storage
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
     * @param  ServerRequestInterface|null  $request
     * @return Response
     * @throws \Throwable
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
                return new Response(200, [], 'To submit an AS2 message, you must POST the message to this URL.');
            }

            $this->getLogger()->debug(sprintf('Received an HTTP POST from `%s`.', $_SERVER['REMOTE_ADDR']));

            foreach (['message-id', 'as2-from', 'as2-to'] as $header) {
                if (! $request->hasHeader($header)) {
                    throw new \InvalidArgumentException(sprintf('Missing required header `%s`.', $header));
                }
            }

            // Get the message id, sender and receiver AS2 IDs
            $messageId = trim($request->getHeaderLine('message-id'), '<>');
            $senderId = $request->getHeaderLine('as2-from');
            $receiverId = $request->getHeaderLine('as2-to');

            $this->getLogger()->debug(sprintf('Check payload to see if its an AS2 Message or ASYNC MDN.'));

            // Load the request header and body as a MIME Email Message
            $payload = MimePart::fromPsrMessage($request);

            // If this is an MDN, get the message ID and check if it exists
            if ($payload->isReport()) {
                $this->getLogger()->info(
                    sprintf(
                        'Asynchronous MDN received for AS2 message `%s` to organization `%s` from partner `%s`.',
                        $messageId,
                        $receiverId,
                        $senderId
                    )
                );
                // Get Original Message-Id
                $origMessageId = null;
                foreach ($payload->getParts() as $part) {
                    if ($part->getParsedHeader('content-type', 0, 0) === 'message/disposition-notification') {
                        $bodyPayload = MimePart::fromString($part->getBody());
                        $origMessageId = trim($bodyPayload->getParsedHeader('original-message-id', 0, 0), '<>');
                    }
                }
                $message = $this->storage->getMessage($origMessageId);
                if (! $message) {
                    throw new \RuntimeException('Unknown AS2 MDN received. Will not be processed');
                }
                // TODO: check if mdn already exists
                $this->manager->processMdn($message, $payload);
                $this->storage->saveMessage($message);
                $responseBody = 'AS2 ASYNC MDN has been received';
            } else {
                // Process the received AS2 message from partner

                // Raise duplicate message error in case message already exists in the system
                $message = $this->storage->getMessage($messageId);
                if ($message) {
                    throw new \RuntimeException('An identical message has already been sent to our server');
                }

                $sender = $this->findPartner($senderId);
                $receiver = $this->findPartner($receiverId);

                // Create a new message
                $message = $this->storage->initMessage();
                $message->setMessageId($messageId);
                $message->setDirection(MessageInterface::DIR_INBOUND);
                $message->setStatus(MessageInterface::STATUS_IN_PROCESS);
                $message->setSender($sender);
                $message->setReceiver($receiver);

                try {
                    // Process the received payload to extract the actual message from partner
                    $payload = $this->manager->processMessage($message, $payload);
                    $message->setPayload($payload);

                    // If MDN enabled than send notification
                    // Create MDN if it requested by partner
                    if (($mdnMode = $receiver->getMdnMode()) && ($mdn = $this->manager->buildMdn($message))) {
                        $mdnMessageId = trim($mdn->getHeaderLine('message-id'), '<>');
                        $message->setMdnPayload($mdn);
                        if ($mdnMode === PartnerInterface::MDN_MODE_SYNC) {
                            $this->getLogger()->debug(
                                sprintf(
                                    'Synchronous MDN with id `%s` sent as answer to message `%s`.',
                                    $mdnMessageId,
                                    $messageId
                                )
                            );
                            $responseHeaders = $mdn->getHeaders();
                            $responseBody = $mdn->getBody();
                        } else {
                            $this->getLogger()->debug(
                                sprintf(
                                    'Asynchronous MDN with id `%s` sent as answer to message `%s`.',
                                    $mdnMessageId,
                                    $messageId
                                )
                            );
                            $this->manager->sendMdn($message);
                        }
                    }

                    $message->setStatus(MessageInterface::STATUS_SUCCESS);
                } catch (\Throwable $e) {
                    $message->setStatus(MessageInterface::STATUS_ERROR);
                    $message->setStatusMsg($e->getMessage());
                } finally {
                    $this->storage->saveMessage($message);
                }
            }

        } catch (\Throwable $e) {
            $this->getLogger()->critical($e->getMessage());
            if (! empty($message)) {
                // TODO: check
                // Build the mdn for the message based on processing status
                $mdn = $this->manager->buildMdn($message, null, $e->getMessage());
                $responseHeaders = $mdn->getHeaders();
                $responseBody = $mdn->getBody();
            } else {
                $responseStatus = 500;
                $responseBody = $e->getMessage();
            }
        }

        if (empty($responseBody)) {
            $responseBody = 'AS2 message has been received';
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
     * @param  LoggerInterface  $logger
     * @return $this
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @param  string  $id
     * @return PartnerInterface
     */
    protected function findPartner($id)
    {
        $partner = $this->storage->getPartner($id);
        if (! $partner) {
            throw new \InvalidArgumentException(sprintf('Unknown AS2 Partner with id `%s`.', $id));
        }

        return $partner;
    }
}
