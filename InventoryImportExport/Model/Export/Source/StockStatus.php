<?php
/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Model\Export\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\InventoryApi\Api\Data\SourceItemInterface;

/**
 * @inheritdoc
 */
class StockStatus extends AbstractSource
{
    /**
     * Retrieve All options
     *
     * @return array
     */
    public function getAllOptions()
    {
        return [
            [
                'value' => SourceItemInterface::STATUS_IN_STOCK,
                'label' => __('In Stock'),
            ],
            [
                'value' => SourceItemInterface::STATUS_OUT_OF_STOCK,
                'label' => __('Out of Stock'),
            ],
        ];
    }
}
