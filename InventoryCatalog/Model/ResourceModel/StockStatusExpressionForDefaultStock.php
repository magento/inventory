<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\InventoryCatalog\Model\ResourceModel;

use Magento\CatalogInventory\Model\Configuration;
use Magento\CatalogInventory\Model\ResourceModel\Indexer\Stock\StatusExpression\ExpressionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Store\Model\ScopeInterface;
use Zend_Db_Expr;

class StockStatusExpressionForDefaultStock implements ExpressionInterface
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Returns status expressions for MySQL query
     *
     * @ingeritdoc
     * @param AdapterInterface $connection
     * @param bool $isAggregate
     * @return Zend_Db_Expr
     */
    public function getExpression(AdapterInterface $connection, bool $isAggregate): Zend_Db_Expr
    {
        $isManageStock = $this->scopeConfig->isSetFlag(
            Configuration::XML_PATH_MANAGE_STOCK,
            ScopeInterface::SCOPE_STORE
        );
        $stockItemInStockField = $isAggregate ? 'MAX(cisi.is_in_stock)' : 'cisi.is_in_stock';
        $isInStockExpression = $connection->getCheckSql(
            'cisi.is_in_stock = 0',
            0,
            $connection->getCheckSql('css.stock_status IS NOT NULL', 'css.stock_status', $stockItemInStockField)
        );

        if ($isManageStock) {
            $statusExpr = $connection->getCheckSql(
                'cisi.use_config_manage_stock = 0 AND cisi.manage_stock = 0',
                1,
                $isInStockExpression
            );
        } else {
            $statusExpr = $connection->getCheckSql(
                'cisi.use_config_manage_stock = 0 AND cisi.manage_stock = 1',
                $isInStockExpression,
                1
            );
        }
        return $statusExpr;
    }
}
