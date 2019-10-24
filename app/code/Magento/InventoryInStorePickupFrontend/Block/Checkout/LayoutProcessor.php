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
use Magento\InventoryInStorePickupFrontend\Model\Validator\StorePickUpValidator;
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
     * @var StorePickUpValidator
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
     * @param StorePickUpValidator $storePickUpValidator
     * @param ScopeConfigInterface $config
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ArrayManager $arrayManager,
        StorePickUpValidator $storePickUpValidator,
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
        try {
            $website = $this->storeManager->getWebsite();
        } catch (LocalizedException $e) {
            return $jsLayout;
        }

        if ($this->storePickUpValidator->execute($website->getCode())) {
            $stepsPath = $this->arrayManager->findPath('steps', $jsLayout);
            $jsLayout = $this->arrayManager->merge(
                $stepsPath,
                $jsLayout,
                $this->getStepConfig()
            );
            $sidebarPath = $this->arrayManager->findPath('shipping-information', $jsLayout);
            $jsLayout = $this->arrayManager->merge(
                $sidebarPath,
                $jsLayout,
                $this->getStorePickupSideBarConfig()
            );
        }

        return $jsLayout;
    }

    /**
     * Get store pickup steps component configuration.
     *
     * @return array
     */
    private function getStepConfig(): array
    {
        return [
            'children' => [
                'store-pickup' => [
                    'component' => 'Magento_InventoryInStorePickupFrontend/js/view/store-pickup',
                    'config' => [
                        'nearbySearchRadius' => $this->getSearchRadius(),
                    ],
                    'sortOrder' => 0,
                    'deps' => ['checkout.steps.shipping-step.shippingAddress'],
                    'children' => [
                        'store-selector' => [
                            'component' => 'Magento_InventoryInStorePickupFrontend/js/view/store-selector',
                            'displayArea' => 'store-selector',
                            'children' => [
                                'customer-email' => $this->getCustomerEmailConfig(),
                            ],
                            'config' => [
                                'popUpList' => [
                                    'element' => '#opc-store-selector-popup',
                                    'options' => [
                                        'type' => 'popup',
                                        'responsive' => true,
                                        'innerScroll' => true,
                                        'title' => __('Select Store'),
                                        'trigger' => 'opc-store-selector-popup',
                                        'buttons' => [],
                                        'modalClass' => 'store-selector-popup',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get side bar ui component configuration.
     *
     * @return array
     * phpcs:disable Magento2.Files.LineLength.MaxExceeded
     */
    private function getStorePickupSideBarConfig(): array
    {
        return [
            'children' => [
                'ship-to' => [
                    'rendererTemplates' => [
                        'store-pickup-address' => [
                            'component' => 'Magento_InventoryInStorePickupFrontend/js/view/shipping-information/address-renderer/store-pickup-address',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Get customer email ui component configuration.
     *
     * @return array
     */
    private function getCustomerEmailConfig(): array
    {
        return [
            'component' => 'Magento_InventoryInStorePickupFrontend/js/view/form/element/email',
            'displayArea' => 'customer-email',
            'tooltip' => [
                'description' => __('We\'ll send your order confirmation here.'),
            ],
            'children' => [
                'before-login-form' => [
                    'component' => 'uiComponent',
                    'displayArea' => 'before-login-form',
                    'children' => [
                        /* before login form fields */
                    ],
                ],
                'additional-login-form-fields' => [
                    'component' => 'uiComponent',
                    'displayArea' => 'additional-login-form-fields',
                    'children' => [
                        /* additional login form fields */
                    ],
                ],
            ],
        ];
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
