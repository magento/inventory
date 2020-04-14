<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryLowQuantityNotification\Plugin\Inventory\Model\ResourceModel\SourceItem\DeleteMultiple;

use Magento\Inventory\Model\ResourceModel\SourceItem\DeleteMultiple as SourceItemDeleteMultiple;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryLowQuantityNotification\Model\ResourceModel\SourceItemConfiguration\DeleteMultiple;

/**
 * Delete source items configuration plugin.
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
