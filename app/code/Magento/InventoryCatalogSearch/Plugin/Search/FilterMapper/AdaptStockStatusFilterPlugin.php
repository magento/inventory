<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogSearch\Plugin\Search\FilterMapper;

use InvalidArgumentException;
use Magento\CatalogSearch\Model\Search\FilterMapper\StockStatusFilter;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Search\Adapter\Mysql\ConditionManager;
use Magento\InventoryCatalog\Model\DefaultStockProvider;
use Magento\InventoryIndexer\Indexer\IndexStructure;
use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Adapt stock status filter to multi stocks
 */
class AdaptStockStatusFilterPlugin
{
    /**
     * @var ConditionManager
     */
    private $conditionManager;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var StockIndexTableNameResolverInterface
     */
    private $stockIndexTableNameResolver;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var DefaultStockProvider
     */
    private $defaultStockProvider;

    /**
     * @param ConditionManager $conditionManager
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     * @param StockIndexTableNameResolverInterface $stockIndexTableNameResolver
     * @param ResourceConnection $resourceConnection
     * @param DefaultStockProvider $defaultStockProvider
     */
    public function __construct(
        ConditionManager $conditionManager,
        StoreManagerInterface $storeManager,
        StockResolverInterface $stockResolver,
        StockIndexTableNameResolverInterface $stockIndexTableNameResolver,
        ResourceConnection $resourceConnection,
        DefaultStockProvider $defaultStockProvider
    ) {
        $this->conditionManager = $conditionManager;
        $this->storeManager = $storeManager;
        $this->stockResolver = $stockResolver;
        $this->stockIndexTableNameResolver = $stockIndexTableNameResolver;
        $this->resourceConnection = $resourceConnection;
        $this->defaultStockProvider = $defaultStockProvider;
    }

    /**
     * @param StockStatusFilter $subject
     * @param callable $proceed
     * @param Select $select
     * @param array $stockValues
     * @param string $type
     * @param bool $showOutOfStockFlag
     * @return Select
     * @throws \InvalidArgumentException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundApply(
        StockStatusFilter $subject,
        callable $proceed,
        Select $select,
        $stockValues,
        $type,
        $showOutOfStockFlag
    ) {
        $website = $this->storeManager->getWebsite();
        $stock = $this->stockResolver->get(SalesChannelInterface::TYPE_WEBSITE, $website->getCode());
        $stockId = (int)$stock->getStockId();

        if ($this->defaultStockProvider->getId() === $stockId) {
            $proceed($select, $stockValues, $type, $showOutOfStockFlag);
        } else {
            if ($type !== StockStatusFilter::FILTER_JUST_ENTITY
                && $type !== StockStatusFilter::FILTER_ENTITY_AND_SUB_PRODUCTS
            ) {
                throw new InvalidArgumentException('Invalid filter type: ' . $type);
            }

            $stockTableName = $this->getStockTableName($stockId);
            $mainTableAlias = $this->extractTableAliasFromSelect($select);

            $this->addProductEntityJoin($select, $mainTableAlias);
            $this->addInventoryStockJoin($select, $showOutOfStockFlag, $stockTableName);

            if ($type === StockStatusFilter::FILTER_ENTITY_AND_SUB_PRODUCTS) {
                $this->addSubProductEntityJoin($select, $mainTableAlias);
                $this->addSubProductInventoryStockJoin($select, $showOutOfStockFlag, $stockTableName);
            }
        }

        return $select;
    }

    /**
     * @param Select $select
     * @param string $mainTableAlias
     * @return void
     */
    private function addProductEntityJoin(Select $select, string $mainTableAlias): void
    {
        $select->joinInner(
            ['product' => $this->resourceConnection->getTableName('catalog_product_entity')],
            sprintf('product.entity_id = %s.entity_id', $mainTableAlias),
            []
        );
    }

    /**
     * @param Select $select
     * @param string $mainTableAlias
     * @return void
     */
    private function addSubProductEntityJoin(Select $select, string $mainTableAlias): void
    {
        $select->joinInner(
            ['sub_product' => $this->resourceConnection->getTableName('catalog_product_entity')],
            sprintf('sub_product.entity_id = %s.source_id', $mainTableAlias),
            []
        );
    }

    /**
     * @param Select $select
     * @param bool $showOutOfStockFlag
     * @param string $stockTableName
     * @return void
     */
    private function addInventoryStockJoin(Select $select, bool $showOutOfStockFlag, string $stockTableName): void
    {
        $select->joinInner(
            ['stock_index' => $stockTableName],
            'stock_index.sku = product.sku',
            []
        );
        if ($showOutOfStockFlag === false) {
            $condition = $this->conditionManager->generateCondition('stock_index.'. IndexStructure::IS_SALABLE, '=', 1);
            $select->where($condition);
        }
    }

    /**
     * @param Select $select
     * @param bool $showOutOfStockFlag
     * @param string $stockTableName
     * @return void
     */
    private function addSubProductInventoryStockJoin(
        Select $select,
        bool $showOutOfStockFlag,
        string $stockTableName
    ): void {
        $select->joinInner(
            ['sub_product_stock_index' => $stockTableName],
            'sub_product_stock_index.sku = sub_product.sku',
            []
        );
        if ($showOutOfStockFlag === false) {
            $condition = $this->conditionManager
                ->generateCondition('sub_product_stock_index.' . IndexStructure::IS_SALABLE, '=', 1);
            $select->where($condition);
        }
    }

    /**
     * Extracts alias for table that is used in FROM clause in Select
     *
     * @param Select $select
     * @return string|null
     */
    private function extractTableAliasFromSelect(Select $select) : ?string
    {
        $fromArr = array_filter(
            $select->getPart(Select::FROM),
            function ($fromPart) {
                return $fromPart['joinType'] === Select::FROM;
            }
        );

        return $fromArr ? array_keys($fromArr)[0] : null;
    }

    /**
     * @param int $stockId
     *
     * @return string
     */
    private function getStockTableName(int $stockId): string
    {
        $tableName = $this->stockIndexTableNameResolver->execute($stockId);

        return $this->resourceConnection->getTableName($tableName);
    }
}
