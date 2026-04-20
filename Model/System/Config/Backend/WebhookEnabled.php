<?php

declare(strict_types=1);

namespace Lipscore\RatingsReviews\Model\System\Config\Backend;

use Lipscore\RatingsReviews\Model\Api\Request;
use Lipscore\RatingsReviews\Model\ApiKeyRepository;
use Lipscore\RatingsReviews\Model\Logger\Logger;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

class WebhookEnabled extends Value
{
    protected $request;

    protected $logger;

    protected $apiKeyRepository;

    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        Request $request,
        Logger $logger,
        ApiKeyRepository $apiKeyRepository,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->logger = $logger;
        $this->request = $request;
        $this->apiKeyRepository = $apiKeyRepository;

        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    public function afterSave(): static
    {
        $newValue = (bool) $this->getValue();
        $oldValue = (bool) $this->getOldValue();

        if ($newValue === $oldValue) {
            return parent::afterSave();
        }

        try {
            $this->apiKeyRepository->syncKeys();
        } catch (\Throwable $e) {
            $this->logger->error('Lipscore webhook config sync failed', [
                'error' => $e->getMessage(),
            ]);
        }

        return parent::afterSave();
    }
}
