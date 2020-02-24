<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Indexer\SaveHandler\Batch;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;

/**
 * Updater for 'is_in_stock'
 */
class IsInStockUpdater
{
    const XML_PATH_BATCH_SIZE = 'cataloginventory/bulk_operations/batch_size';

    /**
     * @var Batch
     */
    private $batch;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var GetProductIdsBySkusInterface
     */
    private $getProductIdsBySkus;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param Batch $batch
     * @param ResourceConnection $resourceConnection
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     */
    public function __construct(
        Batch $batch,
        ResourceConnection $resourceConnection,
        GetProductIdsBySkusInterface $getProductIdsBySkus,
        ScopeConfigInterface $scopeConfig
    )
    {
        $this->batch = $batch;
        $this->resourceConnection = $resourceConnection;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Update 'is_in_stock' field
     *
     * @param \Traversable $documents
     * @param string $connectionName
     * @return void
     */
    public function execute(\Traversable $documents, string $connectionName): void
    {
        $connection = $this->resourceConnection->getConnection($connectionName);

        foreach ($this->batch->getItems($documents, $this->getBatchSize()) as $batchDocuments) {
            $preparedData = $this->prepareValues($batchDocuments);

            if (array_key_exists(SourceItemInterface::STATUS_IN_STOCK, $preparedData)) {
                $connection->update($this->resourceConnection->getTableName('cataloginventory_stock_item'), [
                    'is_in_stock' => SourceItemInterface::STATUS_IN_STOCK,
                ], [
                    'product_id' . ' IN(?)' => $preparedData[SourceItemInterface::STATUS_IN_STOCK],
                ]);
            }

            if (array_key_exists(SourceItemInterface::STATUS_OUT_OF_STOCK, $preparedData)) {
                $connection->update($this->resourceConnection->getTableName('cataloginventory_stock_item'), [
                    'is_in_stock' => SourceItemInterface::STATUS_OUT_OF_STOCK,
                ], [
                    'product_id' . ' IN(?)' => $preparedData[SourceItemInterface::STATUS_OUT_OF_STOCK],
                ]);
            }
        }
    }

    /**
     * Prepare values
     *
     * @param array $values
     * @return array
     */
    private function prepareValues(array $values): array
    {
        $result = [];
        $productIds = $this->getProductIdsBySkus->execute(array_column($values, 'sku'));

        if (!empty($productIds)) {
            foreach ($values as $item) {
                $result[$item['is_salable']][] = $productIds[$item['sku']];
            }
        }

        return $result;
    }

    /**
     * @return int
     */
    private function getBatchSize(): int
    {
        return (int) max(1, (int) $this->scopeConfig->getValue(self::XML_PATH_BATCH_SIZE));
    }
}
