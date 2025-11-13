<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\Framework\Model\ResourceModel\PredefinedId;

/**
 * Implementation of basic operations for Stock entity for specific db layer
 */
class Stock extends AbstractDb
{
    /**
     * Provides possibility of saving entity with predefined/pre-generated id
     */
    use PredefinedId;

    /**#@+
     * Constants related to specific db layer
     */
    public const TABLE_NAME_STOCK = 'inventory_stock';
    /**#@-*/

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(self::TABLE_NAME_STOCK, StockInterface::STOCK_ID);
        $this->addUniqueField(
            [
                'field' => StockInterface::NAME,
                'title' => 'Name'
            ]
        );
    }
}
