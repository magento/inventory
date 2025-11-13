<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryLogging\Plugin\Logging\Model;

use Magento\Inventory\Model\SourceItem;
use Magento\Logging\Model\Config;

class ConfigPlugin
{
    private const AFFECTED_GROUP_NAME = 'catalog_products';

    /**
     * After plugin for getEventGroupConfig method. Adds inventory logging events to catalog_products group.
     *
     * @param Config $subject
     * @param mixed $result
     * @param string $groupName
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetEventGroupConfig(Config $subject, mixed $result, string $groupName): mixed
    {
        if ($groupName !== self::AFFECTED_GROUP_NAME) {
            return $result;
        }

        $result['expected_models'][SourceItem::class]['additional_data'] = [
            'source_item_id',
            'source_code',
            'sku',
            'quantity',
            'status'
        ];

        return $result;
    }
}
