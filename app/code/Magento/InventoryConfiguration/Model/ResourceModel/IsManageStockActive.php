<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

class IsManageStockActive
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * IsManageStockActive constructor.
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param string $sku
     * @return bool
     */
    public function execute(string $sku): bool
    {
        /** @var ResourceConnection $resource */
        $connection = $this->resourceConnection->getConnection();

        /** @var \Magento\Framework\DB\Select $select */
        $select = $connection->select()
            ->from(['csi' => 'cataloginventory_stock_item'], ['manage_stock'])
            ->joinLeft(['cpe' => 'catalog_product_entity'], 'csi.product_id = cpe.entity_id')
            ->where('cpe.sku = ?', $sku);

        return (bool) $connection->fetchOne($select);
    }
}
