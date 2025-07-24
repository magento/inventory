<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\ResourceModel\SourceCarrierLink;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Inventory\Model\ResourceModel\SourceCarrierLink as SourceCarrierLinkResourceModel;
use Magento\Inventory\Model\SourceCarrierLink as SourceCarrierLinkModel;

/**
 * Resource Collection of SourceCarrierLink entities
 * It is not an API because SourceCarrierLink must be loaded via Source entity only
 */
class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(SourceCarrierLinkModel::class, SourceCarrierLinkResourceModel::class);
    }
}
