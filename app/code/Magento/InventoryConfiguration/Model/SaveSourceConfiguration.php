<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\InventoryConfigurationApi\Api\Data\SourceItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\SaveSourceConfigurationInterface;
use Magento\Framework\App\ResourceConnection;

class SaveSourceConfiguration implements SaveSourceConfigurationInterface
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
    public function forSourceItem(
        string $sku,
        string $sourceCode,
        SourceItemConfigurationInterface $sourceItemConfiguration
    ): void {
        $connection = $this->resourceConnection->getConnection();
        $sourceConfigurationTable = $this->resourceConnection->getTableName('inventory_source_configuration');

        $data = [
            'sku' => $sku,
            'source_code' => $sourceCode,
            SourceItemConfigurationInterface::BACKORDERS => $sourceItemConfiguration->getBackorders(),
            SourceItemConfigurationInterface::NOTIFY_STOCK_QTY => $sourceItemConfiguration->getNotifyStockQty()
        ];
        $connection->insertOnDuplicate(
            $sourceConfigurationTable,
            $data
        );
    }

    /**
     * @inheritdoc
     */
    public function forSource(string $sourceCode, SourceItemConfigurationInterface $sourceItemConfiguration): void
    {
        $connection = $this->resourceConnection->getConnection();
        $sourceConfigurationTable = $this->resourceConnection->getTableName('inventory_source_configuration');

        $select = $connection->select()
            ->from($sourceConfigurationTable)
            ->where('source_code = ?', $sourceCode)
            ->where('sku IS NULL');
        $isExistSourceConfiguration = $connection->fetchOne($select);

        $data = [
            'sku' => null,
            'source_code' => $sourceCode,
            SourceItemConfigurationInterface::BACKORDERS => $sourceItemConfiguration->getBackorders(),
            SourceItemConfigurationInterface::NOTIFY_STOCK_QTY => $sourceItemConfiguration->getNotifyStockQty()
        ];

        if ($isExistSourceConfiguration) {
            $where['source_code = ?'] = $sourceCode;
            $where[] = 'sku IS NULL';
            $connection->update($sourceConfigurationTable, $data, $where);
        } else {
            $connection->insert($sourceConfigurationTable, $data);
        }
    }

    /**
     * @inheritdoc
     */
    public function forGlobal(SourceItemConfigurationInterface $sourceItemConfiguration): void
    {
        $this->configWriter->save(
            SourceItemConfigurationInterface::XML_PATH_BACKORDERS,
            $sourceItemConfiguration->getBackorders()
        );

        $this->configWriter->save(
            SourceItemConfigurationInterface::XML_PATH_NOTIFY_STOCK_QTY,
            $sourceItemConfiguration->getNotifyStockQty()
        );
    }
}
