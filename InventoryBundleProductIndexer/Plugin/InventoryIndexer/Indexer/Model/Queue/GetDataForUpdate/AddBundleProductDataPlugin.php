<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Plugin\InventoryIndexer\Model\Queue\GetDataForUpdate;

use Magento\Bundle\Model\Product\Type;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventoryIndexer\Model\Queue\UpdateIndexSalabilityStatus\IndexProcessor\GetDataForUpdate;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;
use Psr\Log\LoggerInterface;

/**
 * Add bundle product data plugin.
 */
class AddBundleProductDataPlugin
{
    /**
     * @var Type
     */
    private $type;

    /**
     * @var GetProductIdsBySkusInterface
     */
    private $getProductIdsBySkus;

    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @var GetStockItemDataInterface
     */
    private $getStockItemData;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Type $type
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param GetStockItemDataInterface $getStockItemData
     * @param LoggerInterface $logger
     */
    public function __construct(
        Type $type,
        GetProductIdsBySkusInterface $getProductIdsBySkus,
        GetSkusByProductIdsInterface $getSkusByProductIds,
        GetStockItemDataInterface $getStockItemData,
        LoggerInterface $logger
    ) {
        $this->type = $type;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->getStockItemData = $getStockItemData;
        $this->logger = $logger;
    }

    /**
     * Add bundle product data to index data.
     *
     * @param GetDataForUpdate $subject
     * @param array $result
     * @param array $salabilityData
     * @param int $stockId
     * @return array
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(GetDataForUpdate $subject, array $result, array $salabilityData, int $stockId): array
    {
        $bundleData = [];
        $skus = array_keys($result);
        $childrenIds = $this->getProductIdsBySkus->execute($skus);
        $bundleProductsIds = [];
        foreach ($childrenIds as $childId) {
            $bundleProductsIds[] = $this->type->getParentIdsByChild($childId);
        }

        $bundleSkus = $this->getSkusByProductIds->execute($bundleProductsIds);
        foreach ($bundleSkus as $bundleSku) {
            $bundleData[$bundleSku] = $this->getIndexSalabilityStatus($bundleSku, $stockId);
        }

        return array_merge($result, $bundleData);
    }

    /**
     * Get current index is_salable value.
     *
     * @param string $sku
     * @param int $stockId
     * @return bool|null
     */
    private function getIndexSalabilityStatus(string $sku, int $stockId): ?bool
    {
        try {
            $data = $this->getStockItemData->execute($sku, $stockId);
            $isSalable = $data ? (bool)$data[GetStockItemDataInterface::IS_SALABLE] : false;
        } catch (LocalizedException $e) {
            $this->logger->error($e->getLogMessage());
            return null;
        }

        return $isSalable;
    }
}
