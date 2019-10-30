<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Indexer\Stock;

use Exception;
use Magento\Bundle\Model\Product\Type;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\ResourceConnection;

/**
 * Class SelectBuilder
 */
class GetAllBundleProductsService
{
    private const BULK_SIZE = 500;
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @param ResourceConnection $resourceConnection
     * @param ProductCollectionFactory $productCollectionFactory
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ProductCollectionFactory $productCollectionFactory
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * Get all bundle products
     *
     * @return ProductCollection
     * @throws Exception
     */
    public function execute(): ProductCollection
    {
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addFieldToSelect('sku');
        $productCollection->addFieldToFilter('type_id', Type::TYPE_CODE);
        $productCollection->setPageSize(self::BULK_SIZE);

        return $productCollection;
    }
}
