<?php

namespace Lipscore\RatingsReviews\Test\Unit\Model\Api;

use Lipscore\RatingsReviews\Model\Api\Response;
use Lipscore\RatingsReviews\Model\Api\ResponseClassifier;
use PHPUnit\Framework\TestCase;

class ResponseClassifierTest extends TestCase
{
    /**
     * @dataProvider responseProvider
     * @param int $code
     * @param string $body
     * @param string $expected
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('responseProvider')]
    public function testClassify($code, $body, $expected)
    {
        $classifier = new ResponseClassifier();

        $this->assertSame($expected, $classifier->classify(new Response($code, $body)));
    }

    /**
     * Cases from the error list provided by Lipscore.
     *
     * @return array
     */
    public static function responseProvider()
    {
        $error = function ($message) {
            return json_encode(['status' => 'error', 'message' => $message]);
        };

        return [
            'created' => [201, '[]', ResponseClassifier::SCHEDULED],
            'ok' => [200, '', ResponseClassifier::SCHEDULED],
            'invalid json' => [400, $error('Invalid JSON in request body'), ResponseClassifier::REJECTED],
            'buyer' => [400, $error('Buyer cannot be found/created'), ResponseClassifier::REJECTED],
            'products blocked' => [422, $error('Products are blocked'), ResponseClassifier::REJECTED],
            'not found' => [404, '', ResponseClassifier::REJECTED],
            'credentials' => [401, '', ResponseClassifier::ACCOUNT],
            'ip' => [401, $error('Your IP address is not authorized'), ResponseClassifier::ACCOUNT],
            'feature disabled' => [
                403,
                $error('Feature is not available at the moment. Retry later.'),
                ResponseClassifier::TEMPORARY,
            ],
            'blocked site' => [403, $error('Member site is blocked.'), ResponseClassifier::ACCOUNT],
            'master account' => [
                403,
                $error('Not allowed to create invitations via Master account'),
                ResponseClassifier::ACCOUNT,
            ],
            'limit' => [403, $error('Period invitations limit has been reached'), ResponseClassifier::ACCOUNT],
            'unreadable 403' => [403, '<html>Forbidden</html>', ResponseClassifier::ACCOUNT],
            'repeat block' => [
                409,
                $error('Customer has this purchase already'),
                ResponseClassifier::ALREADY_SCHEDULED,
            ],
            'duplicate' => [409, $error('Purchase duplication detected'), ResponseClassifier::ALREADY_SCHEDULED],
            'request timeout' => [408, '', ResponseClassifier::TEMPORARY],
            'too many requests' => [429, '', ResponseClassifier::TEMPORARY],
            'server error' => [500, '<html>Oops</html>', ResponseClassifier::TEMPORARY],
            'throttled' => [503, '503 Service Unavailable', ResponseClassifier::TEMPORARY],
            'no response' => [0, '', ResponseClassifier::TEMPORARY],
        ];
    }

    /**
     * @return void
     */
    public function testRequestLevelResults()
    {
        $classifier = new ResponseClassifier();

        $this->assertTrue($classifier->isRequestLevel(ResponseClassifier::TEMPORARY));
        $this->assertTrue($classifier->isRequestLevel(ResponseClassifier::ACCOUNT));
        $this->assertFalse($classifier->isRequestLevel(ResponseClassifier::SCHEDULED));
        $this->assertFalse($classifier->isRequestLevel(ResponseClassifier::ALREADY_SCHEDULED));
        $this->assertFalse($classifier->isRequestLevel(ResponseClassifier::REJECTED));
    }
}
