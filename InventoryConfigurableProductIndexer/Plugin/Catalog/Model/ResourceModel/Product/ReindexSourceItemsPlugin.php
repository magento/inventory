<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProductIndexer\Plugin\Catalog\Model\ResourceModel\Product;

use Magento\Catalog\Model\ResourceModel\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventoryConfigurableProductIndexer\Indexer\SourceItem\SourceItemIndexer;
use Magento\InventoryIndexer\Indexer\SourceItem\GetSourceItemIds;

/**
 * Reindex source items for configurable product plugin.
 */
class ReindexSourceItemsPlugin
{
    /**
     * @var GetSourceItemsBySkuInterface
     */
    private $getSourceItemsBySku;

    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @var GetSourceItemIds
     */
    private $getSourceItemIds;

    /**
     * @var SourceItemIndexer
     */
    private $sourceItemIndexer;

    /**
     * @param GetSourceItemsBySkuInterface $getSourceItemsBySku
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param GetSourceItemIds $getSourceItemIds
     * @param SourceItemIndexer $sourceItemIndexer
     */
    public function __construct(
        GetSourceItemsBySkuInterface $getSourceItemsBySku,
        GetSkusByProductIdsInterface $getSkusByProductIds,
        GetSourceItemIds $getSourceItemIds,
        SourceItemIndexer $sourceItemIndexer
    ) {
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->getSourceItemIds = $getSourceItemIds;
        $this->sourceItemIndexer = $sourceItemIndexer;
    }

    /**
     * Reindex configurable source items after product save.
     *
     * @param Product $subject
     * @param Product $result
     * @param AbstractModel $product
     * @return Product
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(Product $subject, Product $result, AbstractModel $product): Product
    {
        if ($product->getTypeId() !== Configurable::TYPE_CODE) {
            return $result;
        }
        $childrenIds = $product->getExtensionAttributes()->getConfigurableProductLinks();
        $skus = $this->getSkusByProductIds->execute($childrenIds);
        $sourceItems = [[]];
        foreach ($skus as $sku) {
            $sourceItems[] = $this->getSourceItemsBySku->execute($sku);
        }
        $sourceItems = array_merge(...$sourceItems);
        $sourceItemIds = $this->getSourceItemIds->execute($sourceItems);
        $this->sourceItemIndexer->executeList($sourceItemIds);

        return $result;
    }
}
