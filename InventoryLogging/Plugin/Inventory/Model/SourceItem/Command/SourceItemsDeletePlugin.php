<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryLogging\Plugin\Inventory\Model\SourceItem\Command;

use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Inventory\Model\SourceItem\Command\SourceItemsDelete;

class SourceItemsDeletePlugin
{
    /**
     * @param ManagerInterface $eventManager
     */
    public function __construct(private readonly ManagerInterface $eventManager)
    {
    }

    /**
     * After plugin for SourceItemsDelete::execute, triggers action logging
     *
     * @param SourceItemsDelete $subject
     * @param mixed $result
     * @param array $sourceItems
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(SourceItemsDelete $subject, $result, array $sourceItems): void
    {
        if (empty($sourceItems)) {
            return;
        }

        foreach ($sourceItems as $sourceItem) {
            if (!$sourceItem instanceof AbstractModel) {
                continue;
            }
            $this->eventManager->dispatch('model_delete_after', ['object' => $sourceItem]);
        }
    }
}
