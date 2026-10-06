<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Model\Queue;

use Magento\Framework\App\ObjectManager;
use Magento\InventoryCatalogApi\Model\GetParentSkusOfChildrenSkusInterface;
use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Model\GetStockItemsDataInterface;

/**
 * Get stock status changes for given reservation.
 */
class GetSalabilityDataForUpdate
{
    /**
     * @var GetProductTypesBySkusInterface
     */
    private readonly GetProductTypesBySkusInterface $getProductTypesBySkus;

    /**
     * @param GetParentSkusOfChildrenSkusInterface $getParentSkusOfChildrenSkus
     * @param AreProductsSalableInterface $areProductsSalable
     * @param GetStockItemsDataInterface $getStockItemsData
     * @param GetProductTypesBySkusInterface|null $getProductTypesBySkus
     */
    public function __construct(
        private readonly GetParentSkusOfChildrenSkusInterface $getParentSkusOfChildrenSkus,
        private readonly AreProductsSalableInterface $areProductsSalable,
        private readonly GetStockItemsDataInterface $getStockItemsData,
        ?GetProductTypesBySkusInterface $getProductTypesBySkus = null
    ) {
        $this->getProductTypesBySkus = $getProductTypesBySkus
            ?: ObjectManager::getInstance()->get(GetProductTypesBySkusInterface::class);
    }

    /**
     * Get stock status changes for given reservation.
     *
     * @param ReservationData $reservationData
     * @return array<string, bool> - ['sku' => bool]
     */
    public function execute(ReservationData $reservationData): array
    {
        $stockId = $reservationData->getStock();
        $existingSkus = $this->getExistingSkus($reservationData->getSkus());
        if (!$existingSkus) {
            return [];
        }

        $parentSkusOfChildrenSkus = $this->getParentSkusOfChildrenSkus->execute($existingSkus);
        $skus = array_values(array_unique(array_merge($existingSkus, ...array_values($parentSkusOfChildrenSkus))));
        $salabilityData = $this->areProductsSalable->execute($skus, $stockId);
        $currentStatuses = $this->getStockItemsData->execute($skus, $stockId) ?? [];

        $data = [];
        foreach ($salabilityData as $isProductSalableResult) {
            $currentStatus = (bool)($currentStatuses[$isProductSalableResult->getSku()]['is_salable'] ?? false);
            if ($isProductSalableResult->isSalable() !== $currentStatus) {
                $data[$isProductSalableResult->getSku()] = $isProductSalableResult->isSalable();
            }
        }

        return $data;
    }

    /**
     * Filter existing SKUs only.
     *
     * @param string[] $skus
     * @return string[]
     */
    private function getExistingSkus(array $skus): array
    {
        if (!$skus) {
            return [];
        }

        $productTypesBySkus = $this->getProductTypesBySkus->execute($skus);
        $existingSkus = [];

        foreach ($skus as $sku) {
            if (($productTypesBySkus[$sku] ?? null) !== null) {
                $existingSkus[] = $sku;
            }
        }

        return array_values(array_unique($existingSkus));
    }
}
