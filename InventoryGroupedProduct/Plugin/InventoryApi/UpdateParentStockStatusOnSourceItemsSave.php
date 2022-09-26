<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryGroupedProduct\Plugin\InventoryApi;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magento\GroupedProduct\Model\Inventory\ChangeParentStockStatus;

/**
 * Update stock status of grouped products on children products source items update
 */
class UpdateParentStockStatusOnSourceItemsSave
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
     * @var IsSingleSourceModeInterface
     */
    private $isSingleSourceMode;

    /**
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param ChangeParentStockStatus $changeParentStockStatus
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     */
    public function __construct(
        GetProductIdsBySkusInterface $getProductIdsBySkus,
        ChangeParentStockStatus $changeParentStockStatus,
        IsSingleSourceModeInterface $isSingleSourceMode
    ) {
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->changeParentStockStatus = $changeParentStockStatus;
        $this->isSingleSourceMode = $isSingleSourceMode;
    }

    /**
     * Update stock status of grouped products on children products source items update
     *
     * @param SourceItemsSaveInterface $subject
     * @param void $result
     * @param SourceItemInterface[] $sourceItems
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(SourceItemsSaveInterface $subject, $result, array $sourceItems): void
    {
        if ($this->isSingleSourceMode->execute()) {
            foreach ($sourceItems as $sourceItem) {
                $sku = $sourceItem->getSku();
                try {
                    $productId = (int)$this->getProductIdsBySkus->execute([$sku])[$sku];
                } catch (NoSuchEntityException $e) {
                    continue;
                }
                $this->changeParentStockStatus->execute($productId);
            }
        }
    }
}
