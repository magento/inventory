<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\ResourceModel\Source;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Implementation of basic operations for type entity for specific db layer
 */
class Type extends AbstractDb
{
    /**
     * Define main table name and attributes table
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('inventory_source_type', 'type_code');
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAllTypes()
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from(
            $this->getMainTable());

        return $connection->fetchAll($select);
    }
}
