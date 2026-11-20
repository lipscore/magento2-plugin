<?php

namespace Lipscore\RatingsReviews\Test\Unit\Model\Api;

use Lipscore\RatingsReviews\Model\Api\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    /**
     * @return void
     */
    public function testDescribeLipscoreError()
    {
        $response = new Response(409, '{"status":"conflict","message":"Customer has this purchase already"}');

        $this->assertFalse($response->isSuccess());
        $this->assertSame('Customer has this purchase already', $response->getErrorMessage());
        $this->assertSame('HTTP 409: Customer has this purchase already', $response->describe());
    }

    /**
     * Proxies and load balancers return other formats, e.g. HTML.
     *
     * @return void
     */
    public function testDescribeNonJsonBody()
    {
        $response = new Response(502, "<html>\n<body><h1>502 Bad Gateway</h1></body>\n</html>");

        $this->assertNull($response->getData());
        $this->assertSame('', $response->getErrorMessage());
        $this->assertSame('HTTP 502: 502 Bad Gateway', $response->describe());
    }

    /**
     * @return void
     */
    public function testDescribeEmptyBody()
    {
        $this->assertSame('HTTP 401', (new Response(401, ''))->describe());
    }

    /**
     * @return void
     */
    public function testDescribeTransportError()
    {
        $response = new Response(0, '', new \RuntimeException('Operation timed out'));

        $this->assertFalse($response->isSuccess());
        $this->assertSame('No response from the Lipscore API: Operation timed out', $response->describe());
    }

    /**
     * @return void
     */
    public function testDescribeIsTruncated()
    {
        $response = new Response(500, str_repeat('x', 5000));

        $this->assertSame(1000, mb_strlen($response->describe()));
    }

    /**
     * @return void
     */
    public function testSuccess()
    {
        $response = new Response(201, '[{"invitation":{"internal_order_id":"100"}}]');

        $this->assertTrue($response->isSuccess());
        $this->assertSame('100', $response->getData()[0]['invitation']['internal_order_id']);
    }
}
