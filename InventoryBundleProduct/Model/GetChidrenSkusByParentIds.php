<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProduct\Model;

use Magento\Bundle\Model\ResourceModel\Selection\Collection;
use Magento\Bundle\Model\ResourceModel\Selection\CollectionFactory;
use Magento\Bundle\Model\ResourceModel\Selection\Collection\FilterApplier;

/**
 * Retrieve bundle products selections SKUs.
 */
class GetChidrenSkusByParentIds
{
    private const DEFAULT_BATCH_SIZE = 10000;

    /**
     * @var CollectionFactory
     */
    private $selectionCollectionFactory;

    /**
     * @var FilterApplier
     */
    private $selectionCollectionFilterApplier;

    /**
     * @var int
     */
    private $batchSize;

    /**
     * @param CollectionFactory $selectionCollectionFactory
     * @param FilterApplier $selectionCollectionFilterApplier
     * @param int $batchSize
     */
    public function __construct(
        CollectionFactory $selectionCollectionFactory,
        FilterApplier $selectionCollectionFilterApplier,
        int $batchSize = self::DEFAULT_BATCH_SIZE
    ) {
        $this->selectionCollectionFactory = $selectionCollectionFactory;
        $this->selectionCollectionFilterApplier = $selectionCollectionFilterApplier;
        $this->batchSize = $batchSize;
    }

    /**
     * Return bundle products selections SKUs indexed by bundle product link ID.
     *
     * @param array $parentIds
     * @return array
     */
    public function execute(array $parentIds): array
    {
        /** @var Collection $collection */
        $collection = $this->selectionCollectionFactory->create();
        $collection->addFilterByRequiredOptions();
        $collection->setFlag('product_children', true);
        $this->selectionCollectionFilterApplier->apply(
            $collection,
            'parent_product_id',
            $parentIds,
            'in'
        );
        $chidrenSkusByParentId = [];
        foreach ($this->iterate($collection) as $product) {
            $chidrenSkusByParentId[$product->getParentProductId()][$product->getId()] = $product->getSku();
        }

        return $chidrenSkusByParentId;
    }

    /**
     * Iterates collection using pagination.
     *
     * @param Collection $collection
     * @return \Generator
     */
    private function iterate(Collection $collection): \Generator
    {
        $collection->setPageSize($this->batchSize);
        $pages = $collection->getLastPageNumber();
        for ($currentPage = 1; $currentPage <= $pages; $currentPage++) {
            $collection->setCurPage($currentPage);
            foreach ($collection->getItems() as $item) {
                yield $item;
            }
            $collection->clear();
        }
    }
}
