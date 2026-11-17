<?php

namespace Lipscore\RatingsReviews\Model\Api;

/**
 * Result of a Lipscore API request: HTTP status and body, or the transport error when no response was received.
 */
class Response
{
    /** Message length kept for the delivery log. */
    private const MAX_MESSAGE_LENGTH = 1000;

    /** @var int 0 when no HTTP response was received */
    private $statusCode;

    /** @var string */
    private $body;

    /** @var \Throwable|null */
    private $transportError;

    /** @var array|null|false False until decoded */
    private $data = false;

    /**
     * @param int $statusCode
     * @param string $body
     * @param \Throwable|null $transportError
     */
    public function __construct($statusCode, $body = '', ?\Throwable $transportError = null)
    {
        $this->statusCode = (int) $statusCode;
        $this->body = (string) $body;
        $this->transportError = $transportError;
    }

    /**
     * HTTP status code, 0 when the request failed before a response was received.
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Raw response body.
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Timeout or connection failure, when no response was received.
     *
     * @return \Throwable|null
     */
    public function getTransportError()
    {
        return $this->transportError;
    }

    /**
     * Whether the request succeeded (2xx).
     *
     * @return bool
     */
    public function isSuccess()
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * Decoded JSON body; null when the body is empty or not JSON (e.g. errors from proxies or load balancers).
     *
     * @return array|null
     */
    public function getData()
    {
        if ($this->data === false) {
            $data = $this->body !== '' ? json_decode($this->body, true) : null;
            $this->data = is_array($data) ? $data : null;
        }

        return $this->data;
    }

    /**
     * Error message from the body, "message" field of the Lipscore error format, or an empty string.
     *
     * @return string
     */
    public function getErrorMessage()
    {
        $data = $this->getData();

        return isset($data['message']) && is_scalar($data['message']) ? trim((string) $data['message']) : '';
    }

    /**
     * Readable description for logs and the order view, e.g. "HTTP 409: Customer has this purchase already".
     *
     * @return string
     */
    public function describe()
    {
        if ($this->statusCode === 0) {
            $error = $this->transportError ? $this->transportError->getMessage() : '';
            return $this->truncate('No response from the Lipscore API' . ($error !== '' ? ': ' . $error : ''));
        }

        $message = $this->getErrorMessage();
        if ($message === '' && $this->getData() === null) {
            // Not the Lipscore error format, keep the start of the raw body for troubleshooting.
            $message = trim(preg_replace('/\s+/', ' ', strip_tags($this->body)));
        }

        return $this->truncate('HTTP ' . $this->statusCode . ($message !== '' ? ': ' . $message : ''));
    }

    /**
     * Shorten a message for storage.
     *
     * @param string $message
     * @return string
     */
    private function truncate($message)
    {
        return mb_strlen($message) > self::MAX_MESSAGE_LENGTH
            ? mb_substr($message, 0, self::MAX_MESSAGE_LENGTH - 3) . '...'
            : $message;
    }
}
