<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Implementation of basic operations for SourceCarrierLink entity for specific db layer
 */
class StockSourceLink extends AbstractDb
{
    /**#@+
     * Constants related to specific db layer
     */
    public const TABLE_NAME_STOCK_SOURCE_LINK = 'inventory_source_stock_link';
    public const ID_FIELD_NAME = 'link_id';
    /**#@-*/

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(self::TABLE_NAME_STOCK_SOURCE_LINK, self::ID_FIELD_NAME);
    }
}
