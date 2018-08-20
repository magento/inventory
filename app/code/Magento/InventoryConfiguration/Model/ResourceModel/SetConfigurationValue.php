<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

/**
 * If $stockId && $sourceCode && $sku are NULL => GLOBAL CONFIG
 * If specified $stockId only => STOCK CONFIG
 * If specified $stockId && $sku => STOCK ITEM CONFIG
 * If specified $sourceCode only => SOURCE CONFIG
 * If specified $sourceCode && $sku => SOURCE ITEM CONFIG
 */
class SetConfigurationValue
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param string $configOption
     * @param string|null $value
     * @param int|null $stockId
     * @param string|null $sourceCode
     * @param string|null $sku
     * @return void
     */
    public function execute(
        string $configOption,
        ?string $value,
        int $stockId = null,
        string $sourceCode = null,
        string $sku = null
    ): void {
        $connection = $this->resourceConnection->getConnection();
        $inventoryConfigurationTable = $this->resourceConnection->getTableName('inventory_configuration');

        $where = [];
        $where[] = $connection->quoteInto('config_option = ?', $configOption);
        if (isset($stockId)) {
            $where['stock_id = ?'] = $stockId;
        } else {
            $where[] = 'stock_id IS NULL';
        }

        if (isset($sourceCode)) {
            $where['source_code = ?'] = $sourceCode;
        } else {
            $where[] = 'source_code IS NULL';
        }

        if (isset($sku)) {
            $where['sku =?'] = $sku;
        } else {
            $where[] = 'sku IS NULL';
        }

        $connection->delete($inventoryConfigurationTable, $where);

        if ($value !== null) {
            $data = [
                'sku' => $sku,
                'source_code' => $sourceCode,
                'stock_id' => $stockId,
                'config_option' => $configOption,
                'value' => $value
            ];
            $connection->insert($inventoryConfigurationTable, $data);
        }
    }
}
