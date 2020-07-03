<?php

namespace AS2;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * TODO: AS2-Version: 1.1 multiple attachments.
 */
class Management implements LoggerAwareInterface
{
    const AS2_VERSION = '1.2';
    const EDIINT_FEATURES = 'CEM'; // multiple-attachments,
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
        // Per RFC5402 compression is always before encryption but can be before or
        // after signing of message but only in one place
        'compress_before_sign' => false,

        /* @see \GuzzleHttp\Client */
        'client_config' => [],
    ];

    /**
     * Management constructor.
     *
     * @param  array  $options
     */
    public function __construct($options = [])
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * @param  string  $filePath
     * @param  string  $contentType
     * @param  string  $encoding
     *
     * @return MimePart
     *
     * @noinspection PhpUnused
     */
    public function buildMessageFromFile(
        MessageInterface $message,
        $filePath,
        $contentType = null,
        $encoding = 'binary'
    ) {
        if (! $contentType) {
            $contentType = $message->getReceiver()->getContentType();
        }
        $payload = new MimePart(
            [
                'Content-Type' => $contentType ?: 'text/plain',
                'Content-Disposition' => 'attachment; filename="'.basename($filePath).'"',
                'Content-Transfer-Encoding' => $encoding,
            ], file_get_contents($filePath)
        );

        return $this->buildMessage($message, $payload);
    }

    /**
     * Build the AS2 mime message to be sent to the partner.
     * Encrypts, signs and compresses the message based on the partner profile.
     * Returns the message final message content.
     *
     * @param  MimePart|string  $payload
     *
     * @return MimePart
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
            'Subject' => $receiver->getSubject() ?: 'AS2 Message',
            'Date' => date('r'),
            // 'Recipient-Address' => $receiver->getTargetUrl(),
            'Ediint-Features' => self::EDIINT_FEATURES,
        ];

        if (! ($payload instanceof MimePart)) {
            $payload = MimePart::fromString($payload);
        }

        $encoding = $receiver->getContentTransferEncoding();
        $micContent = Utils::canonicalize($payload);

        $compressBeforeSign = (bool) $this->getOption('compress_before_sign');

        // Compress the message before sign if requested in the profile
        if ($compressBeforeSign && $receiver->getCompressionType()) {
            $this->getLogger()->debug('Compressing outbound message before signing...');
            $payload = CryptoHelper::compress($payload, $encoding);
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

            $message->setMic(CryptoHelper::calculateMIC($micContent, $signAlgo));

            $this->getLogger()->debug(
                'Calculate MIC',
                [
                    'mic' => $message->getMic(),
                ]
            );

            $payload = CryptoHelper::sign(
                $payload,
                $sender->getCertificate(),
                [$sender->getPrivateKey(), $sender->getPrivateKeyPassPhrase()],
                [],
                $signAlgo
            );

            $message->setSigned();
        }

        // Compress the message after sign if requested in the profile
        if (! $compressBeforeSign && $receiver->getCompressionType()) {
            $this->getLogger()->debug('Compressing outbound message after signing...');
            $payload = CryptoHelper::compress($payload, $encoding);
            $message->setCompressed();
        }

        // Encrypt the message if requested in the profile
        if ($cipher = $receiver->getEncryptionAlgorithm()) {
            $this->getLogger()->debug('Encrypting the message using partner public key');
            $payload = CryptoHelper::encrypt($payload, $receiver->getCertificate(), $cipher);

            $message->setEncrypted();
        }

        //  If MDN is to be requested from the partner, set the appropriate headers
        if ($mdnMode = $receiver->getMdnMode()) {
            // TODO:
            if ($sender->getEmail()) {
                $as2headers['Disposition-Notification-To'] = $sender->getEmail();
            } else {
                $as2headers['Disposition-Notification-To'] = $sender->getTargetUrl();
            }

            if ($mdnOptions = $receiver->getMdnOptions()) {
                $as2headers['Disposition-Notification-Options'] = $mdnOptions;
            }

            // PARTNER IS ASYNC MDN
            if ($mdnMode === PartnerInterface::MDN_MODE_ASYNC) {
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

        $as2Message = new MimePart($as2headers, $payload->getBody());

        $message->setHeaders($as2Message->getHeaderLines());

        $this->getLogger()->debug('AS2 message has been built successfully');

        return $as2Message;
    }

    /**
     * Function decompresses, decrypts and verifies the received AS2 message
     * Takes an AS2 message as input and returns the actual payload ex. X12 message.
     *
     * @return MimePart
     */
    public function processMessage(MessageInterface $message, MimePart $payload)
    {
        $this->getLogger()->info(
            'Begin Processing of received AS2 message',
            [
                'message_id' => $message->getMessageId(),
            ]
        );

        // Force encode binary data to base64, `openssl_pkcs7_` doesn't work with binary data
        $body = $payload->getBody();
        $body = Utils::normalizeBase64($body);
        $body = Utils::encodeBase64($body);
        $payload->setBody($body);

        // Check if message from this partner are expected to be encrypted
        if (! $payload->isEncrypted() && $message->getSender()->getEncryptionAlgorithm()) {
            throw new \RuntimeException(
                sprintf(
                    'Incoming message from AS2 partner `%s` are defined to be encrypted',
                    $message->getSender()->getAs2Id()
                )
            );
        }

        $isDecompressed = false;
        $micContent = null;
        $micAlg = null;

        // Check if payload is encrypted and if so decrypt it
        if ($payload->isEncrypted()) {
            $this->getLogger()->debug('Inbound AS2 message is encrypted.');
            $payload = CryptoHelper::decrypt(
                $payload,
                $message->getReceiver()->getCertificate(),
                [
                    $message->getReceiver()->getPrivateKey(),
                    $message->getReceiver()->getPrivateKeyPassPhrase(),
                ]
            );

            $this->getLogger()->debug('The inbound AS2 message data has been decrypted.');
            $message->setEncrypted();
        }

        // Check for compression before signature check
        if ($payload->isCompressed()) {
            $this->getLogger()->debug('Decompressing received message before checking signature...');
            $payload = CryptoHelper::decompress($payload);
            $isDecompressed = true;
            $message->setCompressed();
        }

        // Check if message from this partner are expected to be signed
        if (! $payload->isSigned() && $message->getSender()->getSignatureAlgorithm()) {
            throw new \RuntimeException(
                sprintf(
                    'Incoming message from AS2 partner `%s` are defined to be signed.',
                    $message->getSender()->getAs2Id()
                )
            );
        }

        // Check if message is signed and if so verify it
        if ($payload->isSigned()) {
            $this->getLogger()->debug('Message is signed, Verifying it using public key.');

            $message->setSigned();

            // Get the partners public and ca certificates
            // TODO: refactory
            $cert = $message->getSender()->getCertificate();

            if (empty($cert)) {
                throw new \RuntimeException('Partner has no signature verification key defined');
            }

            // Verify message using raw payload received from partner
            if (! CryptoHelper::verify($payload, $cert)) {
                throw new \RuntimeException('Signature Verification Failed');
            }

            $this->getLogger()->debug('Digital signature of inbound AS2 message has been verified successful.');
            $this->getLogger()->debug(
                sprintf(
                    'Found %s payload attachments in the inbound AS2 message.',
                    $payload->getCountParts() - 1
                )
            );

            /*
             * Calculate the MIC after signing or encryption of the message but prior to
             * doing any decompression but include headers for unsigned messages
             * (see RFC4130 section 7.3.1 for details)
             */
            $micAlg = $payload->getParsedHeader('Disposition-Notification-Options', 2, 0);
            if (! $micAlg) {
                $micAlg = $payload->getParsedHeader('Content-Type', 0, 'micalg');
            }

            foreach ($payload->getParts() as $part) {
                if (! $part->isPkc7Signature()) {
                    $payload = $part;
                }
            }

            $micContent = $payload;
        }

        // Check if the message has been compressed and if so decompress it
        if ($payload->isCompressed()) {
            // Per RFC5402 compression is always before encryption but can be before or
            // after signing of message but only in one place
            if ($isDecompressed) {
                throw new \RuntimeException('Message has already been decompressed. Per RFC5402 it cannot occur twice.');
            }
            $this->getLogger()->debug('Decompressing received message after decryption...');
            $payload = CryptoHelper::decompress($payload);
            $message->setCompressed();
        }

        // Saving the message mic for sending it in the MDN
        if ($micContent !== null) {
            // Saving the message mic for sending it in the MDN
            $message->setMic(CryptoHelper::calculateMIC($micContent, $micAlg));
        }

        return $payload;
    }

    /**
     * Sends the AS2 message to the partner.
     * Takes the message as argument and posts the as2 message to the partner.
     *
     * @param  MimePart|string  $payload
     *
     * @return ResponseInterface|false
     * @noinspection PhpDocMissingThrowsInspection
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
                //  'cert' => '' // TODO: partner https cert ?
            ];

            if ($partner->getAuthMethod()) {
                $options['auth'] = [$partner->getAuthUser(), $partner->getAuthPassword(), $partner->getAuthMethod()];
            }

            /** @noinspection PhpUnhandledExceptionInspection */
            $response = $this->getHttpClient()->request('POST', $partner->getTargetUrl(), $options);
            if ($response->getStatusCode() !== 200) {
                throw new \RuntimeException('Message send failed with error');
            }

            $this->getLogger()->debug('AS2 message successfully sent to partner');

            // Process the MDN based on the partner profile settings
            if ($mdnMode = $partner->getMdnMode()) {
                if ($mdnMode === PartnerInterface::MDN_MODE_ASYNC) {
                    $this->getLogger()->debug('Requested ASYNC MDN from partner, waiting for it');
                    $message->setStatus(MessageInterface::STATUS_PENDING);
                } else {
                    // In case of Synchronous MDN the response content will be the MDN. So process it.
                    // Get the response headers, convert key to lower case for normalization
                    $this->getLogger()->debug('Synchronous MDN received from partner');

                    $body = $response->getBody()->getContents();

                    $payload = new MimePart($response->getHeaders(), $body);
                    $response->getBody()->rewind();

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
            $message->setStatusMsg($e->getMessage());
        }

        return false;
    }

    /**
     * Process the received MDN and check status of sent message.
     * Takes the raw mdn as input, verifies the signature if present and the extracts the status of the original message.
     *
     * @param  MimePart|string  $payload
     *
     * @return bool
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
            throw new \RuntimeException('MDN report not found in the response ['.$payload.']');
        }

        $messageId = $message->getMessageId();

        // Save the MDN to the store
        $message->setMdnStatus(MessageInterface::MDN_STATUS_PENDING);
        $message->setMdnPayload($payload);

        foreach ($payload->getParts() as $part) {
            if ($part->getParsedHeader('content-type', 0, 0) === 'message/disposition-notification') {
                $this->getLogger()->debug('Found MDN report for message', [$messageId]);
                try {
                    $bodyPayload = MimePart::fromString($part->getBody());
                    if ($bodyPayload->hasHeader('disposition')) {
                        $mdnStatus = $bodyPayload->getParsedHeader('Disposition', 0, 1);
                        if ($mdnStatus === 'processed') {
                            $this->getLogger()->debug(
                                'Message has been successfully processed, verifying the MIC if present.'
                            );

                            // Compare the MIC of the received message
                            $receivedMic = $bodyPayload->getHeaderLine('Received-Content-MIC');
                            if ($receivedMic &&
                                $message->getMic() &&
                                Utils::normalizeMic($message->getMic()) !== Utils::normalizeMic($receivedMic)
                            ) {
                                throw new \RuntimeException(sprintf('The Message Integrity Code (MIC) does not match the sent AS2 message (required: %s, returned: %s)',
                                    $message->getMic(), $receivedMic));
                            }

                            $message->setMdnStatus(MessageInterface::MDN_STATUS_RECEIVED);
                            $this->getLogger()->debug('File Transferred successfully to the partner');
                        } else {
                            throw new \RuntimeException('Partner failed to process file. '.$mdnStatus);
                        }
                    }
                } catch (\Exception $e) {
                    $message->setMdnStatus(MessageInterface::MDN_STATUS_ERROR);
                    $message->setStatusMsg($e->getMessage());
                    $this->getLogger()->error(
                        $e->getMessage(),
                        [
                            'message_id' => $messageId,
                        ]
                    );
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Build the AS2 MDN to be sent to the partner.
     *
     * @param  string  $confirmationText
     * @param  string  $errorMessage  // TODO: detailedStatus
     *
     * @return MimePart
     */
    public function buildMdn(MessageInterface $message, $confirmationText = null, $errorMessage = null)
    {
        // Parse Message Headers
        $messageHeaders = MimePart::fromString(trim($message->getHeaders())."\r\n\r\n");

        // In case no MDN is requested exit from process
        if (! $messageHeaders->hasHeader('disposition-notification-to')) {
            $this->getLogger()->debug('MDN not requested by partner, closing request.');

            return null;
        }

        $sender = $message->getSender();
        if (! $sender) {
            throw new \RuntimeException('Message Sender is required.');
        }

        $receiver = $message->getReceiver();
        if (! $receiver) {
            throw new \RuntimeException('Message Receiver is required.');
        }

        // Set the confirmation text message here
        if (empty($confirmationText)) {
            if ($receiver->getMdnSubject()) {
                $confirmationText = $receiver->getMdnSubject();
            }
            if ($sender->getMdnSubject()) {
                $confirmationText = $sender->getMdnSubject();
            }
            /* @noinspection NotOptimalIfConditionsInspection */
            if (empty($confirmationText)) {
                $confirmationText = 'Your message was successfully received and processed.';
            }
        }

        $messageId = $message->getMessageId();
        $this->getLogger()->debug(sprintf('Generating outbound MDN, setting message id to `%s`', $messageId));

        $boundary = '=_'.sha1(uniqid('', true));
        $reportHeaders = [
            'Content-Type' => 'multipart/report; report-type=disposition-notification; boundary="----'.$boundary.'"',
        ];

        $isSigned = $messageHeaders->hasHeader('disposition-notification-options');

        // Build the MDN report

        // Set up the message headers
        $mdnHeaders = [
            'Message-ID' => '<'.Utils::generateMessageID($receiver).'>',
            'Date' => date('r'),
            'AS2-From' => $receiver->getAs2Id(),
            'AS2-To' => $sender->getAs2Id(),
            'AS2-Version' => self::AS2_VERSION,
            'User-Agent' => self::USER_AGENT,
            'Ediint-Features' => self::EDIINT_FEATURES,
            // 'Connection' => 'close',
        ];

        // TODO: refactory
        if (! $isSigned) {
            $reportHeaders['Mime-Version'] = '1.0';
            /* @noinspection AdditionOperationOnArraysInspection */
            $reportHeaders += $mdnHeaders;
        }

        $report = new MimePart($reportHeaders);

        // Build the text message with confirmation text and add to report
        $report->addPart(
            new MimePart(
                [
                    'Content-Type' => 'text/plain',
                    'Content-Transfer-Encoding' => '7bit', // TODO: check 8bit
                ], $confirmationText."\n"
            )
        );

        // Build the MDN message and add to report
        $mdnData = [
            'Reporting-UA' => self::USER_AGENT,
            'Original-Recipient' => 'rfc822; '.$receiver->getAs2Id(),
            'Final-Recipient' => 'rfc822; '.$receiver->getAs2Id(),
            'Original-Message-ID' => '<'.$message->getMessageId().'>',
            'Disposition' => 'automatic-action/MDN-sent-automatically; processed'.($errorMessage ? '/error: '.$errorMessage : ''),
        ];

        if ($mic = $message->getMic()) {
            $mdnData['Received-Content-MIC'] = $mic;
        }

        $report->addPart(
            new MimePart(
                [
                    'Content-Type' => 'message/disposition-notification',
                    'Content-Transfer-Encoding' => '7bit',
                ], Utils::normalizeHeaders($mdnData)
            )
        );

        // If signed MDN is requested by partner then sign the MDN and attach to report
        if ($isSigned) {
            // TODO: check
            $notificationOptions = $messageHeaders->getHeader('disposition-notification-options');
            $notificationOptions = Utils::parseHeader($notificationOptions);

            $micAlg = isset($notificationOptions[2]) ? reset($notificationOptions[2]) : null;

            $this->getLogger()->debug('Outbound MDN has been signed.');

            $report = CryptoHelper::sign(
                $report,
                $receiver->getCertificate(),
                [
                    $receiver->getPrivateKey(),
                    $receiver->getPrivateKeyPassPhrase(),
                ],
                $mdnHeaders,
                $micAlg
            );
        }

        $this->getLogger()->debug(sprintf('Outbound MDN created for AS2 message `%s`.', $messageId));

        // Is Async mdn is requested mark MDN as pending and return None
        if ($messageHeaders->hasHeader('receipt-delivery-option')) {
            $message->setMdnMode(PartnerInterface::MDN_MODE_ASYNC);
            $message->setMdnStatus(MessageInterface::MDN_STATUS_PENDING);
            $this->getLogger()->debug('Asynchronous MDN requested, setting status to pending');
        } else {
            // Else mark MDN as sent and return the MDN message
            $message->setMdnMode(PartnerInterface::MDN_MODE_SYNC);
            $message->setMdnStatus(MessageInterface::MDN_STATUS_SENT);
        }

        return $report;
    }

    /**
     * Sends the AS2 MDN to the partner.
     *
     * @return bool|mixed|ResponseInterface
     */
    public function sendMdn(MessageInterface $message)
    {
        // TODO: cron, queue, new thread
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
            if ($response->getStatusCode() !== 200) {
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
     * @param  string  $name
     * @param  string  $default
     *
     * @return mixed|null
     */
    public function getOption($name, $default = null)
    {
        return isset($this->options[$name]) ? $this->options[$name] : $default;
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
     * @return $this
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }
}
