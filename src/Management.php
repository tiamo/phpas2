<?php

namespace AS2;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Management
{
    const AS2_VERSION = '1.2';
    const USER_AGENT = 'PHPAS2';

    /** @var MessageInterface */
    protected $messageClassName;

    /**
     * @var PartnerInterface
     */
    protected $partnerClassName;

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
     * Build the AS2 mime message to be sent to partner.
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
            throw new \Exception('Sender required.');
        }
        $partner = $message->getReceiver();
        if (!$partner) {
            throw new \Exception('Receiver required.');
        }

        $this->getLogger()->debug('Build the AS2 message and header to send to the partner');

        $as2headers = new Headers();
        $as2headers->addHeaders([
            'AS2-Version' => self::AS2_VERSION,
            'MIME-Version' => '1.0',
            'Message-ID' => $message->getUid(),
            'AS2-From' => $sender->getUid(),
            'AS2-To' => $partner->getUid(),
            'Subject' => $partner->getSubject(),
            'Date' => date('Y-m-d H:i:s'),
            'recipient-address' => $partner->getTargetUrl(),
            'ediint-features' => 'CEM',
            'user-agent' => self::USER_AGENT,
        ]);

        if (!($payload instanceof MimePart)) {
            $payload = new MimePart((string)$payload);
        }

        // Compress the message if requested in the profile
        if ($partner->getCompressionType()) {
            $this->getLogger()->debug('Compress the message');
            $payload = CryptoHelper::compress($payload);
            $message->isCompressed(true);
        }

        // Sign the message if requested in the profile
        if ($partner->getSignatureAlgorithm()) {
            $this->getLogger()->debug('Signing the message using sender key');
            $payload = CryptoHelper::sign($payload,
                $sender->getPublicKey(),
                [$sender->getPrivateKey(), $sender->getPrivateKeyPassPhrase()]
            );
            // If MIC content is set, i.e. message has been signed then calculate the MIC
            $mdnOptions = $this->parseMdnOptions($partner->getMdnOptions());
            $micAlgo = null;
            if (isset($mdnOptions['signed-receipt-micalg'])) {
                $algo = explode(',', $mdnOptions['signed-receipt-micalg']);
                $micAlgo = trim(array_pop($algo));
            }
            $this->getLogger()->debug('Calculate MIC', ['algo' => $micAlgo]);
            $message->setMic(CryptoHelper::calculateMIC($payload, $micAlgo));
            $message->isSigned(true);
        }

        // Encrypt the message if requested in the profile
        if ($partner->getEncryptionAlgorithm()) {
            $this->getLogger()->debug('Encrypting the message using partner public key');
            // TODO: set cipher based by algorithm, default is sha256
            $payload = CryptoHelper::encrypt($message, $partner->getPublicKey());
            $message->isEncrypted(true);
        }

        //  If MDN is to be requested from the partner, set the appropriate headers
        if ($partner->getMdnMode()) {
            $as2headers->addHeaderLine('disposition-notification-to', $this->getOption('mdn_notification_to'));
            $as2headers->addHeaderLine('disposition-notification-options', $partner->getMdnOptions());
            // PARTNER IS ASYNC MDN
            if ($partner->getMdnMode() == PartnerInterface::MDN_MODE_ASYNC) {
                $message->setMdnMode(PartnerInterface::MDN_MODE_ASYNC);
                $as2headers->addHeaderLine('receipt-delivery-option', $this->getOption('mdn_url'));
            } else {
                $message->setMdnMode(PartnerInterface::MDN_MODE_SYNC);
            }
        }

        // Extract the As2 headers as a string and save it to the message object
        foreach ($payload->getHeaders() as $name => $value) {
            $as2headers->removeHeader($name);
            $as2headers->addHeaderLine($name, $value);
        }

        $message->setHeaders($as2headers->toString());
        $message->setBody($payload->getBody());

        $this->getLogger()->debug('AS2 message has been built successfully, sending it to the partner');

        return $message;
    }

    /**
     * Sends the AS2 message to the partner.
     * Takes the message and payload as arguments and posts the as2 message to the partner.
     *
     * @param MessageInterface $message
     * @return MessageInterface
     */
    public function sendMessage(MessageInterface $message)
    {
        $partner = $message->getReceiver();

        try {
            // Send the AS2 message to the partner
            $options = [
                'body' => $message->getBody(),
                'headers' => Headers::fromString($message->getHeaders())->toArray(),
//                'cert' => '' // partner https cert ?
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
                    $this->saveMdn($message, $response);
                }
            } else {
                $this->getLogger()->debug('No MDN needed, File Transferred successfully to the partner');
                $message->setStatus(MessageInterface::STATUS_SUCCESS);
            }

        } catch (\Exception $e) {
            $this->getLogger()->critical($e->getMessage());
            $message->setStatus(MessageInterface::STATUS_ERROR);
        }

        return $message;
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
    public function prepareMessage(MessageInterface $message, $payload)
    {
        $messageId = $message->getUid();

        $this->getLogger()->debug('Begin Processing of received AS2 message', [$messageId]);

        try {

            if (!($payload instanceof MimePart)) {
                $payload = MimePart::fromString($payload);
            }

            $partner = $message->getReceiver();

            // Check if message from this partner are expected to be encrypted
            if ($partner->getEncryptionAlgorithm() && !$payload->isEncrypted()) {
                throw new \Exception('Incoming messages from AS2 partner are defined to be encrypted');
            }

            // Check if payload is encrypted and if so decrypt it
            if ($payload->isEncrypted()) {
                $message->isEncrypted(true);
                $this->getLogger()->debug('Decrypting the payload using private key');
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
                throw new \Exception('Incoming messages from AS2 partner are defined to be signed');
            }

            // Check if message is signed and if so verify it
            if ($payload->isSigned()) {
                $message->isSigned(true);
                $this->getLogger()->debug('Verifying the signed payload');
                $micAlg = $payload->getContentType()->getParameter('micalg');

                foreach ($payload->getParts() as $part) {
                    if (!$part->isPkc7Signature()) {
                        $payload = $part;
                    }
                }
                // Verify message using raw payload received from partner
                if (!CryptoHelper::verify($payload)) {
                    throw new \Exception('Signature Verification Failed');
                }
                $message->setMic(CryptoHelper::calculateMIC($payload, $micAlg, true));
            }

            // Check if the message has been compressed and if so decompress it
            if ($payload->isCompressed()) {
                $message->isCompressed(true);
                $this->getLogger()->debug('Decompressing the payload');
                $payload = CryptoHelper::decompress($payload);
            }

            $message->setBody((string)$payload);

        } catch (\Exception $e) {
            $message->setStatus(MessageInterface::STATUS_ERROR);
            $this->getLogger()->error($e->getMessage(), [$messageId]);
        }

        return $message;
    }

    /**
     * @param MessageInterface $message
     * @param MimePart|string $payload
     * @throws \Exception
     */
    public function saveMdn(MessageInterface $message, $payload)
    {
        if (!($payload instanceof MimePart)) {
            $payload = MimePart::fromString($payload);
        }

        if (!$payload->isSigned() && !$payload->isReport()) {
            throw new \Exception('MDN report not found in the response');
        }

        if ($payload->isSigned()) {
            foreach ($payload->getParts() as $part) {
                if (!$part->isPkc7Signature()) {
                    $payload = $part;
                }
            }
            // Verify the signature using raw MDN content
            // TODO: implement
            if (!CryptoHelper::verify($payload)) {
                throw new \Exception('MDN Signature Verification Error');
            }
        }

        // Save the MDN to the store
        $message->setMdn($payload);

        // Process the MDN report to extract the AS2 message status
        if (!$payload->isReport()) {
            throw new \Exception('MDN report not found in the response');
        }

        foreach ($payload->getParts() as $part) {
            if ($part->getContentType()->getType() == 'message/disposition-notification') {
                $this->getLogger()->debug('Found MDN report for message');

                $disposition = $part->getHeader('content-disposition');

            }
        }


//        $messageId = $payload->getHeader('message-id')->toString();
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