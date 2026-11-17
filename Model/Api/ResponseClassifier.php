<?php

namespace Lipscore\RatingsReviews\Model\Api;

/**
 * Tells how to handle a Lipscore invitation API response, based on the error list provided by Lipscore.
 *
 * SCHEDULED, ALREADY_SCHEDULED and REJECTED concern the sent invitations. TEMPORARY and ACCOUNT concern the
 * request as a whole: no invitation of the store can be scheduled now, so the store is not processed further.
 */
class ResponseClassifier
{
    /** Invitation created. */
    public const SCHEDULED = 'scheduled';

    /** 409: an invitation already exists or is blocked by the repeat rules. Never retried. */
    public const ALREADY_SCHEDULED = 'already_scheduled';

    /** 400, 422 and other client errors: invalid invitation data. Not retried until the data is corrected. */
    public const REJECTED = 'rejected';

    /** Timeout, connection failure, 5xx, 503 throttling, 429 or a temporarily disabled feature. Retried later. */
    public const TEMPORARY = 'temporary';

    /**
     * 401 and 403 account errors: invalid credentials, IP not allowed, blocked or cancelled site, master account,
     * invitation limit reached. Not retried until the configuration or the account changes.
     */
    public const ACCOUNT = 'account';

    /** 403 messages meaning the feature is temporarily unavailable ("Feature is not available ... Retry later."). */
    private const TEMPORARY_FORBIDDEN_MARKERS = ['retry later', 'not available at the moment'];

    /**
     * Classify the response.
     *
     * @param Response $response
     * @return string One of the class constants
     */
    public function classify(Response $response)
    {
        $code = $response->getStatusCode();

        if ($response->isSuccess()) {
            return self::SCHEDULED;
        }
        if ($code === 409) {
            return self::ALREADY_SCHEDULED;
        }
        if ($code === 401) {
            return self::ACCOUNT;
        }
        if ($code === 403) {
            return $this->isTemporaryForbidden($response) ? self::TEMPORARY : self::ACCOUNT;
        }
        if ($code === 408 || $code === 429) {
            return self::TEMPORARY;
        }
        if ($code >= 400 && $code < 500) {
            return self::REJECTED;
        }

        // 0 (no response), 5xx and unexpected codes.
        return self::TEMPORARY;
    }

    /**
     * Whether the result concerns the whole request (and store) rather than the individual invitations.
     *
     * @param string $result
     * @return bool
     */
    public function isRequestLevel($result)
    {
        return $result === self::TEMPORARY || $result === self::ACCOUNT;
    }

    /**
     * Whether a 403 is temporary.
     *
     * Only the documented "Retry later" message is; other or unreadable 403 bodies are account errors,
     * so the store is not hammered while blocked.
     *
     * @param Response $response
     * @return bool
     */
    private function isTemporaryForbidden(Response $response)
    {
        $message = strtolower($response->getErrorMessage());
        foreach (self::TEMPORARY_FORBIDDEN_MARKERS as $marker) {
            if ($message !== '' && strpos($message, $marker) !== false) {
                return true;
            }
        }

        return false;
    }
}
