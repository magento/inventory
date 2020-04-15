<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model\ResourceModel;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;

/**
 * Retrieve product status resource.
 */
class GetProductStatus
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @param ResourceConnection $resourceConnection
     * @param MetadataPool $metadataPool
     */
    public function __construct(ResourceConnection $resourceConnection, MetadataPool $metadataPool)
    {
        $this->resourceConnection = $resourceConnection;
        $this->metadataPool = $metadataPool;
    }

    /**
     * Retrieve product status by product id and 'status' attribute id.
     *
     * @param int $statusId
     * @param int $productId
     * @return int
     * @throws \Exception
     */
    public function execute(int $statusId, int $productId): int
    {
        $linkField = $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();
        $connection = $this->resourceConnection->getConnection();
        $productTable = $this->resourceConnection->getTableName('catalog_product_entity');
        $statusTable = $this->resourceConnection->getTableName('catalog_product_entity_int');
        $sql = $connection->select()->from(['product' => $productTable], [])
            ->joinLeft(
                ['status' => $statusTable],
                'product.' . $linkField . ' = status.' . $linkField,
                ['value']
            )
            ->where('status.attribute_id = ?', $statusId)
            ->where('product.entity_id = ?', $productId);

        return (int)$connection->fetchOne($sql);
    }
}
