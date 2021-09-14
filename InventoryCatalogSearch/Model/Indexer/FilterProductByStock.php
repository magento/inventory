<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogSearch\Model\Indexer;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\Store\Api\StoreRepositoryInterface;

/**
 * Filter products by stock status
 */
class FilterProductByStock
{
    /**
     * @var DefaultStockProviderInterface
     */
    private $defaultStockProvider;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var StockByWebsiteIdResolverInterface
     */
    private $stockByWebsiteIdResolver;

    /**
     * @var StockIndexTableNameResolverInterface
     */
    private $stockIndexTableNameResolver;

    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * @var array
     */
    private $selectModifiersPool;

    /**
     * @param DefaultStockProviderInterface $defaultStockProvider
     * @param ResourceConnection $resourceConnection
     * @param StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver
     * @param StockIndexTableNameResolverInterface $stockIndexTableNameResolver
     * @param StoreRepositoryInterface $storeRepository
     * @param array $selectModifiersPool
     */
    public function __construct(
        DefaultStockProviderInterface $defaultStockProvider,
        ResourceConnection $resourceConnection,
        StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver,
        StockIndexTableNameResolverInterface $stockIndexTableNameResolver,
        StoreRepositoryInterface $storeRepository,
        array $selectModifiersPool = []
    )
    {
        $this->defaultStockProvider = $defaultStockProvider;
        $this->resourceConnection = $resourceConnection;
        $this->stockByWebsiteIdResolver = $stockByWebsiteIdResolver;
        $this->stockIndexTableNameResolver = $stockIndexTableNameResolver;
        $this->storeRepository = $storeRepository;
        $this->selectModifiersPool = $selectModifiersPool;
    }

    /**
     * Return filtered product by stock status for product indexer
     *
     * @param Select $select
     * @param int $storeId
     * @return Select
     * @throws NoSuchEntityException
     */
    public function execute(Select $select, int $storeId): Select
    {
        $store = $this->storeRepository->getById($storeId);
        try {
            $stock = $this->stockByWebsiteIdResolver->execute((int)$store->getWebsiteId());
        } catch (NoSuchEntityException $exception) {
            return $select;
        }

        $stockId = $stock->getStockId();
        $stockTable = $this->stockIndexTableNameResolver->execute($stockId);
        $connection = $this->resourceConnection->getConnection();

        if ($this->defaultStockProvider->getId() === $stockId ||
            !$connection->isTableExists($stockTable)) {
            return $select;
        }

        $select->joinInner(
            ['stock' => $stockTable],
            'e.sku = stock.sku',
            []
        );

        $select->where('stock.is_salable = ?', 1);
        $this->applySelectModifiers($select, $stockTable);

        return $select;
    }

    /**
     * Applying filters to select via select modifiers
     *
     * @param Select $select
     * @param string $stockTable
     * @return void
     */
    private function applySelectModifiers(Select $select, string $stockTable): void
    {
        foreach ($this->selectModifiersPool as $selectModifier) {
            $selectModifier->modify($select, $stockTable);
        }
    }
}
