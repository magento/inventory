<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryLowQuantityNotification\Model\ResourceModel\SourceItemConfiguration;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryLowQuantityNotificationApi\Api\Data\SourceItemConfigurationInterface;
use Magento\InventoryLowQuantityNotificationApi\Api\Data\SourceItemConfigurationInterfaceFactory;

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
     * @var SourceItemConfigurationInterfaceFactory
     */
    private $sourceItemConfigurationInterfaceFactory;

    /**
     * @param ResourceConnection $connection
     * @param SourceItemConfigurationInterfaceFactory $sourceItemConfigurationInterfaceFactory
     */
    public function __construct(
        ResourceConnection $connection,
        SourceItemConfigurationInterfaceFactory $sourceItemConfigurationInterfaceFactory
    ) {
        $this->connection = $connection;
        $this->sourceItemConfigurationInterfaceFactory = $sourceItemConfigurationInterfaceFactory;
    }

    /**
     * Get source items configuration for given product sku.
     *
     * @param string $sku
     * @return SourceItemConfigurationInterface[]
     */
    public function execute(string $sku): array
    {
        $connection = $this->connection->getConnection();
        $sourceItemConfigurationTable = $this->connection
            ->getTableName('inventory_low_stock_notification_configuration');
        $select = $connection->select()
            ->from($sourceItemConfigurationTable)
            ->where(SourceItemConfigurationInterface::SKU . ' = ?', $sku);
        $data = $connection->fetchAll($select) ?: [];
        $sourceItemsConfigurations = [];
        foreach ($data as $sourceItemConfigurationData) {
            $sourceItemConfiguration = $this->sourceItemConfigurationInterfaceFactory->create(
                ['data' => $sourceItemConfigurationData]
            );
            $sourceItemsConfigurations[] = $sourceItemConfiguration;
        }

        return $sourceItemsConfigurations;
    }
}
