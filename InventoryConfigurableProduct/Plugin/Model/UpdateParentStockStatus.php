<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Plugin\Model;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Inventory\Model\SourceItem\Command\Handler\SourceItemsSaveHandler;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magento\InventoryIndexer\Model\ResourceModel\UpdateLegacyStockStatus;

class UpdateParentStockStatus
{
    /**
     * @var IsSingleSourceModeInterface
     */
    private IsSingleSourceModeInterface $isSingleSourceMode;

    /**
     * @var GetSkusByProductIdsInterface
     */
    private GetSkusByProductIdsInterface $getSkusByProductIds;

    /**
     * @var GetProductIdsBySkusInterface
     */
    private GetProductIdsBySkusInterface $getProductIdsBySkus;

    /**
     * @var Configurable
     */
    private Configurable $configurableType;

    /**
     * @var UpdateLegacyStockStatus
     */
    private UpdateLegacyStockStatus $updateLegacyStockStatus;

    /**
     * @var StockRegistryInterface
     */
    private StockRegistryInterface $stockRegistry;

    /**
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param Configurable $configurableType
     * @param UpdateLegacyStockStatus $updateLegacyStockStatus
     * @param StockRegistryInterface $stockRegistry
     */
    public function __construct(
        IsSingleSourceModeInterface $isSingleSourceMode,
        GetSkusByProductIdsInterface $getSkusByProductIds,
        GetProductIdsBySkusInterface $getProductIdsBySkus,
        Configurable $configurableType,
        UpdateLegacyStockStatus $updateLegacyStockStatus,
        StockRegistryInterface $stockRegistry
    ) {
        $this->isSingleSourceMode = $isSingleSourceMode;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->configurableType = $configurableType;
        $this->updateLegacyStockStatus = $updateLegacyStockStatus;
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * Process configurable product stock status considering source mode.
     *
     * @param SourceItemsSaveHandler $subject
     * @param void $result
     * @param array $sourceItems
     * @return void
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(
        SourceItemsSaveHandler $subject,
        $result,
        array $sourceItems
    ): void {
        if (!$this->isSingleSourceMode->execute()) {
            $productIds = $this->getProductIds($sourceItems);
            $parentIds = $this->configurableType->getParentIdsByChild($productIds);
            $skus = $this->getSkusByProductIds->execute($parentIds);

            $dataForUpdate = [];
            foreach ($parentIds as $parentId) {
                $parentStockItem = $this->stockRegistry->getStockItem($parentId);
                if ($parentStockItem->getIsInStock()) {
                    $dataForUpdate[$skus[$parentId]] = true;
                }
            }
            if (count($dataForUpdate)) {
                $this->updateLegacyStockStatus->execute($dataForUpdate);
            }
        }
    }

    /**
     * Get product ids
     *
     * @param array $sourceItems
     * @return array
     */
    private function getProductIds(array $sourceItems): array
    {
        $productIds = [];
        foreach ($sourceItems as $sourceItem) {
            if ($sourceItem->getStatus()) {
                try {
                    $sku = $sourceItem->getSku();
                    $productIds[$sku] ??= (int) $this->getProductIdsBySkus->execute([$sku])[$sku];
                } catch (NoSuchEntityException $e) {
                    continue;
                }
            }
        }
        return $productIds;
    }
}
