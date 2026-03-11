<?php

declare(strict_types=1);

namespace Lipscore\RatingsReviews\Setup\Patch\Data;

use Lipscore\RatingsReviews\Model\ApiKeyRepository;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class SyncInitialApiKeys implements DataPatchInterface
{
    protected $apiKeyRepository;

    public function __construct(
        ApiKeyRepository $apiKeyRepository
    ) {
        $this->apiKeyRepository = $apiKeyRepository;
    }

    public function apply(): self
    {
        $this->apiKeyRepository->syncKeys();

        return $this;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
