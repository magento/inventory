<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\ResourceModel;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\InventoryApi\Api\Data\SourceItemInterface;

/**
 * Implementation of basic operations for Source Item entity for specific db layer
 */
class SourceItem extends AbstractDb
{
    /**#@+
     * Constants related to specific db layer
     */
    public const TABLE_NAME_SOURCE_ITEM = 'inventory_source_item';
    public const ID_FIELD_NAME = 'source_item_id';
    /**#@-*/

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(self::TABLE_NAME_SOURCE_ITEM, self::ID_FIELD_NAME);
    }

    /**
     * Retrieve all source items by given SKUs
     *
     * @param array $skus
     * @param array $columns
     * @return array
     * @throws LocalizedException
     */
    public function findAllBySkus(array $skus, array $columns = []): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from(
                $this->getMainTable(),
                $columns ?: '*'
            )->where(
                SourceItemInterface::SKU . ' IN (?)',
                $skus
            );

        return $connection->fetchAll($select);
    }
}
