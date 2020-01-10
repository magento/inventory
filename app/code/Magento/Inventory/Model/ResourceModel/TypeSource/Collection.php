<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\ResourceModel\TypeSource;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    public function _construct()
    {
        $this->_init('Magento\Inventory\Model\SourceType', 'Magento\Inventory\Model\ResourceModel\SourceType');
    }

    /**
     * Convert collection items to select options array
     *
     * @return array
     */
    public function toOptionArray()
    {

        $options = [];
        foreach ($this as $item) {
            $option['label'] = $item->getName();
            $option['value'] = $item->getTypeCode();

            $options[] = $option;
        }

        return $options;
    }
}
