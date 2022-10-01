<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryReservationCli\Model\ResourceModel;

use Magento\Catalog\Model\Product\Type\AbstractType;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\InventoryConfigurationApi\Model\GetAllowedProductTypesForSourceItemManagementInterface;
use Magento\InventoryReservationCli\Model\GetCompleteOrderStateList;

/**
 * Loads order item data for orders, which are not in final state
 */
class GetOrderItemsDataForOrdersInNotFinalState
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var GetCompleteOrderStateList
     */
    private $getCompleteOrderStateList;

    /**
     * @var GetAllowedProductTypesForSourceItemManagementInterface|null
     */
    private $allowedProductTypesForSourceItemManagement;

    /**
     * @var Json
     */
    private $json;

    /**
     * @param ResourceConnection $resourceConnection
     * @param GetCompleteOrderStateList $getCompleteOrderStateList
     * @param GetAllowedProductTypesForSourceItemManagementInterface|null $allowedProductTypesForSourceItemManagement
     * @param Json $json
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        GetCompleteOrderStateList $getCompleteOrderStateList,
        GetAllowedProductTypesForSourceItemManagementInterface $allowedProductTypesForSourceItemManagement = null,
        Json $json
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->getCompleteOrderStateList = $getCompleteOrderStateList;
        $this->allowedProductTypesForSourceItemManagement = $allowedProductTypesForSourceItemManagement
            ?: ObjectManager::getInstance()->get(GetAllowedProductTypesForSourceItemManagementInterface::class);
        $this->json = $json ?: ObjectManager::getInstance()->get(Json::class);
    }

    /**
     * Load reservations from database.
     *
     * @param int $bunchSize
     * @param int $page
     * @return array
     */
    public function execute(int $bunchSize = 50, int $page = 1): array
    {
        $connection = $this->resourceConnection->getConnection('sales');
        $orderTableName = $this->resourceConnection->getTableName('sales_order', 'sales');
        $orderItemTableName = $this->resourceConnection->getTableName('sales_order_item', 'sales');

        $orderEntityIdSelectQuery = $connection
            ->select()
            ->from(
                ['main_table' => $orderTableName],
                ['main_table.entity_id']
            )
            ->where('main_table.state NOT IN (?)', $this->getCompleteOrderStateList->execute())
            ->where('main_table.store_id IS NOT NULL')
            ->limitPage($page, $bunchSize);
        $entityIds = $connection->fetchCol($orderEntityIdSelectQuery);
        $productTypes = $this->allowedProductTypesForSourceItemManagement->execute();
        // Simple products that are part of a grouped product are saved in the database
        // (table sales_order_item) with product type grouped.
        $productTypes[] = 'grouped';

        $query = $connection
            ->select()
            ->from(
                ['main_table' => $orderTableName],
                [
                    'main_table.entity_id',
                    'main_table.increment_id',
                    'main_table.status',
                    'main_table.store_id'
                ]
            )
            ->join(
                ['item' => $orderItemTableName],
                'item.order_id = main_table.entity_id',
                [
                    'item.sku',
                    'item.is_virtual',
                    'item.qty_ordered',
                    'item.qty_canceled',
                    'item.qty_invoiced',
                    'item.qty_refunded',
                    'item.qty_shipped',
                    'item.parent_item_id'
                ]
            )
            ->where('main_table.entity_id IN (?)', $entityIds)
            ->where('item.product_type IN (?)', $productTypes);
        $orderItems = $connection->fetchAll($query);
        $storeWebsiteIds = $this->getStoreWebsiteIds();
        $parentOrderItemIds = array_filter(array_column($orderItems, 'parent_item_id'));
        $parentOrderItems = [];
        if ($parentOrderItemIds) {
            $query = $connection
                ->select()
                ->from(
                    ['item' => $orderItemTableName],
                    [
                        'item.item_id',
                        'item.qty_ordered',
                        'item.qty_canceled',
                        'item.qty_invoiced',
                        'item.qty_refunded',
                        'item.qty_shipped',
                        'item.product_options'
                    ]
                )
                ->where('item.item_id IN (?)', $parentOrderItemIds);
            $parentOrderItems = $connection->fetchAssoc($query);
        }

        foreach ($orderItems as $key => $orderItem) {
            $orderItem['website_id'] = $storeWebsiteIds[$orderItem['store_id']];
            if (isset($orderItem['parent_item_id'], $parentOrderItems[$orderItem['parent_item_id']])) {
                $parentOrderItem = $parentOrderItems[$orderItem['parent_item_id']];
                $orderItem['qty_canceled'] = $this->getQtyCanceled($orderItem, $parentOrderItem);
                $orderItem['qty_invoiced'] = $this->getQtyInvoiced($orderItem, $parentOrderItem);
                $orderItem['qty_shipped'] = $this->getQtyShipped($orderItem, $parentOrderItem);
                $orderItem['qty_refunded'] = $this->getQtyRefunded($orderItem, $parentOrderItem);
            }
            unset($orderItem['parent_item_id']);
            $orderItems[$key] = $orderItem;
        }
        return $orderItems;
    }

    /**
     * Returns order item qty refunded
     *
     * @param array $orderItem
     * @param array|null $parentOrderItem
     * @return float
     */
    private function getQtyRefunded(array $orderItem, ?array $parentOrderItem): float
    {
        if ($parentOrderItem && $parentOrderItem['qty_ordered'] && !$this->isShipSeparately($parentOrderItem)) {
            $qtyUnit = $orderItem['qty_ordered']/$parentOrderItem['qty_ordered'];
            $qty = $qtyUnit * $parentOrderItem['qty_refunded'];
        } else {
            $qty = $orderItem['qty_refunded'];
        }
        return (float) $qty;
    }

    /**
     * Returns order item qty shipped
     *
     * @param array $orderItem
     * @param array|null $parentOrderItem
     * @return float
     */
    private function getQtyShipped(array $orderItem, ?array $parentOrderItem): float
    {
        if ($parentOrderItem && $parentOrderItem['qty_ordered'] && !$this->isShipSeparately($parentOrderItem)) {
            $qtyUnit = $orderItem['qty_ordered']/$parentOrderItem['qty_ordered'];
            $qty = $qtyUnit * $parentOrderItem['qty_shipped'];
        } else {
            $qty = $orderItem['qty_shipped'];
        }
        return (float) $qty;
    }

    /**
     * Returns order item qty invoiced
     *
     * @param array $orderItem
     * @param array|null $parentOrderItem
     * @return float
     */
    private function getQtyInvoiced(array $orderItem, ?array $parentOrderItem): float
    {
        if ($parentOrderItem && $parentOrderItem['qty_ordered'] && !$this->isShipSeparately($parentOrderItem)) {
            $qtyUnit = $orderItem['qty_ordered']/$parentOrderItem['qty_ordered'];
            $qty = $qtyUnit * $parentOrderItem['qty_invoiced'];
        } else {
            $qty = $orderItem['qty_invoiced'];
        }
        return (float) $qty;
    }

    /**
     * Returns order item qty canceled
     *
     * @param array $orderItem
     * @param array|null $parentOrderItem
     * @return float
     */
    private function getQtyCanceled(array $orderItem, ?array $parentOrderItem): float
    {
        if ($parentOrderItem && $parentOrderItem['qty_ordered'] && !$this->isShipSeparately($parentOrderItem)) {
            $qtyUnit = $orderItem['qty_ordered']/$parentOrderItem['qty_ordered'];
            $qty = $qtyUnit * $parentOrderItem['qty_canceled'];
        } else {
            $qty = $orderItem['qty_canceled'];
        }
        return (float) $qty;
    }

    /**
     * Checks whether the order item is shipped separately
     *
     * @param array $parentOrderItem
     * @return bool
     */
    private function isShipSeparately(array $parentOrderItem): bool
    {
        $options = $this->json->unserialize($parentOrderItem['product_options']);

        return isset($options['shipment_type']) && (int)$options['shipment_type'] === AbstractType::SHIPMENT_SEPARATELY;
    }

    /**
     * Get storeIds with their websiteIds
     *
     * @return array
     */
    private function getStoreWebsiteIds(): array
    {
        $storeWebsiteIds = [];
        $connection = $this->resourceConnection->getConnection();
        $storeTableName = $this->resourceConnection->getTableName('store');
        $query = $connection
            ->select()
            ->from(
                ['main_table' => $storeTableName],
                ['store_id', 'website_id']
            );
        foreach ($connection->fetchAll($query) as $store) {
            $storeWebsiteIds[$store['store_id']] = $store['website_id'];
        }
        return $storeWebsiteIds;
    }
}
