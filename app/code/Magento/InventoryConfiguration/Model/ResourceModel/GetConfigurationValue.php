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
class GetConfigurationValue
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
     * @param string|null $sku
     * @param string|null $sourceCode
     * @param int|null $stockId
     * @return null|string
     */
    public function execute(
        string $configOption,
        int $stockId = null,
        string $sourceCode = null,
        string $sku = null
    ): ?string {
        $connection = $this->resourceConnection->getConnection();
        $inventoryConfigurationTable = $this->resourceConnection->getTableName('inventory_configuration');

        $select = $connection->select()
            ->from($inventoryConfigurationTable, 'value')
            ->where('config_option = ?', $configOption)
            ->limit(1);

        if (isset($stockId)) {
            $select->where('stock_id IS NULL');
        } else {
            $select->where('stock_id = ?', $stockId);
        }

        if (isset($sourceCode)) {
            $select->where('source_code IS NULL');
        } else {
            $select->where('source_code = ?', $sourceCode);
        }

        if (isset($sku)) {
            $select->where('sku IS NULL');
        } else {
            $select->where('sku = ?', $sku);
        }

        $value = $connection->fetchOne($select);

        return ($value === false) ? null : $value;
    }
}
