<?php

use App\Repositories\MessageRepository;
use App\Repositories\PartnerRepository;
use AS2\Server;
use AS2\Utils;
use AS2\Management;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\App;

return function (App $app) {

    /**
     * AS2 Receiver
     */
    $app->get('/', function (Request $request, Response $response, array $args) {
        $server = new Server(
            $this->get('manager'),
            $this->get('PartnerRepository'),
            $this->get('MessageRepository')
        );

        // $message = file_get_contents(__DIR__ . '/tmp/phpas2_aXFQKQ');
        // $payload = \AS2\Utils::parseMessage($message);
        // $serverRequest = new ServerRequest(
        //     'POST',
        //     'http:://localhost',
        //     $payload['headers'],
        //     $payload['body'],
        //     '1.1',
        //     [
        //         'REMOTE_ADDR' => '127.0.0.1'
        //     ]
        // );
        // return $server->execute($serverRequest);

        return $server->execute($request);
    });

    /**
     * Send a message
     */
    $app->get('/send', function (Request $request, Response $response, array $args) {

        if (empty($args['sender'])) {
            throw new \RuntimeException('`sender` required');
        }

        if (empty($args['receiver'])) {
            throw new \RuntimeException('`receiver` required');
        }

        $rawMessage = <<<MSG
Content-type: Application/EDI-X12
Content-disposition: attachment; filename=payload
Content-id: <test@test.com>

ISA*00~
MSG;

        /** @var PartnerRepository $partnerRepository */
        $partnerRepository = $this->get('PartnerRepository');
        $sender = $partnerRepository->findPartnerById($args['sender']);
        $receiver = $partnerRepository->findPartnerById($args['receiver']);

        // Initialize New Message

        $messageId = Utils::generateMessageID($args['sender']);

        /** @var MessageRepository $messageRepository */
        $messageRepository = $this->get('MessageRepository');
        $message = $messageRepository->createMessage();
        $message->setMessageId($messageId);
        $message->setSender($sender);
        $message->setReceiver($receiver);

        /** @var Management $manager */
        $manager = $this->get('manager');

        // Generate Message Payload
        $payload = $manager->buildMessage($message, $rawMessage);

        $status = false;

        // Try to send a message
        $result = $manager->sendMessage($message, $payload);
        if ($result) {
            // echo MimePart::fromPsrMessage($response);
            $status = true;
        }

        $messageRepository->saveMessage($message);

        return [
            'status' => $status,
        ];
    });

};
