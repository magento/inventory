<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\ResourceModel\StockSourceLink;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Inventory\Model\ResourceModel\StockSourceLink as StockSourceLinkResourceModel;
use Magento\Inventory\Model\StockSourceLink as StockSourceLinkModel;

/**
 * Resource Collection of StockSourceLink entities
 * It is not an API because StockSourceLink used in module internally
 */
class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(StockSourceLinkModel::class, StockSourceLinkResourceModel::class);
    }
}
