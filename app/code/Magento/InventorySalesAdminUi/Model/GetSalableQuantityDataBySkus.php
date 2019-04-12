<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesAdminUi\Model;

use Magento\InventorySalesAdminUi\Model\ResourceModel\GetSalableQuantityData;
use Magento\Framework\App\ResourceConnection;

/**
 * Get salable quantity data by SKUs
 */
class GetSalableQuantityDataBySkus
{
    /**
     * @var GetSalableQuantityData
     */
    private $getSalableQuantityData;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * GetSalableQuantityDataBySkus constructor.
     * @param GetSalableQuantityData $getSalableQuantityData
     * @param ResourceConnection $resource
     */
    public function __construct(
        GetSalableQuantityData $getSalableQuantityData,
        ResourceConnection $resource
    ) {
        $this->getSalableQuantityData = $getSalableQuantityData;
        $this->resource = $resource;
    }

    /**
     * @param array $skus
     * @return array
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException
     */
    public function execute(array $skus): array
    {
        $stocks = $this->getStockItems();

        $salableQuantity = [];
        foreach ($stocks as $stock) {
            $salableQuantityPerStock = $this->getSalableQuantityData->execute((int) $stock['stock_id'], $skus);

            foreach ($salableQuantityPerStock as $salableItem) {
                $salableQuantity[$salableItem['sku']][] = [
                    'stock_name' => $stock['name'],
                    'qty' => (float) $salableItem['salable_quantity'],
                    'manage_stock' => (bool) $salableItem['is_salable']
                ];
            }
        }

        return $salableQuantity;
    }

    /**
     * @return array
     */
    private function getStockItems(): array
    {
        /** @var \Magento\Framework\DB\Adapter\Pdo\Mysql $connection */
        $connection = $this->resource->getConnection();

        $select = $connection->select()
            ->from(
                ['stock_table' => $connection->getTableName('inventory_stock')]
            );

        return $connection->fetchAll($select);
    }
}
