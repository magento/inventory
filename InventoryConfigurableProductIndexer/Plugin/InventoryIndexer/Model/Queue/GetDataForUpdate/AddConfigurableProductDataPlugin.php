<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProductIndexer\Plugin\InventoryIndexer\Model\Queue\GetDataForUpdate;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventoryIndexer\Model\Queue\UpdateIndexSalabilityStatus\IndexProcessor\GetDataForUpdate;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;
use Psr\Log\LoggerInterface;

/**
 * Add configurable data plugin.
 */
class AddConfigurableProductDataPlugin
{
    /**
     * @var Configurable
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
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Configurable $type
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param GetStockItemDataInterface $getStockItemData
     * @param AreProductsSalableInterface $areProductsSalable
     * @param LoggerInterface $logger
     */
    public function __construct(
        Configurable $type,
        GetProductIdsBySkusInterface $getProductIdsBySkus,
        GetSkusByProductIdsInterface $getSkusByProductIds,
        GetStockItemDataInterface $getStockItemData,
        AreProductsSalableInterface $areProductsSalable,
        LoggerInterface $logger
    ) {
        $this->type = $type;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->getStockItemData = $getStockItemData;
        $this->logger = $logger;
        $this->areProductsSalable = $areProductsSalable;
    }

    /**
     * Add configurable product to index data.
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
        $configurableData = [];
        $skus = array_keys($result);
        $childrenIds = $this->getProductIdsBySkus->execute($skus);
        $configurableProductIds = [];
        foreach ($childrenIds as $childId) {
            $configurableProductIds[] = $this->type->getParentIdsByChild($childId);
        }
        $configurableProductIds = array_merge(...$configurableProductIds);
        $configurableSkus = $configurableProductIds ? $this->getSkusByProductIds->execute($configurableProductIds) : [];
        $areConfigurableProductsSalable = $this->areProductsSalable->execute($configurableSkus, $stockId);
        foreach ($areConfigurableProductsSalable as $salableResult) {
            if ($salableResult->isSalable() !== $this->getIndexSalabilityStatus($salableResult->getSku(), $stockId)) {
                $configurableData[$salableResult->getSku()] = $salableResult->isSalable();
            }
        }

        return array_merge($result, $configurableData);
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
