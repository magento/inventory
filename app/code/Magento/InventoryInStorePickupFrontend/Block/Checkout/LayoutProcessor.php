<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupFrontend\Block\Checkout;

use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\InventoryInStorePickupFrontend\Model\Validator\IsStorePickUpAvailableForWebsiteValidator;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Add store pickup information on checkout page.
 */
class LayoutProcessor implements LayoutProcessorInterface
{
    private const SEARCH_RADIUS = 'carriers/in_store/search_radius';

    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @var IsStorePickUpAvailableForWebsiteValidator
     */
    private $storePickUpValidator;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @param StoreManagerInterface $storeManager
     * @param ArrayManager $arrayManager
     * @param IsStorePickUpAvailableForWebsiteValidator $storePickUpValidator
     * @param ScopeConfigInterface $config
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ArrayManager $arrayManager,
        IsStorePickUpAvailableForWebsiteValidator $storePickUpValidator,
        ScopeConfigInterface $config
    ) {
        $this->arrayManager = $arrayManager;
        $this->storePickUpValidator = $storePickUpValidator;
        $this->storeManager = $storeManager;
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function process($jsLayout)
    {
        $website = $this->storeManager->getWebsite();
        if (!$this->storePickUpValidator->execute($website->getCode())) {
            return $this->removeStorePickup($jsLayout);
        }
        $jsLayout = $this->addStorePickupComponents($jsLayout);

        return $jsLayout;
    }

    /**
     * Remove store pickup ui components from layout.
     *
     * @param array $jsLayout
     * @return array
     */
    private function removeStorePickup(array $jsLayout): array
    {
        $storePickupPath = $this->arrayManager->findPath('store-pickup', $jsLayout);
        $shipToPath = $this->arrayManager->findPath('store-pickup-address', $jsLayout);
        $jsLayout = $this->arrayManager->remove($storePickupPath, $jsLayout);
        $jsLayout = $this->arrayManager->remove($shipToPath, $jsLayout);

        return $jsLayout;
    }

    /**
     * Add ui store pickup components to layout.
     *
     * @param array $jsLayout
     * @return array
     */
    private function addStorePickupComponents(array $jsLayout): array
    {
        $jsLayout = $this->arrayManager->merge(
            $this->arrayManager->findPath('store-pickup', $jsLayout),
            $jsLayout,
            [
                'config' => [
                    'nearbySearchRadius' => $this->getSearchRadius(),
                ],
            ]
        );
        $jsLayout = $this->arrayManager->merge(
            $this->arrayManager->findPath('shipping-information', $jsLayout),
            $jsLayout,
            [
                'component' => 'Magento_InventoryInStorePickupFrontend/js/view/shipping-information',
            ]
        );

        return $jsLayout;
    }

    /**
     * Retrieve store pick-up search radius from config.
     *
     * @return float
     */
    private function getSearchRadius(): float
    {
        try {
            $website = $this->storeManager->getWebsite();
        } catch (LocalizedException $e) {
            return (float)$this->config->getValue(self::SEARCH_RADIUS);
        }

        return (float)$this->config->getValue(self::SEARCH_RADIUS, ScopeInterface::SCOPE_WEBSITE, $website->getId());
    }
}
