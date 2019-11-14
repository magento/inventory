<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupQuote\Plugin\Quote;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryInStorePickupShippingApi\Model\IsInStorePickupDeliveryCartInterface;
use Magento\Quote\Api\BillingAddressManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;

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
     * @var IsInStorePickupDeliveryCartInterface
     */
    private $isInStorePickupDeliveryCart;

    /**
     * @param CartRepositoryInterface $cartRepository
     * @param IsInStorePickupDeliveryCartInterface $isInStorePickupDeliveryCart
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        IsInStorePickupDeliveryCartInterface $isInStorePickupDeliveryCart
    ) {
        $this->cartRepository = $cartRepository;
        $this->isInStorePickupDeliveryCart = $isInStorePickupDeliveryCart;
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

        if ($this->isInStorePickupDeliveryCart->execute($quote)) {
            $useForShipping = false;
        }

        return [$cartId, $address, $useForShipping];
    }
}
