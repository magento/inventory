<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Inventory\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Inventory\Model\ResourceModel\SourceCarrierLink;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\Framework\Api\DataObjectHelper;

/**
 * Class InstallData
 */
class InstallData implements InstallDataInterface
{
    /**
     * {@inheritdoc}
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $sourceCarrierLinkTable = $setup->getTable(SourceCarrierLink::TABLE_NAME_SOURCE_CARRIER_LINK);
        $setup->getConnection()->query('SELECT * FROM ' . $sourceCarrierLinkTable);

        var_dump($sourceCarrierLinkTable);
        var_dump(SourceCarrierLink::TABLE_NAME_SOURCE_CARRIER_LINK);
    }
}
