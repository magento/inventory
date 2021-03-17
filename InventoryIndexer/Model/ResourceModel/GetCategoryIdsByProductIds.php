<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

/**
 * Get all categories where product is visible
 */
class GetCategoryIdsByProductIds
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Get category ids for products
     *
     * @param array $productIds
     * @return array
     */
    public function execute(array $productIds): array
    {
        $connection = $this->resourceConnection->getConnection();
        $categoryProductTable = $this->resourceConnection->getTableName('catalog_category_product');
        $select = $connection->select()
            ->from(['catalog_category_product' => $categoryProductTable], ['category_id'])
            ->where('product_id IN (?)', $productIds);

        return $connection->fetchCol($select);
    }
}
