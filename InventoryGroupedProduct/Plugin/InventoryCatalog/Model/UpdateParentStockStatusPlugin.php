<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryGroupedProduct\Plugin\InventoryCatalog\Model;

use Magento\Framework\Message\ManagerInterface;
use Magento\InventoryCatalog\Model\UpdateInventory;
use Magento\GroupedProduct\Model\Inventory\ChangeParentStockStatus;

/**
 * Disable Source items management for grouped product type.
 */
class UpdateParentStockStatusPlugin
{
    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var ChangeParentStockStatus
     */
    private $changeParentStockStatus;

    /**
     * @param ManagerInterface $messageManager
     * @param ChangeParentStockStatus $changeParentStockStatus
     */
    public function __construct(
        ManagerInterface $messageManager,
        ChangeParentStockStatus $changeParentStockStatus
    ) {
        $this->messageManager = $messageManager;
        $this->changeParentStockStatus = $changeParentStockStatus;
    }

    /**
     * After execute plugin
     *
     * @param UpdateInventory $subject
     * @param array $productIds
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(UpdateInventory $subject, array $productIds)
    {
        try {
            foreach ($productIds as $productId) {
                $this->changeParentStockStatus->processStockForParent((int)$productId);
            }
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('Something went wrong while updating the product(s) stock status.')
            );
        }
    }
}
