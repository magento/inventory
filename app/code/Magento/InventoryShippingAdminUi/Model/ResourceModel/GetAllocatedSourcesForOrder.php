<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryShippingAdminUi\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

/**
 * Get allocated sources for specified order.
 */
class GetAllocatedSourcesForOrder
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
     * Get allocated sources by order ID
     *
     * @param int $orderId
     * @return array
     */
    public function execute(int $orderId): array
    {
        $connection = $this->resourceConnection->getConnection();
        $inventorySourceTableName = $this->resourceConnection->getTableName('inventory_source');
        $inventoryShipmentSourceTableName = $this->resourceConnection->getTableName('inventory_shipment_source');
        $shipmentTableName = $this->resourceConnection->getTableName('sales_shipment');

        $select = $connection->select()
            ->from(
                ['inventory_source' => $inventorySourceTableName],
                ['source_name' => 'inventory_source.name']
            )
            ->joinInner(
                ['shipment_source' => $inventoryShipmentSourceTableName],
                'shipment_source.source_code = inventory_source.source_code',
                []
            )
            ->joinInner(
                ['sales_shipment' => $shipmentTableName],
                'shipment_source.shipment_id = sales_shipment.entity_id',
                []
            )
            ->group('inventory_source.source_code')
            ->where('sales_shipment.order_id = ?', $orderId);

        return $connection->fetchCol($select);
    }
}
