<?php

namespace Lipscore\RatingsReviews\Test\Unit\Model\Invitation;

use Lipscore\RatingsReviews\Model\Config;
use Lipscore\RatingsReviews\Model\Invitation\StorePause;
use Magento\Framework\FlagManager;
use PHPUnit\Framework\TestCase;

class StorePauseTest extends TestCase
{
    /** @var array|null */
    private $flag;

    /** @var string */
    private $apiKey = 'key-1';

    /** @var StorePause */
    private $storePause;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $flagManager = $this->createStub(FlagManager::class);
        $flagManager->method('getFlagData')->willReturnCallback(function () {
            return $this->flag;
        });
        $flagManager->method('saveFlag')->willReturnCallback(function ($code, $data) {
            $this->flag = $data;
            return true;
        });
        $flagManager->method('deleteFlag')->willReturnCallback(function () {
            $this->flag = null;
            return true;
        });

        $config = $this->createStub(Config::class);
        $config->method('getApiUrl')->willReturn('https://api.lipscore.com');
        $config->method('getApiKey')->willReturnCallback(function () {
            return $this->apiKey;
        });
        $config->method('getApiSecret')->willReturn('secret');

        $this->storePause = new StorePause($flagManager, $config);
    }

    /**
     * @return void
     */
    public function testPausedForOneDay()
    {
        $now = 1700000000;
        $this->storePause->pause(1, 'HTTP 401', $now);

        $this->assertSame('HTTP 401', $this->storePause->getPause(1, $now + 3600)['message']);
        $this->assertNull($this->storePause->getPause(2, $now));
        $this->assertNull($this->storePause->getPause(1, $now + StorePause::PAUSE_SECONDS));
    }

    /**
     * @return void
     */
    public function testChangedCredentialsEndPause()
    {
        $this->storePause->pause(1, 'HTTP 401');
        $this->apiKey = 'key-2';

        $this->assertNull($this->storePause->getPause(1));
    }

    /**
     * @return void
     */
    public function testResume()
    {
        $this->storePause->pause(1, 'HTTP 403: Member site is blocked.');
        $this->storePause->resume(1);

        $this->assertNull($this->storePause->getPause(1));
        $this->assertNull($this->flag);
    }
}
