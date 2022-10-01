<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryGroupedProduct\Plugin\InventoryApi;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Inventory\Model\SourceItem\Command\DecrementSourceItemQty;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\GroupedProduct\Model\Inventory\ChangeParentStockStatus;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;

/**
 * Update group product stock status in legacy stock after decrement quantity of child stock item
 */
class UpdateParentStockStatusInLegacyStockPlugin
{
    /**
     * @var ChangeParentStockStatus
     */
    private $changeParentStockStatus;

    /**
     * @var GetProductIdsBySkusInterface
     */
    private $getProductIdsBySkus;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var IsSingleSourceModeInterface
     */
    private $isSingleSourceMode;

    /**
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param ChangeParentStockStatus $changeParentStockStatus
     * @param ManagerInterface $messageManager
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     */
    public function __construct(
        GetProductIdsBySkusInterface $getProductIdsBySkus,
        ChangeParentStockStatus $changeParentStockStatus,
        ManagerInterface $messageManager,
        IsSingleSourceModeInterface $isSingleSourceMode
    ) {
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->changeParentStockStatus = $changeParentStockStatus;
        $this->messageManager = $messageManager;
        $this->isSingleSourceMode = $isSingleSourceMode;
    }

    /**
     *  Make group product out of stock if all its children out of stock
     *
     * @param DecrementSourceItemQty $subject
     * @param void $result
     * @param SourceItemInterface[] $sourceItemDecrementData
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws NoSuchEntityException
     */
    public function afterExecute(DecrementSourceItemQty $subject, $result, array $sourceItemDecrementData): void
    {
        if (!$this->isSingleSourceMode->execute()) {
            return;
        }

        $productIds = [];
        $sourceItems = array_column($sourceItemDecrementData, 'source_item');
        foreach ($sourceItems as $sourceItem) {
            $sku = $sourceItem->getSku();
            $productIds[] = (int)$this->getProductIdsBySkus->execute([$sku])[$sku];
        }
        try {
            if ($productIds) {
                foreach ($productIds as $productId) {
                    $this->changeParentStockStatus->execute((int)$productId);
                }
            }
        } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage(
                    $e,
                    __('Something went wrong while updating the product(s) stock status.')
                );
        }
    }
}
