<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupFrontend\Block\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\InventoryInStorePickupShippingApi\Model\IsInStorePickupDeliveryAvailableForCartInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Add store pickup information on checkout page.
 */
class LayoutProcessor implements LayoutProcessorInterface
{
    private const SEARCH_RADIUS = 'carriers/instore/search_radius';

    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var \Magento\InventoryInStorePickupShippingApi\Model\IsInStorePickupDeliveryAvailableForCartInterface
     */
    private $inStorePickupDeliveryAvailableForCart;

    /**
     * @param ArrayManager $arrayManager
     * @param ScopeConfigInterface $config
     * @param Session $session
     * @param IsInStorePickupDeliveryAvailableForCartInterface $inStorePickupDeliveryAvailableForCart
     */
    public function __construct(
        ArrayManager $arrayManager,
        ScopeConfigInterface $config,
        Session $session,
        IsInStorePickupDeliveryAvailableForCartInterface $inStorePickupDeliveryAvailableForCart
    ) {
        $this->arrayManager = $arrayManager;
        $this->config = $config;
        $this->checkoutSession = $session;
        $this->inStorePickupDeliveryAvailableForCart = $inStorePickupDeliveryAvailableForCart;
    }

    /**
     * @inheritDoc
     */
    public function process($jsLayout)
    {
        if ($this->inStorePickupDeliveryAvailableForCart->execute((int)$this->checkoutSession->getQuoteId())) {
            return $this->addStorePickupComponents($jsLayout);
        }

        return $this->removeStorePickup($jsLayout);
    }

    /**
     * Remove store pickup ui components from layout.
     *
     * @param array $jsLayout
     *
     * @return array
     */
    private function removeStorePickup(array $jsLayout): array
    {
        $storePickupPath = $this->arrayManager->findPath('store-pickup', $jsLayout);
        $jsLayout = $this->arrayManager->remove($storePickupPath, $jsLayout);

        return $jsLayout;
    }

    /**
     * Add ui store pickup components to layout.
     *
     * @param array $jsLayout
     *
     * @return array
     */
    private function addStorePickupComponents(array $jsLayout): array
    {
        return $this->arrayManager->merge(
            $this->arrayManager->findPath('store-pickup', $jsLayout),
            $jsLayout,
            [
                'config' => [
                    'nearbySearchRadius' => $this->getSearchRadius(),
                ],
            ]
        );
    }

    /**
     * Retrieve store pick-up search radius from config.
     *
     * @return float
     */
    private function getSearchRadius(): float
    {
        return (float)$this->config->getValue(self::SEARCH_RADIUS, ScopeInterface::SCOPE_WEBSITE);
    }
}
