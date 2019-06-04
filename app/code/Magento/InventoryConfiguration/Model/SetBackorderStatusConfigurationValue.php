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
     * @inheritDoc
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
     * @inheritDoc
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
     * @inheritDoc
     */
    public function forGlobal(int $backorderStatus): void
    {
        // TODO should validate allowed values?
        $this->configWriter->save(
            SourceItemConfigurationInterface::XML_PATH_BACKORDERS,
            $backorderStatus
        );
    }

    /**
     * @inheritDoc
     */
    public function execute(string $sku = null, string $sourceCode = null, string $backorderStatus = null): void
    {
        if ($sku !== null && $sourceCode !== null) {
            $this->forSourceItem($sku, $sourceCode, is_numeric($backorderStatus) ? (int)$backorderStatus : null);
            return;
        }

        if ($sourceCode !== null)
        {
            $this->forSource($sourceCode, is_numeric($backorderStatus) ? (int)$backorderStatus : null);
            return;
        }

        if (!is_numeric($backorderStatus)) {
            throw new \Magento\Framework\Exception\CouldNotSaveException(
                __('Backorder status should be a numeric value to be saved in global scope')
            );
        }

        $this->forGlobal((int)$backorderStatus);
    }
}
