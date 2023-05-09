<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProductIndexer\Plugin\InventoryIndexer\Indexer\SourceItem\Strategy\Sync;

use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventoryConfigurableProductIndexer\Indexer\SourceItem\SourceItemIndexer;

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
     * @param SourceItemIndexer $configurableProductsSourceItemIndexer
     * @param GetSourceItemsBySkuInterface $getSourceItemsBySku
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     * @param GetSkusByProductIdsInterface $getSkusByProductIdsInterface
     */
    public function __construct(
        SourceItemIndexer              $configurableProductsSourceItemIndexer,
        GetSourceItemsBySkuInterface   $getSourceItemsBySku,
        DefaultSourceProviderInterface $defaultSourceProvider,
        GetSkusByProductIdsInterface   $getSkusByProductIdsInterface
    ) {
        $this->configurableProductsSourceItemIndexer = $configurableProductsSourceItemIndexer;
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->defaultSourceProvider = $defaultSourceProvider;
        $this->skuProvider = $getSkusByProductIdsInterface;
    }

    /**
     * Extracts product source item ids
     *
     * @param array $childProductIds
     * @return array
     * @throws NoSuchEntityException
     */
    private function getProductSourceItemIds(array $childProductIds): array
    {
        $sourceItemIds = [];
        foreach ($childProductIds as $productIds) {
            if (empty($productIds)) {
                continue;
            }
            foreach ($this->skuProvider->execute($productIds) as $childSku) {
                $sourceItems = $this->getSourceItemsBySku->execute($childSku);
                foreach ($sourceItems as $key => $sourceItem) {
                    if ($sourceItem->getSourceCode() === $this->defaultSourceProvider->getCode()) {
                        unset($sourceItems[$key]);
                        continue;
                    }
                    $sourceItemIds[] = $sourceItem->getId();
                }
            }
        }

        return $sourceItemIds;
    }

    /**
     * Once the product has been saved, perform stock reindex
     *
     * @param ProductResource $subject
     * @param ProductResource $result
     * @param AbstractModel $product
     * @return mixed
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(
        ProductResource $subject,
        ProductResource $result,
        AbstractModel   $product
    ): ProductResource {
        if ($product->getTypeId() != Configurable::TYPE_CODE) {
            return $result;
        }

        $childProductIds = $product->getTypeInstance()->getChildrenIds($product->getId());
        $sourceItemIds = $this->getProductSourceItemIds($childProductIds);
        if ($sourceItemIds) {
            $this->configurableProductsSourceItemIndexer->executeList($sourceItemIds);
            $product->setIsChangedCategories(true);
            $product->setAffectedCategoryIds($product->getCategoryIds());
            $product->cleanModelCache();
        }

        return $result;
    }
}
