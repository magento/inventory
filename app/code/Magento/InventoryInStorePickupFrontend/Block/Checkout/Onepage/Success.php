<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupFrontend\Block\Checkout\Onepage;

use Magento\Checkout\Block\Onepage\Success as SuccessBlock;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\View\Element\Template\Context;
use Magento\InventoryInStorePickupApi\Api\IsStorePickupOrderInterface;
use Magento\Sales\Model\Order\Config;

/**
 * Store pickup checkout success block.
 *
 * @api
 */
class Success extends SuccessBlock
{
    /**
     * @var IsStorePickupOrderInterface
     */
    private $isStorePickupOrder;

    /**
     * @param Context $context
     * @param Session $checkoutSession
     * @param Config $orderConfig
     * @param HttpContext $httpContext
     * @param IsStorePickupOrderInterface $isStorePickupOrder
     * @param array $data
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        Config $orderConfig,
        HttpContext $httpContext,
        IsStorePickupOrderInterface $isStorePickupOrder,
        array $data = []
    ) {
        parent::__construct($context, $checkoutSession, $orderConfig, $httpContext, $data);
        $this->isStorePickupOrder = $isStorePickupOrder;
    }

    /**
     * Get is order has pick in store delivery method.
     *
     * @return bool
     */
    public function isOrderStorePickup(): bool
    {
        $order = $this->_checkoutSession->getLastRealOrder();

        return $this->isStorePickupOrder->execute((int)$order->getId());
    }
}
