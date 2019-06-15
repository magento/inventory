<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupAdminUi\Plugin\Sales\Order\View\ShippingAddress;

use Magento\InventoryInStorePickupApi\Api\IsStorePickupOrderInterface;
use Magento\Sales\Block\Adminhtml\Order\View\Info;
use Magento\Sales\Model\Order\Address;

/**
 * Hide edit page link for store pickup order shipping address
 */
class HideEditLink
{
    private const TYPE_SHIPPING = 'shipping';

    /**
     * @var IsStorePickupOrderInterface
     */
    private $isStorePickupOrder;

    /**
     * @param IsStorePickupOrderInterface $isStorePickupOrder
     */
    public function __construct(
        IsStorePickupOrderInterface $isStorePickupOrder
    ) {
        $this->isStorePickupOrder = $isStorePickupOrder;
    }

    /**
     * @param Info $subject
     * @param string $result
     * @param Address $address
     *
     * @return string
     */
    public function afterGetAddressEditLink(Info $subject, string $result, Address $address): string
    {
        if ($address->getAddressType() == self::TYPE_SHIPPING) {
            if ($this->isStorePickupOrder->execute((int)$address->getOrder()->getEntityId())) {
                return '';
            }
        }

        return $result;
    }
}
