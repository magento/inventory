<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalogSearch\Model\Adapter\Mysql\Aggregation;

use Magento\CatalogSearch\Model\Adapter\Mysql\Aggregation\StockSelectProviderInterface;
use Magento\CatalogSearch\Model\Search\FilterMapper\StockStatusFilterInterface;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\ScopeResolverInterface;
use Magento\Framework\DB\Select;
use Magento\CatalogInventory\Model\Stock;
use Magento\Store\Model\ScopeInterface;

/**
 * MSI implementation for StockSelectProviderInterface
 */
class StockSelectProvider implements StockSelectProviderInterface
{
    /**
     * @var Resource
     */
    private $resource;

    /**
     * @var ScopeResolverInterface
     */
    private $scopeResolver;

    /**
     * @var StockStatusFilterInterface
     */
    private $stockStatusFilter;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ResourceConnection $resource
     * @param ScopeResolverInterface $scopeResolver
     * @param StockStatusFilterInterface $stockStatusFilter
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ResourceConnection $resource,
        ScopeResolverInterface $scopeResolver,
        StockStatusFilterInterface $stockStatusFilter,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->resource = $resource;
        $this->scopeResolver = $scopeResolver;
        $this->stockStatusFilter = $stockStatusFilter;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @inheritdoc
     */
    public function get(int $currentScope, AbstractAttribute $attribute, Select $select): Select
    {
        $connection = $this->resource->getConnection();
        $subSelect = $this->getSubSelect($currentScope, $attribute, $select);
        $parentSelect = $connection->select();
        $parentSelect->from(['main_table' => $subSelect], ['main_table.value']);
        $select = $parentSelect;
        return $select;
    }

    /**
     * @param int $currentScope
     * @param AbstractAttribute $attribute
     * @param Select $select
     * @return Select
     * @throws \InvalidArgumentException
     */
    private function getSubSelect(int $currentScope, AbstractAttribute $attribute, Select $select): Select
    {
        $currentScopeId = $this->scopeResolver->getScope($currentScope)
            ->getId();

        $table = $this->resource->getTableName(
            'catalog_product_index_eav' . ($attribute->getBackendType() === 'decimal' ? '_decimal' : '')
        );

        $subSelect = $select;
        $subSelect->from(['main_table' => $table], ['main_table.entity_id', 'main_table.value'])
            ->distinct()
            ->where('main_table.attribute_id = ?', $attribute->getAttributeId())
            ->where('main_table.store_id = ? ', $currentScopeId);

        $subSelect = $this->stockStatusFilter->apply(
            $subSelect,
            Stock::STOCK_IN_STOCK,
            StockStatusFilterInterface::FILTER_JUST_SUB_PRODUCTS,
            $this->isShowOutOfStock()
        );

        return $subSelect;
    }

    /**
     * @return bool
     */
    private function isShowOutOfStock(): bool
    {
        return $this->scopeConfig->isSetFlag(
            'cataloginventory/options/show_out_of_stock',
            ScopeInterface::SCOPE_STORE
        );
    }
}
