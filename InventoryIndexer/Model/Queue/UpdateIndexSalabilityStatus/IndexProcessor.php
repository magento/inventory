<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Model\Queue\UpdateIndexSalabilityStatus;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\StateException;
use Magento\InventoryIndexer\Indexer\InventoryIndexer;
use Magento\InventoryIndexer\Model\Queue\ReservationData;
use Magento\InventoryIndexer\Model\ResourceModel\UpdateIsSalable;
use Magento\InventoryMultiDimensionalIndexerApi\Model\Alias;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameBuilder;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexStructureInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;

/**
 * Update 'is salable' index data processor.
 */
class IndexProcessor
{
    /**
     * @var IndexNameBuilder
     */
    private $indexNameBuilder;

    /**
     * @var IndexStructureInterface
     */
    private $indexStructure;

    /**
     * @var IsProductSalableInterface
     */
    private $isProductSalable;

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
     * @param IndexStructureInterface $indexStructure
     * @param GetStockItemDataInterface $getStockItemData
     * @param UpdateIsSalable $updateIsSalable
     * @param IsProductSalableInterface $isProductSalable
     */
    public function __construct(
        IndexNameBuilder $indexNameBuilder,
        IndexStructureInterface $indexStructure,
        GetStockItemDataInterface $getStockItemData,
        UpdateIsSalable $updateIsSalable,
        IsProductSalableInterface $isProductSalable
    ) {
        $this->indexNameBuilder = $indexNameBuilder;
        $this->indexStructure = $indexStructure;
        $this->isProductSalable = $isProductSalable;
        $this->getStockItemData = $getStockItemData;
        $this->updateIsSalable = $updateIsSalable;
    }

    /**
     * Process index for given reservation data and stock.
     *
     * @param ReservationData $reservationData
     * @param int $stockId
     * @return bool[]
     * @throws StateException
     */
    public function execute(ReservationData $reservationData, int $stockId): array
    {
        $mainIndexName = $this->indexNameBuilder
            ->setIndexId(InventoryIndexer::INDEXER_ID)
            ->addDimension('stock_', (string)$reservationData->getStock())
            ->setAlias(Alias::ALIAS_MAIN)
            ->build();
        if (!$this->indexStructure->isExist($mainIndexName, ResourceConnection::DEFAULT_CONNECTION)) {
            $this->indexStructure->create($mainIndexName, ResourceConnection::DEFAULT_CONNECTION);
        }

        $salabilityData = [];
        foreach ($reservationData->getSkus() as $sku) {
            $salabilityData[$sku] = $this->isProductSalable->execute($sku, $reservationData->getStock());
        }

        $dataForUpdate = $this->getDataForUpdate($salabilityData, $stockId);
        $this->updateIsSalable->execute(
            $mainIndexName,
            $dataForUpdate,
            ResourceConnection::DEFAULT_CONNECTION
        );

        return $dataForUpdate;
    }

    /**
     * Build data for index update.
     *
     * @param array $salabilityData
     * @param int $stockId
     *
     * @return bool[] - ['sku' => bool]
     */
    private function getDataForUpdate(array $salabilityData, int $stockId): array
    {
        $data = [];
        foreach ($salabilityData as $sku => $isSalable) {
            $currentStatus = $this->getIndexSalabilityStatus($sku, $stockId);
            if ($isSalable != $currentStatus && $currentStatus !== null) {
                $data[$sku] = $isSalable;
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
