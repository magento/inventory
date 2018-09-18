<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

class GetStockConfigurationData
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param int $stockId
     * @param string|null $sku
     * @return array
     */
    public function execute(int $stockId, string $sku = null): array
    {
        $connection = $this->resourceConnection->getConnection();
        $stockConfigurationTable = $this->resourceConnection->getTableName('inventory_stock_configuration');

        $select = $connection->select()
            ->from($stockConfigurationTable)
            ->where('stock_id = ?', $stockId);

        if (isset($sku)) {
            $select->where('sku = ?', $sku);
        } else {
            $select->where('sku IS NULL');
        }

        $row = $connection->fetchRow($select);
        return $row ? $row : [];
    }
}
