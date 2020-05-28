<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Model\Queue\UpdateIndexSalabilityStatus;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\StateException;
use Magento\InventoryIndexer\Indexer\InventoryIndexer;
use Magento\InventoryIndexer\Model\Queue\ReservationData;
use Magento\InventoryIndexer\Model\Queue\UpdateIndexSalabilityStatus\IndexProcessor\GetDataForUpdate;
use Magento\InventoryIndexer\Model\ResourceModel\UpdateIsSalable;
use Magento\InventoryMultiDimensionalIndexerApi\Model\Alias;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameBuilder;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexStructureInterface;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
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
     * @param IndexStructureInterface $indexStructure
     * @param AreProductsSalableInterface $areProductsSalable
     * @param GetStockItemDataInterface $getDataForUpdate
     * @param UpdateIsSalable $updateIsSalable
     */
    public function __construct(
        IndexNameBuilder $indexNameBuilder,
        IndexStructureInterface $indexStructure,
        AreProductsSalableInterface $areProductsSalable,
        GetDataForUpdate $getDataForUpdate,
        UpdateIsSalable $updateIsSalable
    ) {
        $this->indexNameBuilder = $indexNameBuilder;
        $this->indexStructure = $indexStructure;
        $this->areProductsSalable = $areProductsSalable;
        $this->getStockItemData = $getDataForUpdate;
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

        return $dataForUpdate;
    }

}
