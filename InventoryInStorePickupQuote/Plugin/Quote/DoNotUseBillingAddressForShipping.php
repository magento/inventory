<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Plugin\Quote;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;
use Magento\Quote\Api\BillingAddressManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;

/**
 * Disallow use Billing Address for shipping if Quote Delivery Method is In-Store Pickup.
 */
class DoNotUseBillingAddressForShipping
{
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(CartRepositoryInterface $cartRepository)
    {
        $this->cartRepository = $cartRepository;
    }

    /**
     * Disallow use Billing Address for shipping if Quote Delivery Method is In-Store Pickup.
     *
     * @param BillingAddressManagementInterface $subject
     * @param int $cartId
     * @param AddressInterface $address
     * @param bool $useForShipping
     *
     * @return array
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeAssign(
        BillingAddressManagementInterface $subject,
        int $cartId,
        AddressInterface $address,
        bool $useForShipping = false
    ): array {
        $quote = $this->cartRepository->getActive($cartId);

        if (!$quote->getExtensionAttributes() || !$quote->getExtensionAttributes()->getShippingAssignments()) {
            return [$cartId, $address, $useForShipping];
        }

        $shippingAssignments = $quote->getExtensionAttributes()->getShippingAssignments();
        /** @var ShippingAssignmentInterface $shippingAssignment */
        $shippingAssignment = current($shippingAssignments);
        $shipping = $shippingAssignment->getShipping();

        if ($shipping->getMethod() === InStorePickup::DELIVERY_METHOD) {
            $useForShipping = false;
        }

        return [$cartId, $address, $useForShipping];
    }
}
