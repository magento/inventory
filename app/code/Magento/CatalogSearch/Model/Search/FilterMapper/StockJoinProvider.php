<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogSearch\Model\Search\FilterMapper;

use Magento\CatalogSearch\Model\Adapter\Mysql\Filter\AliasResolver;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

class StockJoinProvider implements StockJoinProviderInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @inheritdoc
     */
    public function add(Select $select, $alias)
    {
        $stockAlias = $alias . AliasResolver::STOCK_FILTER_SUFFIX;
        $select->joinLeft(
            [
                $stockAlias => $this->resourceConnection->getTableName('cataloginventory_stock_status'),
            ],
            sprintf('%2$s.product_id = %1$s.source_id', $alias, $stockAlias),
            []
        );
    }
}
