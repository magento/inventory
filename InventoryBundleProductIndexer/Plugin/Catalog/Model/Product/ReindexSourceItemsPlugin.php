<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Plugin\Catalog\Model\Product;

use Magento\Bundle\Model\Product\Type;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventoryIndexer\Indexer\SourceItem\GetSourceItemIds;
use Magento\InventoryIndexer\Indexer\SourceItem\SourceItemIndexer;

/**
 * Reindex source items for bundle product plugin.
 */
class ReindexSourceItemsPlugin
{
    /**
     * @var GetSourceItemsBySkuInterface
     */
    private $getSourceItemsBySku;

    /**
     * @var GetSourceItemIds
     */
    private $getSourceItemIds;

    /**
     * @var SourceItemIndexer
     */
    private $sourceItemIndexer;

    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @param GetSourceItemsBySkuInterface $getSourceItemsBySku
     * @param GetSourceItemIds $getSourceItemIds
     * @param SourceItemIndexer $sourceItemIndexer
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     */
    public function __construct(
        GetSourceItemsBySkuInterface $getSourceItemsBySku,
        GetSourceItemIds $getSourceItemIds,
        SourceItemIndexer $sourceItemIndexer,
        GetSkusByProductIdsInterface $getSkusByProductIds
    ) {
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->getSourceItemIds = $getSourceItemIds;
        $this->sourceItemIndexer = $sourceItemIndexer;
        $this->getSkusByProductIds = $getSkusByProductIds;
    }

    /**
     * Reindex bundle source items after product save.
     *
     * @param Product $subject
     * @param Product $result
     * @return Product
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterAfterSave(Product $subject, Product $result): Product
    {
        if ($result->getTypeId() !== Type::TYPE_CODE) {
            return $result;
        }
        $skus = $this->getSelectionsSku($result);
        $sourceItems = [[]];
        foreach ($skus as $sku) {
            $sourceItems[] = $this->getSourceItemsBySku->execute($sku);
        }
        $sourceItems = array_merge(...$sourceItems);
        $sourceItemIds = $this->getSourceItemIds->execute($sourceItems);
        $this->sourceItemIndexer->executeList($sourceItemIds);

        return $result;
    }

    /**
     * Retrieve bundle selections skus.
     *
     * @param Product $result
     * @return array
     */
    private function getSelectionsSku(Product $result): array
    {
        $bundleSelectionsData = $result->getBundleSelectionsData() ?: [];
        $skus = [];
        foreach ($bundleSelectionsData as $option) {
            $skus[] = array_column($option, 'sku');
        }
        $skus = $skus ? array_merge(...$skus) : $skus;
        if ($skus) {
            return $skus;
        }
        $ids = [];
        foreach ($bundleSelectionsData as $option) {
            $ids[] = array_column($option, 'product_id');
        }
        $ids = $ids ? array_merge(...$ids) : $ids;

        return $this->getSkusByProductIds->execute($ids);
    }
}
