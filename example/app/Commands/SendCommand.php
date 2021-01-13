<?php

namespace App\Commands;

use App\Repositories\MessageRepository;
use App\Repositories\PartnerRepository;
use AS2\Management;
use AS2\Utils;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SendCommand extends Command
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct('send-message');
    }

    protected function configure()
    {
        $this
            ->setDescription('Send message to the partner')
            ->setHelp('This command allows you to send a message to the partner...')
            ->addOption('file', null, InputOption::VALUE_OPTIONAL, 'File to send')
            ->addOption('from', null, InputOption::VALUE_REQUIRED, 'Sender partner as2id')
            ->addOption('to', null, InputOption::VALUE_REQUIRED, 'Receiver partner as2id');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file = $input->getOption('file');

        if (! empty($file)) {
            if (! file_exists($file)) {
                throw new \RuntimeException(
                    sprintf('File `%s` not found, please enter the correct file path.', $file)
                );
            }
        } else {

            // Default test message

            $rawMessage = <<<MSG
Content-type: Application/EDI-X12
Content-disposition: attachment; filename=payload
Content-id: <test@test.com>

ISA*00~
MSG;
        }

        /** @var PartnerRepository $partnerRepository */
        $partnerRepository = $this->container->get('PartnerRepository');

        $sender = $partnerRepository->findPartnerById($input->getOption('from'));
        $receiver = $partnerRepository->findPartnerById($input->getOption('to'));

        // Initialize New Message
        $messageId = Utils::generateMessageID($sender);

        // $output->writeln('Initialize new message with id: ' . $messageId);

        /** @var MessageRepository $messageRepository */
        $messageRepository = $this->container->get('MessageRepository');
        $message = $messageRepository->createMessage();
        $message->setMessageId($messageId);
        $message->setSender($sender);
        $message->setReceiver($receiver);

        /** @var Management $manager */
        $manager = $this->container->get('manager');

        // Generate Message Payload
        if (isset($rawMessage)) {
            $payload = $manager->buildMessage($message, $rawMessage);
        } else {
            $payload = $manager->buildMessageFromFile($message, $file);
        }

        // $output->writeln('The message was built successfully...');

        // Try to send a message
        $manager->sendMessage($message, $payload);

        // $output->writeln('Status: ' . $message->getStatus());
        // $output->writeln('Status Message: ' . $message->getStatusMsg());

        $messageRepository->saveMessage($message);
    }
}
