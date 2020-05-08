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
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Store\Model\StoreManagerInterface;

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
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param CollectionFactory $collectionFactory
     * @param MetadataPool $metadataPool
     * @param FilterApplier $selectionCollectionFilterApplier
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        MetadataPool $metadataPool,
        FilterApplier $selectionCollectionFilterApplier,
        StoreManagerInterface $storeManager
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->metadataPool = $metadataPool;
        $this->selectionCollectionFilterApplier = $selectionCollectionFilterApplier;
        $this->storeManager = $storeManager;
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
        $selectionsCollection->addStoreFilter($this->storeManager->getStore());
        $selectionsCollection->addAttributeToFilter(ProductInterface::STATUS, Status::STATUS_ENABLED);
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
