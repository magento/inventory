<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Model\Queue\UpdateIndexSalabilityStatus\IndexProcessor;

use Magento\Framework\Exception\LocalizedException;
use Magento\InventorySalesApi\Api\Data\IsProductSalableResultInterface;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;

/**
 * Build data for index update.
 */
class GetDataForUpdate
{
    /**
     * @var GetStockItemDataInterface
     */
    private $getStockItemData;

    /**
     * @param GetStockItemDataInterface $getStockItemData
     */
    public function __construct(GetStockItemDataInterface $getStockItemData)
    {
        $this->getStockItemData = $getStockItemData;
    }

    /**
     * Build data for index update.
     *
     * @param IsProductSalableResultInterface[] $salabilityData
     * @param int $stockId
     * @return bool[] - ['sku' => bool]
     */
    public function execute(array $salabilityData, int $stockId): array
    {
        $data = [];
        foreach ($salabilityData as $isProductSalableResult) {
            $currentStatus = $this->getIndexSalabilityStatus($isProductSalableResult->getSku(), $stockId);
            if ($isProductSalableResult->isSalable() != $currentStatus && $currentStatus !== null) {
                $data[$isProductSalableResult->getSku()] = $isProductSalableResult->isSalable();
            }
        }

        return $data;
    }

    /**
     * Get current index is_salable value.
     *
     * @param string $sku
     * @param int $stockId
     *
     * @return bool|null
     */
    private function getIndexSalabilityStatus(string $sku, int $stockId): ?bool
    {
        try {
            $data = $this->getStockItemData->execute($sku, $stockId);
            $isSalable = $data ? (bool)$data[GetStockItemDataInterface::IS_SALABLE] : false;
        } catch (LocalizedException $e) {
            $isSalable = null;
        }

        return $isSalable;
    }
}
