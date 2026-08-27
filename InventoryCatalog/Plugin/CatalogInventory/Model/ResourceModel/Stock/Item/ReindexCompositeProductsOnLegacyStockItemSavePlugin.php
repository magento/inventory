<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Plugin\CatalogInventory\Model\ResourceModel\Stock\Item;

use Magento\CatalogInventory\Model\ResourceModel\Stock\Item as ItemResourceModel;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magento\InventoryIndexer\Indexer\CompositeProductsIndexer;
use Throwable;

/**
 * Reindexes non-default stocks for composite products that have no source items
 * (e.g. bundles) when the legacy stock item is saved.
 *
 * Without this plugin, admin-side toggles of bundle Stock Status only update
 * inventory_stock_1 (a VIEW over cataloginventory_stock_status) but leave
 * inventory_stock_N out of sync because no source-item indexer is triggered.
 */
class ReindexCompositeProductsOnLegacyStockItemSavePlugin
{
    /**
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param CompositeProductsIndexer $compositeProductsIndexer
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     */
    public function __construct(
        private readonly GetSkusByProductIdsInterface $getSkusByProductIds,
        private readonly CompositeProductsIndexer $compositeProductsIndexer,
        private readonly IsSingleSourceModeInterface $isSingleSourceMode
    ) {
    }

    /**
     * Trigger composite reindex on non-default stocks for products without source items.
     *
     * @param ItemResourceModel $subject
     * @param mixed $result
     * @param AbstractModel $stockItem
     * @return ItemResourceModel
     * @throws NoSuchEntityException
     * @throws Throwable
     */
    public function afterSave(
        ItemResourceModel $subject,
        ItemResourceModel $result,
        AbstractModel $stockItem
    ): ItemResourceModel {
        if (!$this->isSingleSourceMode->execute()) {
            $productId = (int) $stockItem->getProductId();
            try {
                $sku = $this->getSkusByProductIds->execute([$productId])[$productId];
            } catch (NoSuchEntityException $e) {
                $sku = null;
            }
            if ($sku !== null) {
                $subject->getConnection()->beginTransaction();
                try {
                    // Makes sure if this save is part of a larger transaction,
                    // the reindex will be deferred until after commit.
                    $subject->addCommitCallback(function () use ($sku) {
                        $this->compositeProductsIndexer->reindexList([$sku]);
                    });
                    $subject->getConnection()->commit();
                } catch (Throwable $e) {
                    $subject->getConnection()->rollBack();
                    throw $e;
                }
            }
        }

        return $result;
    }
}
