<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\InventoryConfigurationApi\Api\Data\SourceItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\SetBackorderStatusConfigurationValueInterface;

class SetBackorderStatusConfigurationValue implements SetBackorderStatusConfigurationValueInterface
{
    /**
     * @var ResourceConnection;
     */
    private $resourceConnection;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    public function __construct(
        ResourceConnection $resourceConnection,
        WriterInterface $configWriter
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->configWriter = $configWriter;
    }

    /**
     * @param string $sku
     * @param string $sourceCode Backorder
     * @param int $backorderStatus if NULL is set that means fallback to Source configuration would be used
     * @return void
     */
    public function forSourceItem(string $sku, string $sourceCode, ?int $backorderStatus): void
    {
        // TODO should validate allowed values?
        $connection = $this->resourceConnection->getConnection();
        $configurationTable = $this->resourceConnection->getTableName('inventory_configuration');
        $select = $connection->select()
            ->from($configurationTable)
            ->where('sku = ?', $sku)
            ->where('source_code = ?', $sourceCode)
            ->where('config_option = ?', SourceItemConfigurationInterface::BACKORDERS);
        $data = $connection->fetchRow($select);
        $isNew = false;
        if ($data === false) {
            $isNew = true;
            $data = [
                'sku' => $sku,
                'source_code' => $sourceCode,
                'stock_id' => null,
                'config_option' => SourceItemConfigurationInterface::BACKORDERS,
            ];
        }
        $data['value'] = $backorderStatus ? (string)$backorderStatus : null;
        if ($isNew) {
            $connection->insert($configurationTable, $data);
        } else {
            $where = [
                'sku = ?' => $sku,
                'source_code = ?' => $sourceCode,
                'stock_id IS NULL',
                'config_option = ?' => SourceItemConfigurationInterface::BACKORDERS,
            ];

            $connection->update($configurationTable, $data, $where);
        }
    }

    /**
     * @param string $sourceCode
     * @param int $backorderStatus if NULL is set that means fallback to Global configuration would be used
     * @return void
     */
    public function forSource(string $sourceCode, ?int $backorderStatus): void
    {
        // TODO should validate allowed values?
        $connection = $this->resourceConnection->getConnection();
        $configurationTable = $this->resourceConnection->getTableName('inventory_configuration');
        $select = $connection->select()
            ->from($configurationTable)
            ->where('sku IS NULL')
            ->where('source_code = ?', $sourceCode)
            ->where('stock_id IS NULL')
            ->where('config_option = ?', SourceItemConfigurationInterface::BACKORDERS);
        $data = $connection->fetchRow($select);
        $isNew = false;
        if ($data === false) {
            $isNew = true;
            $data = [
                'sku' => null,
                'source_code' => $sourceCode,
                'stock_id' => null,
                'config_option' => SourceItemConfigurationInterface::BACKORDERS,
            ];
        }
        $data['value'] = $backorderStatus ? (string)$backorderStatus : null;
        if ($isNew) {
            $connection->insert($configurationTable, $data);
        } else {
            $where = [
                'sku IS NULL',
                'source_code = ?' => $sourceCode,
                'stock_id IS NULL',
                'config_option = ?' => SourceItemConfigurationInterface::BACKORDERS,
            ];
            $connection->update($configurationTable, $data, $where);
        }
    }

    /**
     * @param int $backorderStatus Backorder configuration applied globally
     * @return void
     */
    public function forGlobal(int $backorderStatus): void
    {
        // TODO should validate allowed values?
        $this->configWriter->save(
            SourceItemConfigurationInterface::XML_PATH_BACKORDERS,
            $backorderStatus
        );
    }
}
