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
use Magento\InventoryIndexer\Model\Queue\GetSalabilityDataForUpdate;
use Magento\InventoryIndexer\Model\Queue\ReservationData;
use Magento\InventoryIndexer\Model\ResourceModel\UpdateIsSalable;
use Magento\InventoryMultiDimensionalIndexerApi\Model\Alias;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameBuilder;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexStructureInterface;

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
     * @var UpdateIsSalable
     */
    private $updateIsSalable;

    /**
     * @var GetSalabilityDataForUpdate
     */
    private $getSalabilityDataForUpdate;

    /**
     * @param IndexNameBuilder $indexNameBuilder
     * @param IndexStructureInterface $indexStructure
     * @param UpdateIsSalable $updateIsSalable
     * @param GetSalabilityDataForUpdate $getSalabilityDataForUpdate
     */
    public function __construct(
        IndexNameBuilder $indexNameBuilder,
        IndexStructureInterface $indexStructure,
        UpdateIsSalable $updateIsSalable,
        GetSalabilityDataForUpdate $getSalabilityDataForUpdate
    ) {
        $this->indexNameBuilder = $indexNameBuilder;
        $this->indexStructure = $indexStructure;
        $this->updateIsSalable = $updateIsSalable;
        $this->getSalabilityDataForUpdate = $getSalabilityDataForUpdate;
    }

    /**
     * Process index for given reservation data and stock.
     *
     * @param ReservationData $reservationData
     * @param int $stockId
     * @return bool[]
     * @throws StateException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
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

        $dataForUpdate = $this->getSalabilityDataForUpdate->execute($reservationData);
        $this->updateIsSalable->execute(
            $mainIndexName,
            $dataForUpdate,
            ResourceConnection::DEFAULT_CONNECTION
        );

        return $dataForUpdate;
    }
}
