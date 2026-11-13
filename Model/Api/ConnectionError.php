<?php

namespace Lipscore\RatingsReviews\Model\Api;

/**
 * Detects timeouts and connection failures of the HTTP clients used by Request.
 */
class ConnectionError
{
    /** instanceof does not autoload, so clients that are not installed are fine here. */
    private const EXCEPTION_CLASSES = [
        \Laminas\Http\Client\Adapter\Exception\RuntimeException::class,
        \Zend\Http\Client\Adapter\Exception\RuntimeException::class,
        \GuzzleHttp\Exception\ConnectException::class,
    ];

    /**
     * Checks the exception and its previous exceptions.
     *
     * @param \Throwable $exception
     * @return bool
     */
    public function matches(\Throwable $exception)
    {
        for ($current = $exception; $current !== null; $current = $current->getPrevious()) {
            foreach (self::EXCEPTION_CLASSES as $class) {
                if ($current instanceof $class) {
                    return true;
                }
            }
        }

        return false;
    }
}
