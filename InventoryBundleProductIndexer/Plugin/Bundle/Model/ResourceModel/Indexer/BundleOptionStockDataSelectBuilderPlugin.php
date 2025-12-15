<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProductIndexer\Plugin\Bundle\Model\ResourceModel\Indexer;

use Magento\Bundle\Model\ResourceModel\Indexer\BundleOptionStockDataSelectBuilder;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryIndexer\Indexer\Stock\ReservationsIndexTable;

class BundleOptionStockDataSelectBuilderPlugin
{
    /**
     * @param ResourceConnection $resourceConnection
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param ReservationsIndexTable $reservationsIndexTable
     */
    public function __construct(
        private readonly ResourceConnection $resourceConnection,
        private readonly DefaultStockProviderInterface $defaultStockProvider,
        private readonly ReservationsIndexTable $reservationsIndexTable,
    ) {
    }

    /**
     * Join reservations table to the select used for bundle options stock status indexation.
     *
     * @param BundleOptionStockDataSelectBuilder $subject
     * @param Select $select
     * @param string $idxTable
     * @return Select
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterBuildSelect(
        BundleOptionStockDataSelectBuilder $subject,
        Select $select,
        string $idxTable
    ): Select {
        $reservationsTableName = $this->reservationsIndexTable->getTableName($this->defaultStockProvider->getId());
        $select->joinLeft(
            ['reservations' => $this->resourceConnection->getTableName($reservationsTableName)],
            'reservations.sku = e.sku',
            []
        );

        return $select;
    }
}
