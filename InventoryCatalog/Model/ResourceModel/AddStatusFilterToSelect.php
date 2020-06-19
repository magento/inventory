<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model\ResourceModel;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Catalog\Model\ResourceModel\Product\Indexer\Price\Query\JoinAttributeProcessor;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;

/**
 * Add status filter to select resource.
 */
class AddStatusFilterToSelect
{
    /**
     * @var ResourceConnection
     */
    private $connection;

    /**
     * @var JoinAttributeProcessor
     */
    private $joinAttributeProcessor;

    /**
     * @param ResourceConnection $connection
     * @param JoinAttributeProcessor $joinAttributeProcessor
     */
    public function __construct(ResourceConnection $connection, JoinAttributeProcessor $joinAttributeProcessor)
    {
        $this->connection = $connection;
        $this->joinAttributeProcessor = $joinAttributeProcessor;
    }

    /**
     * Add product status filter to select.
     *
     * @param Select $select
     * @return Select
     * @throws LocalizedException
     * @throws \Zend_Db_Select_Exception
     */
    public function execute(Select $select): Select
    {
        $select->joinInner(
            ['pw' => $this->connection->getTableName('catalog_product_website')],
            'pw.product_id = e.entity_id',
            ['pw.website_id']
        )->joinInner(
            ['cwd' => $this->connection->getTableName('catalog_product_index_website')],
            'pw.website_id = cwd.website_id',
            []
        );
        $this->joinAttributeProcessor->process($select, ProductInterface::STATUS, ProductStatus::STATUS_ENABLED);

        return $select;
    }
}
