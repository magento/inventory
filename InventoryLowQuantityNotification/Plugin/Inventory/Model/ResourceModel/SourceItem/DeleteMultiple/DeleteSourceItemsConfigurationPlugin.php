<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryLowQuantityNotification\Plugin\Inventory\Model\ResourceModel\SourceItem\DeleteMultiple;

use Magento\Inventory\Model\ResourceModel\SourceItem\DeleteMultiple as SourceItemDeleteMultiple;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryLowQuantityNotification\Model\ResourceModel\SourceItemConfiguration\DeleteMultiple;

/**
 * Handles the deletion of source item configurations after the associated source items have been removed.
 */
class DeleteSourceItemsConfigurationPlugin
{
    /**
     * @var DeleteMultiple
     */
    private $deleteMultiple;

    /**
     * @param DeleteMultiple $deleteMultiple
     */
    public function __construct(DeleteMultiple $deleteMultiple)
    {
        $this->deleteMultiple = $deleteMultiple;
    }

    /**
     * Delete source items configuration after sources have been deleted.
     *
     * @param SourceItemDeleteMultiple $subject
     * @param void $result
     * @param SourceItemInterface[] $sourceItems
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(SourceItemDeleteMultiple $subject, $result, array $sourceItems): void
    {
        $this->deleteMultiple->execute($sourceItems);
    }
}
