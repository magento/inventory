<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProductIndexer\Plugin\InventoryIndexer\Indexer\SourceItem\Strategy\Sync;

use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\InventoryApi\Model\GetStockIdsBySkusInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventoryIndexer\Indexer\SourceItem\SkuListInStockFactory;
use Magento\InventoryIndexer\Indexer\Stock\SkuListsProcessor;

class APISourceItemIndexerPlugin
{
    /**
     * @param Configurable $configurableType
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param GetStockIdsBySkusInterface $getStockIdsBySkus
     * @param SkuListInStockFactory $skuListInStockFactory
     * @param SkuListsProcessor $skuListsProcessor
     */
    public function __construct(
        private readonly Configurable $configurableType,
        private readonly GetSkusByProductIdsInterface $getSkusByProductIds,
        private readonly GetStockIdsBySkusInterface $getStockIdsBySkus,
        private readonly SkuListInStockFactory $skuListInStockFactory,
        private readonly SkuListsProcessor $skuListsProcessor,
    ) {
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

        $childProductIds = $this->configurableType->getChildrenIds($product->getId())[0];
        if (!$childProductIds) {
            return $result;
        }

        $childProductSkus = $this->getSkusByProductIds->execute($childProductIds);
        $stockIds = $this->getStockIdsBySkus->execute($childProductSkus);
        if ($stockIds) {
            $skuListInStockList = [];
            foreach ($stockIds as $stockId) {
                $skuListInStock = $this->skuListInStockFactory->create(
                    ['stockId' => $stockId, 'skuList' => [$product->getSku()]]
                );
                $skuListInStockList[] = $skuListInStock;
            }
            $this->skuListsProcessor->reindexList($skuListInStockList);

            $product->setIsChangedCategories(true);
            $product->setAffectedCategoryIds($product->getCategoryIds());
            $product->cleanModelCache();
        }

        return $result;
    }
}
