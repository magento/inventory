<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupFrontend\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\InventoryInStorePickupShippingApi\Model\IsInStorePickupDeliveryCartInterface;

/**
 * Provide "selectedPickupLocationCode" in checkout config.
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class SelectedPickupLocationCodeProvider implements ConfigProviderInterface
{
    /**
     * @param IsInStorePickupDeliveryCartInterface $isInStorePickupDeliveryCart
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        private readonly IsInStorePickupDeliveryCartInterface $isInStorePickupDeliveryCart,
        private readonly CheckoutSession $checkoutSession
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getConfig()
    {
        $config = [];
        if ($this->isInStorePickupDeliveryCart->execute($this->checkoutSession->getQuote())) {
            $pickupLocationCode = $this->checkoutSession->getQuote()
                ->getShippingAddress()
                ?->getExtensionAttributes()
                ?->getPickupLocationCode();

            if ($pickupLocationCode) {
                $config['selectedPickupLocationCode'] = $pickupLocationCode;
            }
        }
        return $config;
    }
}
