<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\ResourceModel\Stock;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Inventory\Model\ResourceModel\Stock as StockResourceModel;
use Magento\Inventory\Model\Stock as StockModel;

/**
 * Resource Collection of Source Items entity
 *
 * @api
 */
class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(StockModel::class, StockResourceModel::class);
    }
}
