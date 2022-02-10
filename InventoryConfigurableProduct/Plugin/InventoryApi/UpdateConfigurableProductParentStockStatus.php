<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Plugin\InventoryApi;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magento\ConfigurableProduct\Model\Inventory\ChangeParentStockStatus;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Update configurable product parent stock status
 *
 * Update product status based on the available stock of the child product
 */
class UpdateConfigurableProductParentStockStatus
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
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param ChangeParentStockStatus $changeParentStockStatus
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        GetProductIdsBySkusInterface $getProductIdsBySkus,
        ChangeParentStockStatus $changeParentStockStatus,
        IsSingleSourceModeInterface $isSingleSourceMode,
        StoreManagerInterface $storeManager
    ) {
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->changeParentStockStatus = $changeParentStockStatus;
        $this->isSingleSourceMode = $isSingleSourceMode;
        $this->storeManager = $storeManager;
    }

    /**
     *  Make configurable product out of stock if all its children out of stock
     *
     * @param SourceItemsSaveInterface $subject
     * @param void $result
     * @param SourceItemInterface[] $sourceItems
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(SourceItemsSaveInterface $subject, $result, array $sourceItems): void
    {
        $productIds = [];
        if ($this->isSingleSourceMode->execute() && $this->storeManager->hasSingleStore()) {
            foreach ($sourceItems as $sourceItem) {
                $sku = $sourceItem->getSku();
                try {
                    $productIds[] = (int)$this->getProductIdsBySkus->execute([$sku])[$sku];
                } catch (NoSuchEntityException $e) {
                    $productIds = [];
                }
            }
            if ($productIds) {
                $this->changeParentStockStatus->execute($productIds);
            }
        }
    }
}
