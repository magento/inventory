<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Plugin\Model\ResourceModel\Product\Indexer\Price\Configurable;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Indexer\Price\Configurable;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Inventory\Model\ResourceModel\SourceItem;
use Magento\Framework\DB\Select;
use Magento\Inventory\Model\ResourceModel\StockSourceLink;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;

/**
 * Plugin for FilterSelectByInventory to add "is_salable" filter.
 */
class FilterSelectByInventoryPlugin
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
    public function __construct(
        ResourceConnection $resourceConnection,
        MetadataPool $metadataPool
    )
    {
        $this->resourceConnection = $resourceConnection;
        $this->metadataPool = $metadataPool;
    }

    /**
     * Add "is_salable" filter to select.
     *
     * @param Configurable $subject
     * @param callable $proceed
     * @param Select $select
     * @return Select
     *
     * @throws \Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundFilterSelectByInventory(Configurable $subject, callable $proceed, Select $select): Select
    {
        $metadata = $this->metadataPool->getMetadata(ProductInterface::class);
        $linkField = $metadata->getLinkField();

        $select->joinInner(
            ['le2' => $this->resourceConnection->getTableName('catalog_product_entity')],
            'le2.' . $linkField . ' = l.product_id',
            []
        )->joinInner(
            ['isi' => $this->resourceConnection->getTableName(SourceItem::TABLE_NAME_SOURCE_ITEM)],
            'isi.sku = le2.sku',
            []
        )->joinInner(
            ['issl' =>
                $this->resourceConnection->getTableName(StockSourceLink::TABLE_NAME_STOCK_SOURCE_LINK)],
            'issl.' . SourceItemInterface::SOURCE_CODE .' = isi.' . SourceItemInterface::SOURCE_CODE,
            []
        )->joinInner(
            ['issc' => $this->resourceConnection->getTableName('inventory_stock_sales_channel')],
            'issc.stock_id = issl.' . StockSourceLinkInterface::STOCK_ID,
            []
        )->joinInner(
            ['sw' => $this->resourceConnection->getTableName('store_website')],
            'sw.code = issc.code AND sw.website_id = i.website_id',
            []
        )->group(
            ['isi.sku']
        )->having(
            'MAX(isi.status) = 1'
        );

        return $select;
    }
}
