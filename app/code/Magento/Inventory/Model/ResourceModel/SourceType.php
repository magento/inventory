<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Implementation of basic operations for Source Type entity for specific db layer
 */
class SourceType extends AbstractDb
{

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init('inventory_source_type', 'type_code');
    }
}
