<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProduct\Model;

use Magento\Bundle\Api\Data\OptionInterface;
use Magento\Bundle\Model\ResourceModel\Selection\Collection;
use Magento\Bundle\Model\ResourceModel\Selection\Collection\FilterApplier;
use Magento\Bundle\Model\ResourceModel\Selection\CollectionFactory;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\EntityManager\MetadataPool;

/**
 * Retrieve bundle product selection service.
 */
class GetProductSelection
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var FilterApplier
     */
    private $selectionCollectionFilterApplier;

    /**
     * @param CollectionFactory $collectionFactory
     * @param MetadataPool $metadataPool
     * @param FilterApplier $selectionCollectionFilterApplier
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        MetadataPool $metadataPool,
        FilterApplier $selectionCollectionFilterApplier
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->metadataPool = $metadataPool;
        $this->selectionCollectionFilterApplier = $selectionCollectionFilterApplier;
    }

    /**
     * Retrieve bundle product selection for given bundle product and option.
     *
     * @param ProductInterface $product
     * @param OptionInterface $option
     * @return Collection
     * @throws \Exception
     */
    public function execute(ProductInterface $product, OptionInterface $option): Collection
    {
        $metadata = $this->metadataPool->getMetadata(ProductInterface::class);
        $selectionsCollection = $this->collectionFactory->create();
        $selectionsCollection->addAttributeToSelect('status');
        $selectionsCollection->setFlag('product_children', true);
        $selectionsCollection->addFilterByRequiredOptions();
        $selectionsCollection->setOptionIdsFilter([$option->getId()]);

        $this->selectionCollectionFilterApplier->apply(
            $selectionsCollection,
            'parent_product_id',
            $product->getData($metadata->getLinkField())
        );

        return $selectionsCollection;
    }
}
