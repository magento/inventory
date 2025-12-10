<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryLogging\Plugin\Inventory\Model\SourceItem\Command\Handler;

use Magento\Framework\Model\AbstractModel;
use Magento\Inventory\Model\SourceItem\Command\Handler\SourceItemsSaveHandler;
use Magento\Framework\Event\ManagerInterface;

class SourceItemsSaveHandlerPlugin
{
    /**
     * @param ManagerInterface $eventManager
     */
    public function __construct(private readonly ManagerInterface $eventManager)
    {
    }

    /**
     * After plugin for SourceItemsSaveHandler::execute, triggers action logging
     *
     * @param SourceItemsSaveHandler $subject
     * @param mixed $result
     * @param array $sourceItems
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(
        SourceItemsSaveHandler $subject,
        $result,
        array $sourceItems
    ): mixed {
        if (empty($sourceItems)) {
            return $result;
        }

        foreach ($sourceItems as $sourceItem) {
            if (!$sourceItem instanceof AbstractModel) {
                continue;
            }
            $this->eventManager->dispatch('model_save_after', ['object' => $sourceItem]);
        }

        return $result;
    }
}
