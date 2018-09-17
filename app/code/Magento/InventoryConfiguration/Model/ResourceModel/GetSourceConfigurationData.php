<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

class GetSourceConfigurationData
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
     * @param string $sourceCode
     * @param string|null $sku
     * @return array
     */
    public function execute(string $sourceCode, string $sku = null): array
    {
        $connection = $this->resourceConnection->getConnection();
        $sourceConfigurationTable = $connection->getTableName('inventory_source_configuration');

        $select = $connection->select()
            ->from($sourceConfigurationTable)
            ->where('source_code = ?', $sourceCode);

        if (isset($sku)) {
            $select->where('sku = ?', $sku);
        } else {
            $select->where('sku IS NULL');
        }

        $row = $connection->fetchRow($select);
        return $row ? $row : [];
    }
}
