<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\SaveStockConfigurationInterface;
use Magento\Framework\App\ResourceConnection;

class SaveStockConfiguration implements SaveStockConfigurationInterface
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @param ResourceConnection $resourceConnection
     * @param WriterInterface $configWriter
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        WriterInterface $configWriter
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->configWriter = $configWriter;
    }

    /**
     * @inheritdoc
     */
    public function forStockItem(
        string $sku,
        int $stockId,
        StockItemConfigurationInterface $stockItemConfiguration
    ): void {
        $connection = $this->resourceConnection->getConnection();
        $stockConfigurationTable = $this->resourceConnection->getTableName('inventory_stock_configuration');

        $data = [
            'sku' => $sku,
            'stock_id' => $stockId,
            StockItemConfigurationInterface::MIN_QTY => $stockItemConfiguration->getMinQty(),
            StockItemConfigurationInterface::MIN_SALE_QTY => $stockItemConfiguration->getMinSaleQty(),
            StockItemConfigurationInterface::MAX_SALE_QTY => $stockItemConfiguration->getMaxSaleQty(),
            StockItemConfigurationInterface::ENABLE_QTY_INCREMENTS => $stockItemConfiguration->isEnableQtyIncrements(),
            StockItemConfigurationInterface::QTY_INCREMENTS => $stockItemConfiguration->getQtyIncrements(),
            StockItemConfigurationInterface::MANAGE_STOCK => $stockItemConfiguration->isManageStock(),
            StockItemConfigurationInterface::LOW_STOCK_DATE => $stockItemConfiguration->getLowStockDate(),
            StockItemConfigurationInterface::IS_QTY_DECIMAL => $stockItemConfiguration->isQtyDecimal(),
            StockItemConfigurationInterface::IS_DECIMAL_DIVIDED => $stockItemConfiguration->isDecimalDivided(),
            StockItemConfigurationInterface::STOCK_STATUS_CHANGED_AUTO =>
                $stockItemConfiguration->getStockStatusChangedAuto(),
            StockItemConfigurationInterface::STOCK_THRESHOLD_QTY => $stockItemConfiguration->getStockThresholdQty(),
        ];
        $connection->insertOnDuplicate(
            $stockConfigurationTable,
            $data
        );
    }

    /**
     * @inheritdoc
     */
    public function forStock(int $stockId, StockItemConfigurationInterface $stockItemConfiguration): void
    {
        $connection = $this->resourceConnection->getConnection();
        $stockConfigurationTable = $this->resourceConnection->getTableName('inventory_stock_configuration');

        $select = $connection->select()
            ->from($stockConfigurationTable)
            ->where('stock_id = ?', $stockId)
            ->where('sku IS NULL');
        $isExistStockConfiguration = $connection->fetchOne($select);

        $data = [
            'sku' => null,
            'stock_id' => $stockId,
            StockItemConfigurationInterface::MIN_QTY => $stockItemConfiguration->getMinQty(),
            StockItemConfigurationInterface::MIN_SALE_QTY => $stockItemConfiguration->getMinSaleQty(),
            StockItemConfigurationInterface::MAX_SALE_QTY => $stockItemConfiguration->getMaxSaleQty(),
            StockItemConfigurationInterface::ENABLE_QTY_INCREMENTS => $stockItemConfiguration->isEnableQtyIncrements(),
            StockItemConfigurationInterface::QTY_INCREMENTS => $stockItemConfiguration->getQtyIncrements(),
            StockItemConfigurationInterface::MANAGE_STOCK => $stockItemConfiguration->isManageStock(),
            StockItemConfigurationInterface::LOW_STOCK_DATE => $stockItemConfiguration->getLowStockDate(),
            StockItemConfigurationInterface::IS_QTY_DECIMAL => $stockItemConfiguration->isQtyDecimal(),
            StockItemConfigurationInterface::IS_DECIMAL_DIVIDED => $stockItemConfiguration->isDecimalDivided(),
            StockItemConfigurationInterface::STOCK_STATUS_CHANGED_AUTO =>
                $stockItemConfiguration->getStockStatusChangedAuto(),
            StockItemConfigurationInterface::STOCK_THRESHOLD_QTY => $stockItemConfiguration->getStockThresholdQty(),
        ];

        if ($isExistStockConfiguration) {
            $where['stock_id = ?'] = $stockId;
            $where[] = 'sku IS NULL';
            $connection->update($stockConfigurationTable, $data, $where);
        } else {
            $connection->insert($stockConfigurationTable, $data);
        }
    }

    /**
     * @inheritdoc
     */
    public function forGlobal(StockItemConfigurationInterface $stockItemConfiguration): void
    {
        $this->configWriter->save(
            StockItemConfigurationInterface::XML_PATH_MIN_QTY,
            $stockItemConfiguration->getMinQty()
        );

        $this->configWriter->save(
            StockItemConfigurationInterface::XML_PATH_MIN_SALE_QTY,
            $stockItemConfiguration->getMinSaleQty()
        );

        $this->configWriter->save(
            StockItemConfigurationInterface::XML_PATH_MAX_SALE_QTY,
            $stockItemConfiguration->getMaxSaleQty()
        );

        $this->configWriter->save(
            StockItemConfigurationInterface::XML_PATH_ENABLE_QTY_INCREMENTS,
            $stockItemConfiguration->isEnableQtyIncrements()
        );

        $this->configWriter->save(
            StockItemConfigurationInterface::XML_PATH_QTY_INCREMENTS,
            $stockItemConfiguration->getQtyIncrements()
        );

        $this->configWriter->save(
            StockItemConfigurationInterface::XML_PATH_MANAGE_STOCK,
            $stockItemConfiguration->isManageStock()
        );

        $this->configWriter->save(
            StockItemConfigurationInterface::XML_PATH_STOCK_THRESHOLD_QTY,
            $stockItemConfiguration->getStockThresholdQty()
        );
    }
}
