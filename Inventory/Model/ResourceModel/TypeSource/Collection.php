<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\ResourceModel\TypeSource;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Inventory\Model\ResourceModel\Source\Type as SourceTypeResourceModel;
use Magento\Inventory\Model\SourceType;

/**
 * Resource Collection of Source Type entity
 */
class Collection extends AbstractCollection
{

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(SourceType::class, SourceTypeResourceModel::class);
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
            $options[] = [
                'label' => $item->getName(),
                'value' => $item->getTypeCode()
            ];
        }

        return $options;
    }
}
