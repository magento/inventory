<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryLowQuantityNotification\Model\ResourceModel\SourceItemConfiguration;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryLowQuantityNotificationApi\Api\Data\SourceItemConfigurationInterface;

/**
 * Get source items configuration for product resource model.
 */
class GetBySku
{
    /**
     * @var ResourceConnection
     */
    private $connection;

    /**
     * @param ResourceConnection $connection
     */
    public function __construct(ResourceConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Get source items configuration for given product sku.
     *
     * @param string $sku
     * @return array
     */
    public function execute(string $sku): array
    {
        $connection = $this->connection->getConnection();
        $sourceItemConfigurationTable = $this->connection
            ->getTableName('inventory_low_stock_notification_configuration');
        $select = $connection->select()
            ->from($sourceItemConfigurationTable)
            ->where(SourceItemConfigurationInterface::SKU . ' = ?', $sku);

        return $connection->fetchAll($select) ?: [];
    }
}
