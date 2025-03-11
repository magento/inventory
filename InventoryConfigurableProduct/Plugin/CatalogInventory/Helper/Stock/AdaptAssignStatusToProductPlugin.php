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
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalog\Model\GetStockIdForByStoreId;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;

/**
 * Process configurable product stock status considering configurable options salable status.
 */
class AdaptAssignStatusToProductPlugin
{
    /**
     * @param Configurable $configurable
     * @param AreProductsSalableInterface $areProductsSalable
     * @param GetStockItemDataInterface $getStockItemData
     * @param GetStockIdForByStoreId $getStockIdForByStoreId
     */
    public function __construct(
        private readonly Configurable $configurable,
        private readonly AreProductsSalableInterface $areProductsSalable,
        private readonly GetStockItemDataInterface $getStockItemData,
        private readonly GetStockIdForByStoreId $getStockIdForByStoreId
    ) {
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
            $stockId = $this->getStockIdForByStoreId->execute((int) $product->getStoreId());
            try {
                $stockItemData = $this->getStockItemData->execute($product->getSku(), $stockId);
            } catch (NoSuchEntityException $exception) {
                $stockItemData = null;
            }
            if (null !== $stockItemData) {
                if (!((bool) $stockItemData[GetStockItemDataInterface::IS_SALABLE])) {
                    return [$product, $status];
                }
            }
            $options = $this->configurable->getConfigurableOptions($product);
            $status = 0;
            $skus = [[]];
            foreach ($options as $attribute) {
                $skus[] = array_column($attribute, 'sku');
            }
            $skus = array_merge(...$skus);
            $results = $this->areProductsSalable->execute($skus, $stockId);
            foreach ($results as $result) {
                if ($result->isSalable()) {
                    $status = 1;
                    break;
                }
            }
        }

        return [$product, $status];
    }
}
