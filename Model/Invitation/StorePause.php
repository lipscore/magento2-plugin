<?php

namespace Lipscore\RatingsReviews\Model\Invitation;

use Lipscore\RatingsReviews\Model\Config;
use Magento\Framework\FlagManager;

/**
 * Stores whose invitations are not scheduled by the scheduled check after a Lipscore account error
 * (401, 403 such as blocked site or invitation limit reached).
 *
 * Retrying unchanged would fail again, so the store is skipped until its API settings change or PAUSE_SECONDS
 * pass, in case the account was fixed in Lipscore (e.g. IP allowlist, limit renewed).
 */
class StorePause
{
    public const FLAG_CODE = 'lipscore_invitations_paused_stores';

    public const PAUSE_SECONDS = 86400;

    /** @var FlagManager */
    private $flagManager;

    /** @var Config */
    private $config;

    /** @var array|null */
    private $pauses;

    /**
     * @param FlagManager $flagManager
     * @param Config $config
     */
    public function __construct(FlagManager $flagManager, Config $config)
    {
        $this->flagManager = $flagManager;
        $this->config = $config;
    }

    /**
     * Pause the store.
     *
     * @param int $storeId
     * @param string $message
     * @param int|null $now
     * @return void
     */
    public function pause($storeId, $message, $now = null)
    {
        $pauses = $this->load();
        $pauses[(int) $storeId] = [
            'until' => ($now ?? time()) + self::PAUSE_SECONDS,
            'credentials' => $this->getCredentialsHash($storeId),
            'message' => (string) $message,
        ];
        $this->save($pauses);
    }

    /**
     * Resume the store after a successful request.
     *
     * @param int $storeId
     * @return void
     */
    public function resume($storeId)
    {
        $pauses = $this->load();
        if (isset($pauses[(int) $storeId])) {
            unset($pauses[(int) $storeId]);
            $this->save($pauses);
        }
    }

    /**
     * Pause of the store, null when it is not paused (or the pause ended).
     *
     * @param int $storeId
     * @param int|null $now
     * @return array|null ['until' => timestamp, 'message' => string]
     */
    public function getPause($storeId, $now = null)
    {
        $pause = $this->load()[(int) $storeId] ?? null;
        if (!$pause
            || (int) $pause['until'] <= ($now ?? time())
            || $pause['credentials'] !== $this->getCredentialsHash($storeId)
        ) {
            return null;
        }

        return $pause;
    }

    /**
     * Hash of the store's API settings, a change ends the pause.
     *
     * @param int $storeId
     * @return string
     */
    private function getCredentialsHash($storeId)
    {
        return hash('sha256', implode("\n", [
            (string) $this->config->getApiUrl($storeId),
            (string) $this->config->getApiKey($storeId),
            (string) $this->config->getApiSecret($storeId),
        ]));
    }

    /**
     * Load pauses.
     *
     * @return array
     */
    private function load()
    {
        if ($this->pauses === null) {
            $pauses = $this->flagManager->getFlagData(self::FLAG_CODE);
            $this->pauses = is_array($pauses) ? $pauses : [];
        }

        return $this->pauses;
    }

    /**
     * Save pauses.
     *
     * @param array $pauses
     * @return void
     */
    private function save(array $pauses)
    {
        $this->pauses = $pauses;
        if ($pauses) {
            $this->flagManager->saveFlag(self::FLAG_CODE, $pauses);
        } else {
            $this->flagManager->deleteFlag(self::FLAG_CODE);
        }
    }
}
