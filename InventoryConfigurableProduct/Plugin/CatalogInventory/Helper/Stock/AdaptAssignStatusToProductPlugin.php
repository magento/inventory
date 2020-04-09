<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Plugin\CatalogInventory\Helper\Stock;

use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Helper\Stock;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Process configurable product stock status considering configurable options salable status.
 */
class AdaptAssignStatusToProductPlugin
{
    /**
     * @var Configurable
     */
    private $configurable;

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
     * @param Configurable $configurable
     * @param IsProductSalableInterface $isProductSalable
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     */
    public function __construct(
        Configurable $configurable,
        IsProductSalableInterface $isProductSalable,
        StoreManagerInterface $storeManager,
        StockResolverInterface $stockResolver
    ) {
        $this->configurable = $configurable;
        $this->isProductSalable = $isProductSalable;
        $this->storeManager = $storeManager;
        $this->stockResolver = $stockResolver;
    }

    /**
     * Process configurable product stock status, considering configurable options.
     *
     * @param Stock $subject
     * @param Product $product
     * @param int|null $status
     * @return array
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeAssignStatusToProduct(
        Stock $subject,
        Product $product,
        $status = null
    ): array {
        if ($product->getTypeId() === Configurable::TYPE_CODE) {
            $website = $this->storeManager->getWebsite();
            $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $website->getCode());
            $options = $this->configurable->getConfigurableOptions($product);
            $status = 0;
            foreach ($options as $attribute) {
                foreach ($attribute as $option) {
                    if ($this->isProductSalable->execute($option['sku'], $stock->getStockId())) {
                        $status = 1;
                        break 2;
                    }
                }
            }
        }

        return [$product, $status];
    }
}
