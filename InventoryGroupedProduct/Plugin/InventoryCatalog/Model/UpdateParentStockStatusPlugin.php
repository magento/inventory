<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryGroupedProduct\Plugin\InventoryCatalog\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\InventoryCatalog\Model\UpdateInventory;
use Magento\InventoryCatalog\Model\UpdateInventory\InventoryData;
use Magento\GroupedProduct\Model\Inventory\ChangeParentStockStatus;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;

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
     * @var GetProductIdsBySkusInterface
     */
    private $getProductIdsBySkus;

    /**
     * @param ManagerInterface $messageManager
     * @param ChangeParentStockStatus $changeParentStockStatus
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     */
    public function __construct(
        ManagerInterface $messageManager,
        ChangeParentStockStatus $changeParentStockStatus,
        GetProductIdsBySkusInterface $getProductIdsBySkus
    ) {
        $this->messageManager = $messageManager;
        $this->changeParentStockStatus = $changeParentStockStatus;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
    }

    /**
     * After execute plugin
     *
     * @param UpdateInventory $subject
     * @param mixed $result
     * @param InventoryData $data
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(UpdateInventory $subject, $result, InventoryData $data)
    {
        $skus = $data->getSkus();
        try {
            $productIds = $this->getProductIdsBySkus->execute($skus);
        } catch (NoSuchEntityException $e) {
            $productIds = [];
        }
        try {
            foreach ($productIds as $productId) {
                $this->changeParentStockStatus->execute((int)$productId);
            }
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('Something went wrong while updating the product(s) stock status.')
            );
        }
    }
}
