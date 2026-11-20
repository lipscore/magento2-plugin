<?php

namespace Lipscore\RatingsReviews\Test\Unit\Model;

use Lipscore\RatingsReviews\Model\Config;
use Lipscore\RatingsReviews\Model\Logger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\Manager;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @dataProvider lookbackProvider
     * @param mixed $value
     * @param int $expected
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('lookbackProvider')]
    public function testCronLookbackDays($value, $expected)
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturn($value);
        $config = new Config(
            $this->createStub(Logger::class),
            $scopeConfig,
            $this->createStub(Manager::class)
        );

        $this->assertSame($expected, $config->getEmailsCronLookbackDays(1));
    }

    /**
     * @return array
     */
    public static function lookbackProvider()
    {
        return [
            'not set' => [null, Config::EMAILS_CRON_LOOKBACK_DEFAULT_DAYS],
            'invalid' => ['abc', Config::EMAILS_CRON_LOOKBACK_DEFAULT_DAYS],
            'configured' => ['14', 14],
            'maximum' => ['30', 30],
            'above maximum, e.g. saved before the limit' => ['365', Config::EMAILS_CRON_LOOKBACK_MAX_DAYS],
        ];
    }
}
