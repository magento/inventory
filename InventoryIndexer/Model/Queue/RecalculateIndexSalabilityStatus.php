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
use Magento\InventoryIndexer\Indexer\IndexStructure;
use Magento\InventoryIndexer\Indexer\InventoryIndexer;
use Magento\InventoryMultiDimensionalIndexerApi\Model\Alias;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexHandlerInterface;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexNameBuilder;
use Magento\InventoryMultiDimensionalIndexerApi\Model\IndexStructureInterface;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Api\Data\IsProductSalableResultInterface;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;

/**
 * Recalculates index items salability status.
 */
class RecalculateIndexSalabilityStatus
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
     * @var IndexHandlerInterface
     */
    private $indexHandler;

    /**
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @var GetStockItemDataInterface
     */
    private $getStockItemData;

    /**
     * @param IndexNameBuilder $indexNameBuilder
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param IndexStructureInterface $indexStructure
     * @param IndexHandlerInterface $indexHandler
     * @param AreProductsSalableInterface $areProductsSalable
     * @param GetStockItemDataInterface $getStockItemData
     */
    public function __construct(
        IndexNameBuilder $indexNameBuilder,
        DefaultStockProviderInterface $defaultStockProvider,
        IndexStructureInterface $indexStructure,
        IndexHandlerInterface $indexHandler,
        AreProductsSalableInterface $areProductsSalable,
        GetStockItemDataInterface $getStockItemData
    ) {
        $this->indexNameBuilder = $indexNameBuilder;
        $this->defaultStockProvider = $defaultStockProvider;
        $this->indexStructure = $indexStructure;
        $this->indexHandler = $indexHandler;
        $this->areProductsSalable = $areProductsSalable;
        $this->getStockItemData = $getStockItemData;
    }

    /**
     * @param ReservationData $reservationData
     *
     * @return void
     * @throws StateException
     */
    public function execute(ReservationData $reservationData): void
    {
        $stockId = $reservationData->getStock();
        if ($this->defaultStockProvider->getId() === $stockId || !$reservationData->getSkus()) {
            return;
        }

        $mainIndexName = $this->indexNameBuilder
            ->setIndexId(InventoryIndexer::INDEXER_ID)
            ->addDimension('stock_', (string)$reservationData->getStock())
            ->setAlias(Alias::ALIAS_MAIN)
            ->build();
        if (!$this->indexStructure->isExist($mainIndexName, ResourceConnection::DEFAULT_CONNECTION)) {
            $this->indexStructure->create($mainIndexName, ResourceConnection::DEFAULT_CONNECTION);
        }
        $this->indexHandler->saveIndex(
            $mainIndexName,
            $this->getSalabilityData($reservationData->getSkus(), $stockId),
            ResourceConnection::DEFAULT_CONNECTION
        );
    }

    /**
     * @param string[] $skuList
     *
     * @param int $stockId
     *
     * @return \Traversable
     */
    private function getSalabilityData(array $skuList, int $stockId): \Traversable
    {
        $data = array_map(
            function (IsProductSalableResultInterface $isProductSalableResult) use ($stockId): array {
                return [
                    IndexStructure::SKU => $isProductSalableResult->getSku(),
                    IndexStructure::IS_SALABLE => $isProductSalableResult->isSalable(),
                    IndexStructure::QUANTITY => $this->getIndexQty($isProductSalableResult->getSku(), $stockId)
                ];
            },
            $this->areProductsSalable->execute($skuList, $stockId)
        );

        return new \ArrayIterator($data);
    }

    /**
     * Get current index QTY value.
     *
     * @param string $sku
     * @param int $stockId
     *
     * @return float|null
     */
    private function getIndexQty(string $sku, int $stockId): ?float
    {
        try {
            $data = $this->getStockItemData->execute($sku, $stockId);
            $qty = $data ? (float)$data[GetStockItemDataInterface::QUANTITY] : null;
        } catch (LocalizedException $e) {
            $qty = null;
        }

        return $qty;
    }
}
