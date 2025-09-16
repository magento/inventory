<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryLowQuantityNotificationApi\Api;

/**
 * Delete the source item configuration
 *
 * @api
 */
interface DeleteSourceItemsConfigurationInterface
{
    /**
     * Delete multiple source items configuration for low quantity
     *
     * @param \Magento\InventoryApi\Api\Data\SourceItemInterface[] $sourceItems
     * @return void
     */
    public function execute(array $sourceItems): void;
}
