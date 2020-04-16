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
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalogApi\Model\ProductStatusSelectProcessorInterface;
use Magento\Store\Model\Store;

/**
 * @inheritDoc
 */
class ProductStatusSelectProcessor implements ProductStatusSelectProcessorInterface
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
     * @inheritDoc
     */
    public function execute(Select $select, int $stockId): Select
    {
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
            . ' AND product_entity_int_store.store_id = ' . $this->getStoreId($stockId),
            []
        )->where(
            $select->getConnection()->getIfNullSql(
                'product_entity_int_store.value',
                'product_entity_int.value'
            ) . '= ?',
            Status::STATUS_ENABLED
        );

        return $select;
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

    /**
     * Retrieve store id for given stock.
     *
     * @param int $stockId
     * @return int
     */
    private function getStoreId(int $stockId): int
    {
        $salesChannel = $this->resourceConnection->getTableName('inventory_stock_sales_channel');
        $website = $this->resourceConnection->getTableName('store_website');
        $store = $this->resourceConnection->getTableName('store');
        $connection = $this->resourceConnection->getConnection();
        $query = $connection->select()
            ->from(
                ['store' => $store],
                ['store_id']
            )->where(
                'store.is_active = ?',
                1
            )
            ->joinInner(
                ['website' => $website],
                'store.website_id = website.website_id',
                []
            )
            ->joinInner(
                ['sales_channel' => $salesChannel],
                'sales_channel.code = website.code',
                []
            )->where(
                'sales_channel.type = ?',
                'website'
            )
            ->where(
                'sales_channel.stock_id = ?',
                $stockId
            );

        return (int)$connection->fetchOne($query);
    }
}
