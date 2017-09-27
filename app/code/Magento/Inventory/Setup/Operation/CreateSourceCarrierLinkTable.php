<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Inventory\Setup\Operation;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Inventory\Model\ResourceModel\Source as SourceResourceModel;
use Magento\Inventory\Model\ResourceModel\SourceCarrierLink;
use Magento\InventoryApi\Api\Data\SourceCarrierLinkInterface;
use Magento\InventoryApi\Api\Data\SourceInterface;

class CreateSourceCarrierLinkTable
{
    /**
     * @param SchemaSetupInterface $setup
     * @return void
     */
    public function execute(SchemaSetupInterface $setup)
    {
        $sourceCarrierLinkTable = $this->createSourceCarrierLinkTable($setup);

        $setup->getConnection()->createTable($sourceCarrierLinkTable);
    }

    /**
     * @param SchemaSetupInterface $setup
     * @return Table
     */
    private function createSourceCarrierLinkTable(SchemaSetupInterface $setup)
    {
        $sourceCarrierLinkTable = $setup->getTable(SourceCarrierLink::TABLE_NAME_SOURCE_CARRIER_LINK);
        $sourceTable = $setup->getTable(SourceResourceModel::TABLE_NAME_SOURCE);

        return $setup->getConnection()->newTable(
            $sourceCarrierLinkTable
        )->setComment(
            'Inventory Source Carrier Link Table'
        )->addColumn(
            SourceCarrierLink::ID_FIELD_NAME,
            Table::TYPE_INTEGER,
            null
        );
    }
}
