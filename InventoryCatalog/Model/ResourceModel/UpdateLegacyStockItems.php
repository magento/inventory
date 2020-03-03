<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model\ResourceModel;

use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Framework\App\ResourceConnection;

/**
 * Update legacy stock items resource.
 */
class UpdateLegacyStockItems
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var StockConfigurationInterface
     */
    private $legaycyStockConfiguration;

    /**
     * @param ResourceConnection $resourceConnection
     * @param StockConfigurationInterface $legaycyStockConfiguration
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        StockConfigurationInterface $legaycyStockConfiguration
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->legaycyStockConfiguration = $legaycyStockConfiguration;
    }

    /**
     * Update stock items.
     *
     * @param array $productIds
     * @param array $inventoryData
     * @return void
     */
    public function execute(array $productIds, array $inventoryData): void
    {
        $connection = $this->resourceConnection->getConnection();
        $connection->update(
            $this->resourceConnection->getTableName('cataloginventory_stock_item'),
            $inventoryData,
            [
                StockItemInterface::PRODUCT_ID . ' in (?)' => $productIds,
                'website_id = ?' => $this->legaycyStockConfiguration->getDefaultScopeId(),
            ]
        );
    }
}
