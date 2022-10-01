<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Plugin\CatalogInventory;

use Magento\Catalog\Model\ResourceModel\GetProductTypeById;
use Magento\CatalogInventory\Model\ResourceModel\Stock\Item as ItemResourceModel;
use Magento\CatalogInventory\Model\Stock\Item as StockItemModel;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Model\AbstractModel as StockItem;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\CatalogInventory\Model\Stock;
use Magento\InventoryCatalog\Model\ResourceModel\UpdateLegacyStockItems;

class UpdateLegacyStockItemForNewConfigurableProduct
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var GetProductTypeById
     */
    private $getProductTypeById;

    /**
     * @var Configurable
     */
    private $configurableType;

    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @var UpdateLegacyStockItems
     */
    private $updateLegacyStockItems;

    /**
     * @param RequestInterface $request
     * @param Json $serializer
     * @param GetProductTypeById $getProductTypeById
     * @param Configurable $configurableType
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param AreProductsSalableInterface $areProductsSalable
     * @param UpdateLegacyStockItems $updateLegacyStockItems
     */
    public function __construct(
        RequestInterface $request,
        Json $serializer,
        GetProductTypeById $getProductTypeById,
        Configurable $configurableType,
        GetSkusByProductIdsInterface $getSkusByProductIds,
        AreProductsSalableInterface $areProductsSalable,
        UpdateLegacyStockItems $updateLegacyStockItems
    ) {
        $this->request = $request;
        $this->serializer = $serializer;
        $this->getProductTypeById = $getProductTypeById;
        $this->configurableType = $configurableType;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->areProductsSalable = $areProductsSalable;
        $this->updateLegacyStockItems = $updateLegacyStockItems;
    }

    /**
     * Updates stock item for new configurable product based on variation's qty
     *
     * @param ItemResourceModel $subject
     * @param ItemResourceModel $result
     * @param StockItem $stockItem
     * @return ItemResourceModel
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(ItemResourceModel $subject, ItemResourceModel $result, StockItem $stockItem)
    {
        if ($stockItem->isObjectNew() &&
            $stockItem->getIsInStock() &&
            $this->getProductTypeById->execute($stockItem->getProductId()) === Configurable::TYPE_CODE
        ) {
            $configurableMatrix = $this->request->getParam('configurable-matrix-serialized');
            if (!empty($configurableMatrix) && $configurableMatrix !== '[]') {
                $this->updateStatus($stockItem, $this->hasStockStatusFromVariationMatrix($configurableMatrix));
            } else {
                $childrenIds = $this->configurableType->getChildrenIds($stockItem->getProductId());
                $childrenIds = array_shift($childrenIds);
                if (!empty($childrenIds)) {
                    $this->updateStatus($stockItem, $this->hasStockStatusFromChildren($childrenIds));
                }
            }
        }

        return $result;
    }

    /**
     * Updates Configurable stock status based on the variations
     *
     * @param StockItem $stockItem
     * @param bool $isInStock
     * @return void
     */
    private function updateStatus(StockItem $stockItem, bool $isInStock): void
    {
        if ($stockItem->getIsInStock() == $isInStock) {
            return;
        }
        $stockItemData = [
            StockItemModel::IS_IN_STOCK => $isInStock,
            StockItemModel::STOCK_STATUS_CHANGED_AUTO => 1
        ];
        $this->updateLegacyStockItems->execute([$stockItem->getProductId()], $stockItemData);
    }

    /**
     * Get stock status based on qty of the variation-matrix from request
     *
     * @param string $configurableMatrix
     * @return bool
     */
    private function hasStockStatusFromVariationMatrix(string $configurableMatrix): bool
    {
        $configurableMatrix = $this->serializer->unserialize($configurableMatrix);
        foreach ($configurableMatrix as $item) {
            if (!empty($item['qty'])) {
                return true;
            } elseif (!empty($item['quantity_per_source'])) {
                foreach ($item['quantity_per_source'] as $source) {
                    if (!empty($source['quantity_per_source'])) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Checks if configurable has salable options
     *
     * @param array $childrenIds
     * @return bool
     */
    private function hasStockStatusFromChildren(array $childrenIds): bool
    {
        $skus = $this->getSkusByProductIds->execute($childrenIds);
        $areSalableResults = $this->areProductsSalable->execute($skus, Stock::DEFAULT_STOCK_ID);
        foreach ($areSalableResults as $productSalable) {
            if ($productSalable->isSalable() === true) {
                return true;
            }
        }

        return false;
    }
}
