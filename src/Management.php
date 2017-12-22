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
        'mdn_notification_to' => 'vk.tiamo@gmail.com',
        /** @see \GuzzleHttp\Client */
        'client_config' => [],
//        'mdn_url' => null,
    ];

    /**
     * Management constructor.
     * @param array $options
     */
    public function __construct($options = [])
    {
        $this->options = array_merge($this->options, $options);
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
     * @param string $data
     * @return array
     */
    public function parseMdnOptions($data)
    {
        $options = [];
        $values = preg_split('#\s*;\s*#', $data);
        $values = array_filter($values);
        foreach ($values as $keyValuePair) {
            list($key, $value) = explode('=', trim($keyValuePair), 2);
            $value = trim($value, "'\" \t\n\r\0\x0B");
            $options[$key] = $value;
        }
        return $options;
    }

    /**
     * Build the AS2 mime message to be sent to the partner.
     * Encrypts, signs and compresses the message based on the partner profile.
     * Returns the message final message content.
     *
     * @param MessageInterface $message
     * @param MimePart|string $payload
     * @return MessageInterface
     * @throws \Exception
     */
    public function buildMessage(MessageInterface $message, $payload)
    {
        $sender = $message->getSender();
        if (!$sender) {
            throw new \Exception('Unknown Sender');
        }
        $receiver = $message->getReceiver();
        if (!$receiver) {
            throw new \Exception('Unknown Receiver');
        }

        $this->getLogger()->debug('Build the AS2 message and header to send to the partner');

        // Build the As2 message headers as per specifications
        $as2headers = new Headers([
            'MIME-Version' => '1.0',
            'AS2-Version' => self::AS2_VERSION,
            'Message-ID' => $message->getMessageId(),
            'AS2-From' => $sender->getAs2Id(),
            'AS2-To' => $receiver->getAs2Id(),
            'Subject' => $receiver->getSubject() ? $receiver->getSubject() :
                sprintf('AS2 Message from %s to %s', $sender->getAs2Id(), $receiver->getAs2Id()),
            'Date' => date('r'),
            'recipient-address' => $receiver->getTargetUrl(),
            'ediint-features' => 'CEM',
            'user-agent' => self::USER_AGENT,
        ]);

        if (!($payload instanceof MimePart)) {
            $payload = MimePart::fromString($payload);
        }

        // Compress the message if requested in the profile
        if ($receiver->getCompressionType()) {
            $this->getLogger()->debug('Compress the message');
            $payload = CryptoHelper::compress($payload);
            $message->isCompressed(true);
        }

        // Sign the message if requested in the profile
        if ($receiver->getSignatureAlgorithm()) {
            $this->getLogger()->debug('Signing the message using partner key');
            $payload = CryptoHelper::sign($payload,
                $sender->getPublicKey(),
                [$sender->getPrivateKey(), $sender->getPrivateKeyPassPhrase()]
            );
            // If MIC content is set, i.e. message has been signed then calculate the MIC
            $mdnOptions = $this->parseMdnOptions($receiver->getMdnOptions());
            $micAlgo = null;
            if (isset($mdnOptions['signed-receipt-micalg'])) {
                $algo = explode(',', $mdnOptions['signed-receipt-micalg']);
                $micAlgo = trim(array_pop($algo));
            }
            $this->getLogger()->debug('Calculate MIC', ['algo' => $micAlgo]);
            $message->setCalculatedMic(CryptoHelper::calculateMIC($payload, $micAlgo));
            $message->isSigned(true);
        }

        // Encrypt the message if requested in the profile
        if ($receiver->getEncryptionAlgorithm()) {
            $this->getLogger()->debug('Encrypting the message using partner public key');
            // TODO: set cipher based by algorithm, default is sha256
            $payload = CryptoHelper::encrypt($payload, $receiver->getPublicKey());
            $message->isEncrypted(true);
        }

        //  If MDN is to be requested from the partner, set the appropriate headers
        if ($receiver->getMdnMode()) {
            $as2headers->addHeaderLine('disposition-notification-to', $this->getOption('mdn_notification_to'));
            $as2headers->addHeaderLine('disposition-notification-options', $receiver->getMdnOptions());
            // PARTNER IS ASYNC MDN
            if ($receiver->getMdnMode() == PartnerInterface::MDN_MODE_ASYNC) {
//                $message->setMdnMode(PartnerInterface::MDN_MODE_ASYNC);
                // TODO: get mdn_url from config ?
                $as2headers->addHeaderLine('receipt-delivery-option', $sender->getTargetUrl());
            } else {
//                $message->setMdnMode(PartnerInterface::MDN_MODE_SYNC);
            }
        }

        // Extract the As2 headers as a string and save it to the message object
        foreach ($payload->getHeaders() as $header) {
            $as2headers->removeHeader($header->getFieldName());
            $as2headers->addHeader($header);
        }

        $message->setHeaders($as2headers->toString());
        $message->setPayload($payload->getBody());

        $this->getLogger()->debug('AS2 message has been built successfully, sending it to the partner');

        return $message;
    }

    /**
     * Sends the AS2 message to the partner.
     * Takes the message as argument and posts the as2 message to the partner.
     *
     * @param MessageInterface $message
     * @return \Psr\Http\Message\ResponseInterface|false
     */
    public function sendMessage(MessageInterface $message)
    {
        $partner = $message->getReceiver();

        try {
            // Send the AS2 message to the partner
            $options = [
                'body' => $message->getPayload(),
                'headers' => Headers::fromString($message->getHeaders())->toArray(),
//                'cert' => '' // TODO: partner https cert ?
            ];
            if ($partner->getAuthMethod()) {
                $options['auth'] = [$partner->getAuthUser(), $partner->getAuthPassword(), $partner->getAuthMethod()];
            }
            $response = $this->getHttpClient()->request('POST', $partner->getTargetUrl(), $options);
            if ($response->getStatusCode() != 200) {
                throw new \Exception('Message send failed with error');
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
                    $payload = new MimePart($response->getBody()->getContents(), $response->getHeaders());

                    $this->processMdn($message, $payload);
                }
            } else {
                $this->getLogger()->debug('No MDN needed, File Transferred successfully to the partner');
                $message->setStatus(MessageInterface::STATUS_SUCCESS);
            }

            return $response;

        } catch (\Exception $e) {
            $this->getLogger()->critical($e->getMessage());
            $message->setStatus(MessageInterface::STATUS_ERROR);
        }

        return false;
    }

    /**
     * Function decompresses, decrypts and verifies the received AS2 message
     * Takes an AS2 message as input and returns the actual payload ex. X12 message
     *
     * @param MessageInterface $message
     * @param MimePart|string $payload
     * @return MessageInterface
     * @throws \Exception
     */
    public function processMessage(MessageInterface $message, $payload)
    {
        $messageId = $message->getMessageId();

        $this->getLogger()->debug('Begin Processing of received AS2 message', [$messageId]);

        try {

            if (!($payload instanceof MimePart)) {
                $payload = MimePart::fromString($payload);
            }

            $partner = $message->getReceiver();

            // Check if message from this partner are expected to be encrypted
            if ($partner->getEncryptionAlgorithm() && !$payload->isEncrypted()) {
                throw new \Exception('Incoming message from AS2 partner are defined to be encrypted');
            }

            // Save initial headers
            $message->setHeaders($payload->getHeaders()->toString());

            // Check if payload is encrypted and if so decrypt it
            if ($payload->isEncrypted()) {
                $this->getLogger()->debug('Decrypting the payload using private key');
                $message->isEncrypted(true);
                $payload = CryptoHelper::decrypt($payload, $partner->getPublicKey(), [
                    $partner->getPrivateKey(),
                    $partner->getPrivateKeyPassPhrase(),
                ]);
                if ($payload == false) {
                    throw new \Exception('Failed to decrypt message');
                }
            }

            // Check if message from this partner are expected to be signed
            if ($partner->getSignatureAlgorithm() && !$payload->isSigned()) {
                throw new \Exception('Incoming message from AS2 partner are defined to be signed');
            }

            // Check if message is signed and if so verify it
            if ($payload->isSigned()) {
                $this->getLogger()->debug('Verifying the signed payload');
                $message->isSigned(true);
                $micAlg = $payload->getContentType()->getParameter('micalg');
                foreach ($payload->getParts() as $part) {
                    if (!$part->isPkc7Signature()) {
                        $payload = $part;
                    }
                }
                // Verify message using raw payload received from partner
                if (!CryptoHelper::verify($payload, $partner->getPublicKey())) {
                    throw new \Exception('Signature Verification Failed');
                }
                $message->setCalculatedMic(CryptoHelper::calculateMIC($payload, $micAlg, true));
            }

            // Check if the message has been compressed and if so decompress it
            if ($payload->isCompressed()) {
                $this->getLogger()->debug('Decompressing the payload');
                $message->isCompressed(true);
                $payload = CryptoHelper::decompress($payload);
            }

            $message->setPayload((string)$payload);

        } catch (\Exception $e) {
            $message->setStatus(MessageInterface::STATUS_ERROR);
            $this->getLogger()->error($e->getMessage(), [$messageId]);
        }

        return $message;
    }

    /**
     * Build the AS2 MDN to be sent to the partner.
     *
     * @param MessageInterface $message
     * @param string $text
     * @param array $headers
     * @return MimePart
     * @throws \Exception
     */
    public function buildMdn(MessageInterface $message, $text = null, $headers = [])
    {
        $sender = $message->getSender();
        if (!$sender) {
            throw new \Exception('Unknown Sender');
        }
        $receiver = $message->getReceiver();
        if (!$receiver) {
            throw new \Exception('Unknown Receiver');
        }

        $boundary = '=_' . strtoupper(md5(microtime(1)));

        $reportParts = new MimePart(null, [
            'content-type' => 'multipart/report; report-type=disposition-notification; boundary="----' . $boundary . '"',
        ]);

//        $text = sprintf('This MDN was automatically built on %s in response to a message with id %s received from %s on %s.
//Unless stated otherwise, the message to which this MDN applies was successfully processed.',
//            date('r'),
//            $message->getMessageId(),
//            $sender->getAs2Id()
//        );

        // Create the text part
        $textPart = new MimePart($text, [
            'content-type' => 'text/plain'
        ]);
        $reportParts->addPart($textPart);

        // Create the report part
        $reportPartHeaders = new Headers(array_merge([
            'Reporting-UA' => self::USER_AGENT,
            'Original-Recipient' => 'rfc822; ' . $message->getReceiver()->getAs2Id(),
            'Final-Recipient' => 'rfc822; ' . $message->getReceiver()->getAs2Id(),
            'Original-Message-ID' => '<' . $message->getMessageId() . '>',
            'Disposition' => 'automatic-action/MDN-sent-automatically; processed',
        ], $headers));

        if ($mic = $message->getCalculatedMic()) {
            $reportPartHeaders->addHeaderLine('Received-Content-MIC', $mic);
        }
        $reportPart = new MimePart($reportPartHeaders->toString(), [
            'content-type' => 'message/disposition-notification',
//            'content-transfer-encoding' => '7bit',
        ]);
        $reportParts->addPart($reportPart);

        $messageHeaders = Headers::fromString($message->getHeaders());

        $headers = [
            'Mime-Version' => '1.0',
            'Message-ID' => $message->getMessageId(),
            'Date' => date('r'),
            'Ediint-Features' => 'CEM',
            'As2-From' => $sender->getAs2Id(),
            'As2-To' => $receiver->getAs2Id(),
            'AS2-Version' => self::AS2_VERSION,
            'User-Agent' => self::USER_AGENT,
            'Subject' => ($subject = $receiver->getSubject()) ? $subject : 'Your Requested MDN Response',
        ];

        if ($email = $receiver->getEmail()) {
            $headers['Email'] = $email;
        }

        // If signed MDN is requested by partner then sign the MDN and attach to report
        if ($messageHeaders->get('disposition-notification-options')) {
            $x509 = openssl_x509_read($sender->getPublicKey());
            $key = openssl_get_privatekey($sender->getPrivateKey(), $sender->getPrivateKeyPassPhrase());
            $mdn = CryptoHelper::sign($reportParts, $x509, $key, $headers);
        } else {
            $mdn = $reportParts;
            foreach ($headers as $name => $value) {
                $mdn->addHeader($name, $value);
            }
        }

        $message->setMdnPayload($mdn->toString());

        return $mdn;
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
                'headers' => $mdn->getHeaders()->toArray(),
            ];
            if ($partner->getAuthMethod()) {
                $options['auth'] = [$partner->getAuthUser(), $partner->getAuthPassword(), $partner->getAuthMethod()];
            }
            $response = $this->getHttpClient()->request('POST', $partner->getTargetUrl(), $options);
            if ($response->getStatusCode() != 200) {
                throw new \Exception('Message send failed with error');
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
        if (!($payload instanceof MimePart)) {
            $payload = MimePart::fromString($payload);
        }

        // Raise error if message is not an MDN
        if (!$payload->isSigned() && !$payload->isReport()) {
            throw new \RuntimeException('MDN report not found in the response');
        }

        $messageId = $message->getMessageId();
        $partner = $message->getReceiver();

        if ($payload->isSigned()) {
            // Verify the signature using raw MDN content
            if (!CryptoHelper::verify($payload, $partner->getPublicKey())) {
                throw new \RuntimeException('MDN Signature Verification Error');
            }
            foreach ($payload->getParts() as $part) {
                if ($part->isReport()) {
                    $payload = $part;
                    break;
                }
            }
        }

        // Process the MDN report to extract the AS2 message status
        if (!$payload->isReport()) {
            throw new \RuntimeException('MDN report not found in the response');
        }

        // Save the MDN to the store
        $message->setMdnStatus(MessageInterface::MDN_STATUS_PENDING);
        $message->setMdnPayload($payload);

        foreach ($payload->getParts() as $part) {
            if ($part->getContentType()->getType() == 'message/disposition-notification') {
                $this->getLogger()->debug('Found MDN report for message', [$messageId]);
                try {
                    $headers = Headers::fromString($part->getBody());
                    $disposition = $headers->get('disposition');
                    if ($disposition) {
                        $mdnStatus = explode(';', $disposition->getFieldValue(), 2);
                        $mdnStatus = trim($mdnStatus[1]);
                        if ($mdnStatus == 'processed') {
                            $this->getLogger()->debug('Message has been successfully processed, verifying the MIC if present.');
                            // Compare the MIC of the received message
                            $receivedMic = $headers->get('Received-Content-MIC');
                            if ($receivedMic && $message->getCalculatedMic()) {
                                if ($message->getCalculatedMic() != $receivedMic) {
                                    throw new \Exception('MIC algorithm returned by partner is not the same as the algorithm requested');
                                }
                            }
                            $message->setMdnStatus(MessageInterface::MDN_STATUS_RECEIVED);
                            $this->getLogger()->debug('File Transferred successfully to the partner');
                        } else {
                            throw new \Exception('Partner failed to process file.');
                        }
                    }
                } catch (\Exception $e) {
                    $message->setMdnStatus(MessageInterface::MDN_STATUS_ERROR);
                    $this->getLogger()->error($e->getMessage(), [$messageId]);
                }
                return true;
            }
        }
        return false;
    }

    /**
     * @return Client
     */
    public function getHttpClient()
    {
        if (!$this->httpClient) {
            $this->httpClient = new Client($this->getOption('client_config'));
        }
        return $this->httpClient;
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