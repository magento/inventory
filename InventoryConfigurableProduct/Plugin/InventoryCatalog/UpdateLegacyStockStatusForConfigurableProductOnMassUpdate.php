<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Plugin\InventoryCatalog;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\InventoryCatalog\Model\UpdateInventory;
use Magento\InventoryCatalog\Model\UpdateInventory\InventoryData;
use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\CatalogInventory\Model\Stock;
use Magento\InventoryConfigurableProduct\Model\IsProductSalableCondition\IsConfigurableProductChildrenSalable;
use Magento\InventoryIndexer\Model\ResourceModel\UpdateLegacyStockStatus;

/**
 * Update configurable products legacy stock statuses based on children stock status on mass update
 */
class UpdateLegacyStockStatusForConfigurableProductOnMassUpdate
{
    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var IsConfigurableProductChildrenSalable
     */
    private IsConfigurableProductChildrenSalable $isConfigurableProductChildrenSalable;

    /**
     * @var GetProductTypesBySkusInterface
     */
    private GetProductTypesBySkusInterface $getProductTypeBySku;

    /**
     * @var UpdateLegacyStockStatus
     */
    private UpdateLegacyStockStatus $updateLegacyStockStatus;

    /**
     * @param SerializerInterface $serializer
     * @param IsConfigurableProductChildrenSalable $isConfigurableProductChildrenSalable
     * @param GetProductTypesBySkusInterface $getProductTypeBySku
     * @param UpdateLegacyStockStatus $updateLegacyStockStatus
     */
    public function __construct(
        SerializerInterface $serializer,
        IsConfigurableProductChildrenSalable $isConfigurableProductChildrenSalable,
        GetProductTypesBySkusInterface $getProductTypeBySku,
        UpdateLegacyStockStatus $updateLegacyStockStatus
    ) {
        $this->isConfigurableProductChildrenSalable = $isConfigurableProductChildrenSalable;
        $this->serializer = $serializer;
        $this->getProductTypeBySku = $getProductTypeBySku;
        $this->updateLegacyStockStatus = $updateLegacyStockStatus;
    }

    /**
     * Update configurable products legacy stock statuses based on children stock status
     *
     * @param UpdateInventory $subject
     * @param mixed $result
     * @param InventoryData $data
     * @return void
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(
        UpdateInventory $subject,
        $result,
        InventoryData $data
    ): void {
        $skus = $data->getSkus();
        $inventoryData = $this->serializer->unserialize($data->getData());
        $stockId = Stock::DEFAULT_STOCK_ID;
        if (isset($inventoryData[StockItemInterface::IS_IN_STOCK])
            && (int) $inventoryData[StockItemInterface::IS_IN_STOCK] === Stock::STOCK_IN_STOCK
        ) {
            $productTypesBySku = $this->getProductTypeBySku->execute($skus);
            $dataForUpdate = [];
            foreach ($productTypesBySku as $sku => $productType) {
                if ($productType === Configurable::TYPE_CODE) {
                    $dataForUpdate[$sku] = $this->isConfigurableProductChildrenSalable->execute($sku, $stockId);
                }
            }
            if ($dataForUpdate) {
                $this->updateLegacyStockStatus->execute($dataForUpdate);
            }
        }
    }
}
