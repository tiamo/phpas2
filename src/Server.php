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
            'id' => $messageId,
            'from' => $as2from,
            'to' => $as2to,
        ]);

        try {

            $payload = new MimePart($request->getBody()->getContents(), $request->getHeaders());

            // Check if this is MDN message
            if ($payload->isReport()) {
                $messageId = null;
                foreach ($payload->getParts() as $part) {
                    if ($part->getContentType()->getType() == 'message/disposition-notification') {
                        $headers = Headers::fromString($part->getBody());
                        $messageId = trim($headers->get('original-message-id')->getFieldValue(), '<>');
                    }
                }
                if (!empty($messageId)) {
                    $this->getLogger()->debug('Asynchronous MDN received for AS2 message', [$messageId]);
                    try {
                        $message = $this->storage->getMessageById($messageId);
                        if (!$message) {
                            throw new \InvalidArgumentException('Unknown AS2 MDN received. Will not be processed');
                        }
                        $this->manager->saveMdn($message, $payload);
                    } catch (\Exception $e) {
                        $this->getLogger()->error($e->getMessage(), [$messageId]);
                    }
                }
            } else {

                $this->getLogger()->debug('Received an AS2 message', [$messageId]);

                $sender = $this->storage->getPartnerById($as2from);
                if (!$sender) {
                    throw new \InvalidArgumentException('Unknown AS2 Sender');
                }
                $receiver = $this->storage->getPartnerById($as2to);
                if (!$receiver) {
                    throw new \InvalidArgumentException('Unknown AS2 Receiver');
                }

                $message = $this->storage->initMessage([
                    'id' => $messageId,
                    'sender' => $sender,
                    'receiver' => $receiver,
                ]);

                $this->storage->saveMessage($this->manager->prepareMessage($message, $payload));

                // TODO: MDN response if mode is sync
                return new Response(200, [], 'ok');
            }

        } catch (\InvalidArgumentException $e) {
            $this->getLogger()->error($e->getMessage());
            return new Response(400, [], $e->getMessage());
        } catch (\Exception $e) {
            $this->getLogger()->critical($e->getMessage());
            return new Response(500, [], $e->getMessage());
        }

        // TODO: MDN response if mode is sync
        return new Response(200);
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

    /**
     * Close current HTTP connection and wait some secons
     *
     * @param int $sleep The number of seconds to wait for
     */
    protected function closeConnectionAndWait($sleep)
    {
        // cut connexion and wait a few seconds
        ob_end_clean();
        header("Connection: close\r\n");
        header("Content-Encoding: none\r\n");
        ignore_user_abort(true); // optional
        ob_start();
        $size = ob_get_length();
        header("Content-Length: $size");
        ob_end_flush(); // Strange behaviour, will not work
        flush(); // Unless both are called !
        ob_end_clean();
        session_write_close();

        // wait some seconds before sending MDN notification
        sleep($sleep);
    }

}