<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Plugin\Model\AttributeOptionProvider;

use Magento\ConfigurableProduct\Model\AttributeOptionProviderInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Verify configurable options are salable.
 */
class IsSalableOptionPlugin
{
    /**
     * @var IsProductSalableInterface
     */
    private $isProductSalable;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @param IsProductSalableInterface $isProductSalable
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     */
    public function __construct(
        IsProductSalableInterface $isProductSalable,
        StoreManagerInterface $storeManager,
        StockResolverInterface $stockResolver
    ) {
        $this->isProductSalable = $isProductSalable;
        $this->storeManager = $storeManager;
        $this->stockResolver = $stockResolver;
    }

    /**
     * Remove not salable configurable options from options array.
     *
     * @param AttributeOptionProviderInterface $subject
     * @param array $options
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetAttributeOptions(AttributeOptionProviderInterface $subject, array $options): array
    {
        try {
            $website = $this->storeManager->getWebsite();
            $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $website->getCode());
        } catch (NoSuchEntityException|LocalizedException $e) {
            return $options;
        }

        foreach ($options as $key => $option) {
            if (!$this->isProductSalable->execute($option['sku'], $stock->getStockId())) {
                unset($options[$key]);
            }
        }

        return array_values($options);
    }
}
