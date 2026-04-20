<?php

declare(strict_types=1);

namespace Lipscore\RatingsReviews\Block\Product;

use Lipscore\RatingsReviews\Model\Service\JsonLd\LipscoreReadService;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;

class JsonLd extends Template
{
    protected $registry;

    protected $readService;

    public function __construct(
        Template\Context $context,
        Registry $registry,
        LipscoreReadService $readService,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->readService = $readService;

        parent::__construct($context, $data);
    }

    /**
     * @return \Magento\Catalog\Model\Product|null
     */
    public function getProduct()
    {
        return $this->registry->registry('current_product');
    }

    public function getProductJsonLd(): ?array
    {
        $product = $this->getProduct();
        if (!$product) {
            return null;
        }

        return $this->readService->getJsonLdProduct($product);
    }

    public function getReviewsJsonLd(): ?array
    {
        $product = $this->getProduct();
        if (!$product) {
            return null;
        }

        return $this->readService->getJsonLdProductReviews($product);
    }
}
