<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model\ResourceModel;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store;

/**
 * Retrieve product status resource.
 */
class GetProductStatusByProductIdAndStoreId
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
     * @var ProductAttributeRepositoryInterface
     */
    private $productAttributeRepository;

    /**
     * @param ResourceConnection $resourceConnection
     * @param MetadataPool $metadataPool
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        MetadataPool $metadataPool,
        ProductAttributeRepositoryInterface $productAttributeRepository
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->metadataPool = $metadataPool;
        $this->productAttributeRepository = $productAttributeRepository;
    }

    /**
     * Retrieve product status by product id and website code.
     *
     * @param int $productId
     * @param int $storeId
     * @return int
     * @throws \Exception
     */
    public function execute(int $productId, int $storeId): int
    {
        $connection = $this->resourceConnection->getConnection();
        $productTable = $this->resourceConnection->getTableName('catalog_product_entity');
        $select = $connection->select()
            ->from(['product' => $productTable])
            ->where(
                'product.entity_id = ?',
                $productId
            );
        $linkField = $this->metadataPool->getMetadata(ProductInterface::class)->getLinkField();
        $select->joinInner(
            ['product_entity_int' => $this->resourceConnection->getTableName('catalog_product_entity_int')],
            'product_entity_int.' . $linkField . ' = product.' . $linkField . ' ' .
            'AND product_entity_int.attribute_id = ' . $this->getStatusId()
            . ' AND product_entity_int.store_id = ' . Store::DEFAULT_STORE_ID,
            []
        )->joinLeft(
            ['product_entity_int_store' => $this->resourceConnection->getTableName('catalog_product_entity_int')],
            'product_entity_int_store.' . $linkField . ' = product.' . $linkField . ' ' .
            'AND product_entity_int_store.attribute_id = ' . $this->getStatusId()
            . ' AND product_entity_int_store.store_id = ' . $storeId,
            []
        )->where(
            $select->getConnection()->getIfNullSql(
                'product_entity_int_store.value',
                'product_entity_int.value'
            ) . '= ?',
            Status::STATUS_ENABLED
        );

        return $connection->fetchOne($select) ? Status::STATUS_ENABLED : Status::STATUS_DISABLED;
    }

    /**
     * Retrieve 'status' attribute id.
     *
     * @return int
     * @throws NoSuchEntityException
     */
    private function getStatusId(): int
    {
        return (int)$this->productAttributeRepository->get(ProductInterface::STATUS)->getAttributeId();
    }
}
