<?php
/**
 * Copyright 2021 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProduct\Model;

use Magento\Bundle\Model\ResourceModel\Selection\Collection;
use Magento\Bundle\Model\ResourceModel\Selection\CollectionFactory;
use Magento\Bundle\Model\ResourceModel\Selection\Collection\FilterApplier;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\EntityManager\MetadataPool;
use Zend_Db_Expr;
use Zend_Db_Select_Exception;

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
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @param CollectionFactory $selectionCollectionFactory
     * @param FilterApplier $selectionCollectionFilterApplier
     * @param int $batchSize
     * @param MetadataPool|null $metadataPool
     */
    public function __construct(
        CollectionFactory $selectionCollectionFactory,
        FilterApplier $selectionCollectionFilterApplier,
        int $batchSize = self::DEFAULT_BATCH_SIZE,
        ?MetadataPool $metadataPool = null
    ) {
        $this->selectionCollectionFactory = $selectionCollectionFactory;
        $this->selectionCollectionFilterApplier = $selectionCollectionFilterApplier;
        $this->batchSize = $batchSize;
        $this->metadataPool = $metadataPool ?:
            ObjectManager::getInstance()->get(MetadataPool::class);
    }

    /**
     * Return bundle products selections SKUs indexed by bundle product link ID.
     *
     * @param array $parentIds
     * @return array
     * @throws Zend_Db_Select_Exception
     */
    public function execute(array $parentIds): array
    {
        /** @var Collection $collection */
        $collection = $this->selectionCollectionFactory->create();
        $collection->addFilterByRequiredOptions();
        $collection->setFlag('product_children', true);
        $linkField = $this->metadataPool->getMetadata(ProductInterface::class)
            ->getLinkField();
        $collection->getSelect()->group('e.' . $linkField);
        $collection->getSelect()->columns([
            'parent_product_id' =>
                new Zend_Db_Expr('GROUP_CONCAT(selection.parent_product_id)')
        ]);
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
