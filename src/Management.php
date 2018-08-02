<?php

namespace AS2;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Management
{
    const AS2_VERSION = '1.2';
    const USER_AGENT = 'PHPAS2';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Client
     */
    protected $httpClient;

    /**
     * @var array
     */
    protected $options = [
        /** @see \GuzzleHttp\Client */
        'client_config' => [],
    ];

    /**
     * Management constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * @param MessageInterface $message
     * @param string $filePath
     * @param string $contentType
     * @return MimePart
     * @throws \Exception
     */
    public function buildMessageFromFile(MessageInterface $message, $filePath, $contentType = null)
    {
        if (! $contentType) {
            $contentType = $message->getReceiver()->getContentType();
        }
        $payload = new MimePart([
            'Content-Type' => $contentType ? $contentType : 'text/plain',
            'Content-Disposition' => 'attachment; filename="' . basename($filePath) . '"',
        ], file_get_contents($filePath));

        return $this->buildMessage($message, $payload);
    }

    /**
     * Build the AS2 mime message to be sent to the partner.
     * Encrypts, signs and compresses the message based on the partner profile.
     * Returns the message final message content.
     *
     * @param MessageInterface $message
     * @param MimePart|string $payload
     * @return MimePart
     * @throws \Exception
     */
    public function buildMessage(MessageInterface $message, $payload)
    {
        $sender = $message->getSender();
        if (! $sender) {
            throw new \InvalidArgumentException('Unknown Sender');
        }
        $receiver = $message->getReceiver();
        if (! $receiver) {
            throw new \InvalidArgumentException('Unknown Receiver');
        }

        $message->setStatus(MessageInterface::STATUS_PENDING);
        $message->setPayload($payload);

        $this->getLogger()->debug('Build the AS2 message to send to the partner');

        // Build the As2 message headers as per specifications
        $as2headers = [
            'MIME-Version' => '1.0',
            'AS2-Version' => self::AS2_VERSION,
            'User-Agent' => self::USER_AGENT,
            'Message-ID' => $message->getMessageId(),
            'AS2-From' => $sender->getAs2Id(),
            'AS2-To' => $receiver->getAs2Id(),
            'Subject' => $receiver->getSubject() ? $receiver->getSubject() : 'AS2 Message',
            'Date' => date('r'),
            'Recipient-Address' => $receiver->getTargetUrl(),
            'Ediint-Features' => 'CEM',
        ];

        if (! ($payload instanceof MimePart)) {
            $payload = MimePart::fromString($payload);
        }

        $micContent = Utils::canonicalize($payload);

        // Compress the message if requested in the profile
        if ($receiver->getCompressionType()) {
            $this->getLogger()->debug('Compress the message');
            $payload = CryptoHelper::compress($payload);
            $message->setCompressed();
        }

        // Sign the message if requested in the profile
        if ($signAlgo = $receiver->getSignatureAlgorithm()) {
            $this->getLogger()->debug('Signing the message using partner key');

            //            // If MIC content is set, i.e. message has been signed then calculate the MIC
            //            $mdnOptions = Utils::parseHeader($receiver->getMdnOptions());
            //            $micAlgo = null;
            //            if (isset($mdnOptions[2])) {
            //                $micAlgo = reset($mdnOptions[2]);
            //                $micAlgo = trim($micAlgo);
            //            }

            $this->getLogger()->debug('Calculate MIC', ['algo' => $signAlgo]);
            $message->setMic(CryptoHelper::calculateMIC($micContent, $signAlgo));

            $payload = CryptoHelper::sign($payload,
                $sender->getCertificate(),
                [$sender->getPrivateKey(), $sender->getPrivateKeyPassPhrase()],
                [],
                $signAlgo
            );

            $message->setSigned();
        }

        // Encrypt the message if requested in the profile
        if ($cipher = $receiver->getEncryptionAlgorithm()) {
            $this->getLogger()->debug('Encrypting the message using partner public key');
            $payload = CryptoHelper::encrypt($payload, $receiver->getCertificate(), $cipher);
            $message->setEncrypted();
        }

        //  If MDN is to be requested from the partner, set the appropriate headers
        if ($receiver->getMdnMode()) {

            $as2headers['Disposition-Notification-To'] = $sender->getTargetUrl();
            $as2headers['Disposition-Notification-Options'] = $receiver->getMdnOptions();

            // PARTNER IS ASYNC MDN
            if ($receiver->getMdnMode() == PartnerInterface::MDN_MODE_ASYNC) {
                $message->setMdnMode(PartnerInterface::MDN_MODE_ASYNC);
                $as2headers['Receipt-Delivery-Option'] = $sender->getTargetUrl();
            } else {
                $message->setMdnMode(PartnerInterface::MDN_MODE_SYNC);
            }
        }

        // Extract the As2 headers as a string and save it to the message object
        foreach ($payload->getHeaders() as $name => $values) {
            $as2headers[$name] = implode(', ', $values);
        }

        // TODO: refactory
        $as2headers['Content-Type'] = str_replace('x-pkcs7', 'pkcs7', $as2headers['Content-Type']);

        $as2Message = new MimePart($as2headers, $payload->getBody());

        $message->setHeaders($as2Message->getHeaderLines());

        $this->getLogger()->debug('AS2 message has been built successfully, sending it to the partner');

        return $as2Message;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
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

    /**
     * Sends the AS2 message to the partner.
     * Takes the message as argument and posts the as2 message to the partner.
     *
     * @param MessageInterface $message
     * @param MimePart $payload
     * @return \Psr\Http\Message\ResponseInterface|false
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendMessage(MessageInterface $message, $payload)
    {
        $partner = $message->getReceiver();

        if (! ($payload instanceof MimePart)) {
            $payload = MimePart::fromString($payload);
        }

        try {
            $options = [
                'headers' => $payload->getHeaders(),
                'body' => $payload->getBody(),
                //                'cert' => '' // TODO: partner https cert ?
            ];

            if ($partner->getAuthMethod()) {
                $options['auth'] = [$partner->getAuthUser(), $partner->getAuthPassword(), $partner->getAuthMethod()];
            }

            $response = $this->getHttpClient()->request('POST', $partner->getTargetUrl(), $options);
            if ($response->getStatusCode() != 200) {
                throw new \RuntimeException('Message send failed with error');
            }

            $this->getLogger()->debug('AS2 message successfully sent to partner');

            // Process the MDN based on the partner profile settings
            if ($mdnMode = $partner->getMdnMode()) {
                if ($mdnMode == PartnerInterface::MDN_MODE_ASYNC) {
                    $this->getLogger()->debug('Requested ASYNC MDN from partner, waiting for it');
                    $message->setStatus(MessageInterface::STATUS_PENDING);
                } else {
                    // In case of Synchronous MDN the response content will be the MDN. So process it.
                    // Get the response headers, convert key to lower case for normalization
                    $this->getLogger()->debug('Synchronous MDN received from partner');

                    $body = $response->getBody()->getContents();
                    $payload = new MimePart($response->getHeaders(), $body);

                    $this->processMdn($message, $payload);
                }
            } else {
                $this->getLogger()->debug('No MDN needed, File Transferred successfully to the partner');
            }

            $message->setStatus(MessageInterface::STATUS_SUCCESS);

            return $response;

        } catch (\Exception $e) {
            $this->getLogger()->critical($e->getMessage());
            $message->setStatus(MessageInterface::STATUS_ERROR);
        }

        return false;
    }

    /**
     * @return Client
     */
    public function getHttpClient()
    {
        if (! $this->httpClient) {
            $this->httpClient = new Client($this->getOption('client_config'));
        }

        return $this->httpClient;
    }

    /**
     * @param string $name
     * @param null $default
     * @return mixed|null
     */
    public function getOption($name, $default = null)
    {
        return isset($this->options[$name]) ? $this->options[$name] : $default;
    }

    /**
     * Process the received MDN and check status of sent message.
     * Takes the raw mdn as input, verifies the signature if present and the extracts the status of the original message.
     *
     * @param MessageInterface $message
     * @param MimePart|string $payload
     * @return boolean
     * @throws \Exception
     */
    public function processMdn(MessageInterface $message, $payload)
    {
        if (! ($payload instanceof MimePart)) {
            $payload = MimePart::fromString($payload);
        }

        if ($payload->isSigned()) {
            foreach ($payload->getParts() as $part) {
                if (! $part->isPkc7Signature()) {
                    $payload = $part;
                }
            }
        }

        // Raise error if message is not an MDN
        if (! $payload->isReport()) {
            throw new \RuntimeException('MDN report not found in the response');
        }

        $messageId = $message->getMessageId();

        // Save the MDN to the store
        $message->setMdnStatus(MessageInterface::MDN_STATUS_PENDING);
        $message->setMdnPayload($payload);

        foreach ($payload->getParts() as $part) {
            if ($part->getParsedHeader('content-type', 0, 0) == 'message/disposition-notification') {
                $this->getLogger()->debug('Found MDN report for message', [$messageId]);
                try {
                    $bodyPayload = MimePart::fromString($part->getBody());

                    if ($bodyPayload->hasHeader('disposition')) {
                        $mdnStatus = $bodyPayload->getParsedHeader('Disposition', 0, 1);
                        if ($mdnStatus == 'processed') {
                            $this->getLogger()->debug('Message has been successfully processed, verifying the MIC if present.');
                            // Compare the MIC of the received message
                            $receivedMic = $bodyPayload->getHeaderLine('Received-Content-MIC');
                            if ($receivedMic && $message->getMic()) {

                                if (Utils::normalizeMic($message->getMic()) != Utils::normalizeMic($receivedMic)) {
                                    throw new \Exception(
                                        sprintf('The Message Integrity Code (MIC) does not match the sent AS2 message (required: %s, returned: %s)',
                                            $message->getMic(),
                                            $receivedMic
                                        )
                                    );
                                }
                            }
                            $message->setMdnStatus(MessageInterface::MDN_STATUS_RECEIVED);
                            $this->getLogger()->debug('File Transferred successfully to the partner');
                        } else {
                            throw new \Exception('Partner failed to process file. ' . $mdnStatus);
                        }
                    }
                } catch (\Exception $e) {
                    $message->setMdnStatus(MessageInterface::MDN_STATUS_ERROR);
                    $message->setStatusMsg($e->getMessage());
                    $this->getLogger()->error($e->getMessage(), [$messageId]);
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Build the AS2 MDN to be sent to the partner.
     *
     * @param MessageInterface $message
     * @param string $confirmationText
     * @param string $errorMessage
     * @return MimePart
     * @throws \InvalidArgumentException
     */
    public function buildMdn(MessageInterface $message, $confirmationText = null, $errorMessage = null)
    {
        $sender = $message->getSender();
        if (! $sender) {
            throw new \InvalidArgumentException('Unknown Message Sender');
        }
        $receiver = $message->getReceiver();
        if (! $receiver) {
            throw new \InvalidArgumentException('Unknown Message Receiver');
        }

        $messageId = $message->getMessageId();
        $this->getLogger()->debug(sprintf('Generating outbound MDN, setting message id to "%s"', $messageId));

        $boundary = '=_' . sha1(uniqid('', true));
        $reportHeaders = [
            'Content-Type' => 'multipart/report; report-type=disposition-notification; boundary="----' . $boundary . '"',
        ];

        // Parse Message Headers
        $messageHeaders = MimePart::fromString($message->getHeaders());
        $isSignedRequested = $messageHeaders->hasHeader('disposition-notification-options');

        $headers = [
            'Message-ID' => '<' . Utils::generateMessageID($receiver) . '>',
            'Date' => date('r'),
            'Ediint-Features' => 'CEM', // multiple-attachments, CEM
            'AS2-From' => $receiver->getAs2Id(),
            'AS2-To' => $sender->getAs2Id(),
            'AS2-Version' => self::AS2_VERSION,
            'User-Agent' => self::USER_AGENT,
            'Connection' => 'close',
        ];

        if (! $isSignedRequested) {
            $reportHeaders['Mime-Version'] = '1.0';
            $reportHeaders += $headers;
        }

        if (empty($confirmationText)) {
            $confirmationText = 'The AS2 message has been received';
        }

        // Build the text message with confirmation text and add to report
        $report = new MimePart($reportHeaders);
        $report->addPart(new MimePart([
            'Content-Type' => 'text/plain',
            'Content-Transfer-Encoding' => '7bit',
        ], $confirmationText));

        // Build the MDN message and add to report
        $mdnData = [
            'Reporting-UA' => self::USER_AGENT,
            'Original-Recipient' => 'rfc822; ' . $receiver->getAs2Id(),
            'Final-Recipient' => 'rfc822; ' . $receiver->getAs2Id(),
            'Original-Message-ID' => '<' . $message->getMessageId() . '>',
            'Disposition' => 'automatic-action/MDN-sent-automatically; processed' . ($errorMessage ? '/error: ' . $errorMessage : ''),
        ];
        if ($mic = $message->getMic()) {
            $mdnData['Received-Content-MIC'] = $mic;
        }
        $report->addPart(new MimePart([
            'Content-Type' => 'message/disposition-notification',
            'Content-Transfer-Encoding' => '7bit',
        ], Utils::normalizeHeaders($mdnData)));

        // If signed MDN is requested by partner then sign the MDN and attach to report
        if ($isSignedRequested) {
            $this->getLogger()->debug('Outbound MDN has been signed.');
            $x509 = openssl_x509_read($receiver->getCertificate());
            $key = openssl_get_privatekey($receiver->getPrivateKey(), $receiver->getPrivateKeyPassPhrase());
            $report = CryptoHelper::sign($report, $x509, $key, $headers);
        }

        $this->getLogger()->debug(sprintf('Outbound MDN created for AS2 message "%s".', $messageId));

        if ($messageHeaders->hasHeader('receipt-delivery-option')) {
            $message->setMdnMode(PartnerInterface::MDN_MODE_ASYNC);
            $message->setMdnStatus(MessageInterface::MDN_STATUS_PENDING);
            $this->getLogger()->debug('Asynchronous MDN requested, setting status to pending');
        } else {
            $message->setMdnMode(PartnerInterface::MDN_MODE_SYNC);
            $message->setMdnStatus(MessageInterface::MDN_STATUS_SENT);
        }

        $message->setMdnPayload($report->toString());

        return $report;
    }

    /**
     * Sends the AS2 MDN to the partner.
     *
     * @param MessageInterface $message
     * @return bool|mixed|\Psr\Http\Message\ResponseInterface
     */
    public function sendMdn(MessageInterface $message)
    {
        try {
            $partner = $message->getReceiver();
            $mdn = MimePart::fromString($message->getMdnPayload());
            $options = [
                'body' => $mdn->getBody(),
                'headers' => $mdn->getHeaders(),
            ];
            if ($partner->getAuthMethod()) {
                $options['auth'] = [$partner->getAuthUser(), $partner->getAuthPassword(), $partner->getAuthMethod()];
            }
            $response = $this->getHttpClient()->post($partner->getTargetUrl(), $options);
            if ($response->getStatusCode() != 200) {
                throw new \RuntimeException('Message send failed with error');
            }
            $this->getLogger()->debug('AS2 MDN has been sent.');
            $message->setMdnStatus(MessageInterface::MDN_STATUS_SENT);

            return $response;
        } catch (\Exception $e) {
            $this->getLogger()->critical($e->getMessage());
            $message->setMdnStatus(MessageInterface::MDN_STATUS_ERROR);
        }

        return false;
    }
}
