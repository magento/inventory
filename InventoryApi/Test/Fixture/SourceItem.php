<?php
/**
 * Copyright 2022 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryApi\Test\Fixture;

use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\InventoryApi\Api\Data\SourceItemInterface;

class SourceItem extends SourceItems
{
    public const DEFAULT_DATA = [
        'sku' => 'sku%uniqid%',
        'source_code' => 'source%uniqid%',
        'quantity' => 100,
        'status' => SourceItemInterface::STATUS_IN_STOCK,
    ];

    /**
     * {@inheritdoc}
     * @param array $data Parameters. Same format as SourceItem::DEFAULT_DATA.
     */
    public function apply(array $data = []): ?DataObject
    {
        return parent::apply([$data]);
    }
}
