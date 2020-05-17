<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Model\Queue;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\StateException;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryIndexer\Indexer\InventoryIndexer;
use Magento\InventoryIndexer\Model\ResourceModel\UpdateIsSalable;
use Magento\InventoryMultiDimensionalIndexerApi\Model\Alias;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameBuilder;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexStructureInterface;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Api\Data\IsProductSalableResultInterface;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;

/**
 * Recalculates index items salability status.
 */
class UpdateIndexSalabilityStatus
{
    /**
     * @var IndexNameBuilder
     */
    private $indexNameBuilder;

    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @var IndexStructureInterface
     */
    private $indexStructure;

    /**
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @var GetStockItemDataInterface
     */
    private $getStockItemData;

    /**
     * @var UpdateIsSalable
     */
    private $updateIsSalable;

    /**
     * @param IndexNameBuilder $indexNameBuilder
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param IndexStructureInterface $indexStructure
     * @param AreProductsSalableInterface $areProductsSalable
     * @param GetStockItemDataInterface $getStockItemData
     * @param UpdateIsSalable $updateIsSalable
     */
    public function __construct(
        IndexNameBuilder $indexNameBuilder,
        DefaultStockProviderInterface $defaultStockProvider,
        IndexStructureInterface $indexStructure,
        AreProductsSalableInterface $areProductsSalable,
        GetStockItemDataInterface $getStockItemData,
        UpdateIsSalable $updateIsSalable
    ) {
        $this->indexNameBuilder = $indexNameBuilder;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->indexStructure = $indexStructure;
        $this->areProductsSalable = $areProductsSalable;
        $this->getStockItemData = $getStockItemData;
        $this->updateIsSalable = $updateIsSalable;
    }

    /**
     * @param ReservationData $reservationData
     *
     * @return bool[] - ['sku' => bool]
     * @throws StateException
     */
    public function execute(ReservationData $reservationData): array
    {
        $stockId = $reservationData->getStock();
        $dataForUpdate = [];
        if ($stockId !== $this->defaultStockProvider->getId() && $reservationData->getSkus()) {
            $mainIndexName = $this->indexNameBuilder
                ->setIndexId(InventoryIndexer::INDEXER_ID)
                ->addDimension('stock_', (string)$reservationData->getStock())
                ->setAlias(Alias::ALIAS_MAIN)
                ->build();
            if (!$this->indexStructure->isExist($mainIndexName, ResourceConnection::DEFAULT_CONNECTION)) {
                $this->indexStructure->create($mainIndexName, ResourceConnection::DEFAULT_CONNECTION);
            }
            $salabilityData = $this->areProductsSalable->execute(
                $reservationData->getSkus(),
                $reservationData->getStock()
            );

            $dataForUpdate = $this->getDataForUpdate($salabilityData, $stockId);
            $this->updateIsSalable->execute(
                $mainIndexName,
                $dataForUpdate,
                ResourceConnection::DEFAULT_CONNECTION
            );
        }

        return $dataForUpdate;
    }

    /**
     * @param IsProductSalableResultInterface[] $salabilityData
     * @param int $stockId
     *
     * @return bool[] - ['sku' => bool]
     */
    private function getDataForUpdate(array $salabilityData, int $stockId): array
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
