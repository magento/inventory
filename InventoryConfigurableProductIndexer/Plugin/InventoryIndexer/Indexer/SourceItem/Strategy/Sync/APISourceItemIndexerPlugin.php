<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProductIndexer\Plugin\InventoryIndexer\Indexer\SourceItem\Strategy\Sync;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\AbstractResource;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryCache\Model\FlushCacheByCategoryIds;
use Magento\InventoryCache\Model\FlushCacheByProductIds;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventoryConfigurableProductIndexer\Indexer\SourceItem\SourceItemIndexer;
use Magento\InventoryIndexer\Model\ResourceModel\GetCategoryIdsByProductIds;

class APISourceItemIndexerPlugin
{
    /**
     * @var SourceItemIndexer
     */
    private SourceItemIndexer $configurableProductsSourceItemIndexer;

    /**
     * @var GetSourceItemsBySkuInterface
     */
    private GetSourceItemsBySkuInterface $getSourceItemsBySku;

    /**
     * @var DefaultSourceProviderInterface
     */
    private DefaultSourceProviderInterface $defaultSourceProvider;

    /**
     * @var GetSkusByProductIdsInterface
     */
    private GetSkusByProductIdsInterface $skuProvider;

    /**
     * @var FlushCacheByProductIds
     */
    private FlushCacheByProductIds $flushCacheByIds;

    /**
     * @var FlushCacheByCategoryIds
     */
    private FlushCacheByCategoryIds $flushCategoryByCategoryIds;

    /**
     * @var GetCategoryIdsByProductIds
     */
    private GetCategoryIdsByProductIds $getCategoryIdsByProductIds;

    /**
     * @param SourceItemIndexer $configurableProductsSourceItemIndexer
     * @param GetSourceItemsBySkuInterface $getSourceItemsBySku
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     * @param GetSkusByProductIdsInterface $getSkusByProductIdsInterface
     * @param FlushCacheByProductIds $flushCacheByIds
     * @param FlushCacheByCategoryIds $flushCategoryByCategoryIds
     * @param GetCategoryIdsByProductIds $getCategoryIdsByProductIds
     */
    public function __construct(
        SourceItemIndexer              $configurableProductsSourceItemIndexer,
        GetSourceItemsBySkuInterface   $getSourceItemsBySku,
        DefaultSourceProviderInterface $defaultSourceProvider,
        GetSkusByProductIdsInterface   $getSkusByProductIdsInterface,
        FlushCacheByProductIds         $flushCacheByIds,
        FlushCacheByCategoryIds        $flushCategoryByCategoryIds,
        GetCategoryIdsByProductIds     $getCategoryIdsByProductIds
    ) {
        $this->configurableProductsSourceItemIndexer = $configurableProductsSourceItemIndexer;
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->defaultSourceProvider = $defaultSourceProvider;
        $this->skuProvider = $getSkusByProductIdsInterface;
        $this->flushCacheByIds = $flushCacheByIds;
        $this->flushCategoryByCategoryIds = $flushCategoryByCategoryIds;
        $this->getCategoryIdsByProductIds = $getCategoryIdsByProductIds;
    }

    /**
     * Once the product has been saved, perform stock reindex
     *
     * @param ProductResource $subject
     * @param AbstractResource $result
     * @param AbstractModel $object
     * @return AbstractResource
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(
        ProductResource  $subject,
        AbstractResource $result,
        AbstractModel    $object
    ): AbstractResource {
        if ($object instanceof Product) {
            $childProductIds = $object->getTypeInstance()->getChildrenIds($object->getId());
            $sourceItemIds = $productsIdsToFlush = [];
            foreach ($childProductIds as $productIds) {
                if (empty($productIds)) {
                    continue;
                }
                foreach ($productIds as $productId) {
                    $productsIdsToFlush[] = $productId;
                    $childProductSku = $this->skuProvider->execute([$productId])[$productId];
                    $sourceItems = $this->getSourceItemsBySku->execute($childProductSku);
                    foreach ($sourceItems as $key => $sourceItem) {
                        if ($sourceItem->getSourceCode() === $this->defaultSourceProvider->getCode()) {
                            unset($sourceItems[$key]);
                            continue;
                        }
                        $sourceItem->setSku($object->getSku());
                        $sourceItemIds[] = $sourceItem->getId();
                    }
                }
            }
            if ($sourceItemIds) {
                $this->configurableProductsSourceItemIndexer->executeList($sourceItemIds);
                $categoryIds = $this->getCategoryIdsByProductIds->execute($productsIdsToFlush);
                $this->flushCacheByIds->execute($productsIdsToFlush);
                $this->flushCategoryByCategoryIds->execute($categoryIds);
            }
        }

        return $result;
    }
}
