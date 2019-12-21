<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\ResourceModel\SourceTypeLink;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Inventory\Model\ResourceModel\SourceTypeLink as SourceTypeLinkResourceModel;
use Magento\Inventory\Model\SourceTypeLink as SourceTypeLinkModel;

/**
 * Resource Collection of SourceTypeLink entities
 *
 * It is not an API because SourceTypeLink must be loaded via Source entity only
 */
class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(SourceTypeLinkModel::class, SourceTypeLinkResourceModel::class);
    }
}
