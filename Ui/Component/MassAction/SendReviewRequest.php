<?php

namespace Lipscore\RatingsReviews\Ui\Component\MassAction;

use Lipscore\RatingsReviews\Controller\Adminhtml\Order\MassSendReviewRequest;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Action;

/**
 * Hidden for admins without the ACL resource.
 */
class SendReviewRequest extends Action
{
    /** @var AuthorizationInterface */
    protected $authorization;

    /**
     * @param ContextInterface $context
     * @param AuthorizationInterface $authorization
     * @param array $components
     * @param array $data
     * @param array|\JsonSerializable|null $actions
     */
    public function __construct(
        ContextInterface $context,
        AuthorizationInterface $authorization,
        array $components = [],
        array $data = [],
        $actions = null
    ) {
        $this->authorization = $authorization;
        parent::__construct($context, $components, $data, $actions);
    }

    /**
     * @inheritDoc
     */
    public function prepare()
    {
        parent::prepare();

        if (!$this->authorization->isAllowed(MassSendReviewRequest::ADMIN_RESOURCE)) {
            $config = $this->getConfiguration();
            $config['actionDisable'] = true;
            $this->setData('config', $config);
        }
    }
}
