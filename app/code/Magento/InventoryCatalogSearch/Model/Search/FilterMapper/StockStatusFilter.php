<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogSearch\Model\Search\FilterMapper;

use Magento\CatalogSearch\Model\Search\FilterMapper\StockStatusFilterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Search\Adapter\Mysql\ConditionManager;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\MultiDimensionalIndex\Alias;
use Magento\Framework\MultiDimensionalIndex\IndexNameBuilder;
use Magento\Framework\MultiDimensionalIndex\IndexNameResolverInterface;
/**
 *
 */
class StockStatusFilter implements StockStatusFilterInterface
{
    /**
     * Stock table names and aliases
     */
    const TABLE_ALIAS_PRODUCT =  'product';
    const TABLE_ALIAS_SUB_PRODUCT =  'sub_product';
    const TABLE_ALIAS_STOCK_INDEX =  'stock_index';
    const TABLE_ALIAS_SUB_PRODUCT_STOCK_INDEX =  'sub_product_stock_index';

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
     * @var IndexNameBuilder
     */
    private $indexNameBuilder;

    /**
     * @var IndexNameResolverInterface
     */
    private $indexNameResolver;

    /**
     * @param ConditionManager $conditionManager
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     * @param IndexNameBuilder $indexNameBuilder
     * @param IndexNameResolverInterface $indexNameResolver
     */
    public function __construct(
        ConditionManager $conditionManager,
        StoreManagerInterface $storeManager,
        StockResolverInterface $stockResolver,
        IndexNameBuilder $indexNameBuilder,
        IndexNameResolverInterface $indexNameResolver
    ) {
        $this->conditionManager = $conditionManager;
        $this->storeManager = $storeManager;
        $this->stockResolver = $stockResolver;
        $this->indexNameBuilder = $indexNameBuilder;
        $this->indexNameResolver = $indexNameResolver;
    }

    /**
     * Adds filter by stock status to base select
     *
     * @param Select $select
     * @param mixed $stockValues
     * @param string $type
     * @param bool $showOutOfStockFlag
     *
     * @return Select
     *
     * @throws LocalizedException
     * @throws \InvalidArgumentException
     */
    public function apply(Select $select, $stockValues, $type, $showOutOfStockFlag)
    {
        if (!$this->isValidType($type)) {
            throw new \InvalidArgumentException(sprintf('Invalid filter type: %s', $type));
        }

        $select = clone $select;
        $mainTableAlias = $this->extractTableAliasFromSelect($select);

        if ($type === StockStatusFilterInterface::FILTER_JUST_ENTITY ||
            $type === StockStatusFilterInterface::FILTER_ENTITY_AND_SUB_PRODUCTS
        ) {
            $this->addProductEntityJoin($select, $mainTableAlias);
            $this->addInventoryStockJoin($select, $showOutOfStockFlag);
        }

        if ($type === StockStatusFilterInterface::FILTER_JUST_SUB_PRODUCTS ||
            $type === StockStatusFilterInterface::FILTER_ENTITY_AND_SUB_PRODUCTS
        ) {
            $this->addSubProductEntityJoin($select, $mainTableAlias);
            $this->addSubProductInventoryStockJoin($select, $showOutOfStockFlag);
        }

        return $select;
    }

    /**
     * @param string $type
     * @return bool
     */
    private function isValidType($type): bool
    {
        return \in_array($type, [
            static::FILTER_JUST_SUB_PRODUCTS,
            static::FILTER_JUST_ENTITY,
            static::FILTER_ENTITY_AND_SUB_PRODUCTS
        ], true);
    }

    /**
     * @param Select $select
     * @param string $mainTableAlias
     */
    private function addProductEntityJoin(Select $select, $mainTableAlias)
    {
        $select->joinInner(
            [static::TABLE_ALIAS_PRODUCT => 'catalog_product_entity'],
            sprintf('%s.entity_id = %s.entity_id', static::TABLE_ALIAS_PRODUCT, $mainTableAlias),
            []
        );
    }

    /**
     * @param Select $select
     * @param string $mainTableAlias
     */
    private function addSubProductEntityJoin(Select $select, $mainTableAlias)
    {
        $select->joinInner(
            [static::TABLE_ALIAS_SUB_PRODUCT => 'catalog_product_entity'],
            sprintf('%s.entity_id = %s.source_id', static::TABLE_ALIAS_SUB_PRODUCT, $mainTableAlias),
            []
        );
    }

    /**
     * @param Select $select
     * @param bool $showOutOfStockFlag
     * @throws LocalizedException
     */
    private function addInventoryStockJoin(Select $select, $showOutOfStockFlag)
    {
        $select->joinInner(
            [static::TABLE_ALIAS_STOCK_INDEX => $this->getStockTableName()],
            sprintf(
                '%s.sku = %s.sku',
                static::TABLE_ALIAS_STOCK_INDEX,
                static::TABLE_ALIAS_PRODUCT
            ),
            []
        );

        if ($showOutOfStockFlag === false) {
            $select->where($this->conditionManager->generateCondition(
                sprintf('%s.quantity', static::TABLE_ALIAS_STOCK_INDEX),
                '>',
                0
            ));
        }
    }

    /**
     * @param Select $select
     * @param bool $showOutOfStockFlag
     * @throws LocalizedException
     */
    private function addSubProductInventoryStockJoin(Select $select, $showOutOfStockFlag)
    {
        $select->joinInner(
            [static::TABLE_ALIAS_SUB_PRODUCT_STOCK_INDEX => $this->getStockTableName()],
            sprintf(
                '%s.sku = %s.sku',
                static::TABLE_ALIAS_SUB_PRODUCT_STOCK_INDEX,
                static::TABLE_ALIAS_SUB_PRODUCT
            ),
            []
        );

        if ($showOutOfStockFlag === false) {
            $select->where($this->conditionManager->generateCondition(
                sprintf('%s.quantity', static::TABLE_ALIAS_SUB_PRODUCT_STOCK_INDEX),
                '>',
                0
            ));
        }
    }

    /**
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getStockTableName(): string
    {
        $website = $this->storeManager->getWebsite();
        $stock = $this->stockResolver->get(
            SalesChannelInterface::TYPE_WEBSITE,
            $website->getCode()
        );

        $indexName = $this->indexNameBuilder
            ->setIndexId('inventory_stock')
            ->addDimension('stock_', (string)$stock->getStockId())
            ->setAlias(Alias::ALIAS_MAIN)
            ->build();

        return  $this->indexNameResolver->resolveName($indexName);
    }

    /**
     * Extracts alias for table that is used in FROM clause in Select
     *
     * @param Select $select
     * @return string|null
     */
    private function extractTableAliasFromSelect(Select $select)
    {
        $fromArr = array_filter(
            $select->getPart(Select::FROM),
            function ($fromPart) {
                return $fromPart['joinType'] === Select::FROM;
            }
        );

        return $fromArr ? array_keys($fromArr)[0] : null;
    }
}
