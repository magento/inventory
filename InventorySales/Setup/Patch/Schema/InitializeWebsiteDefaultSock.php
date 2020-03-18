<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Setup\Patch\Schema;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\InventorySales\Setup\Operation\AssignWebsiteToDefaultStock;
use Magento\Store\Setup\Patch\Schema\InitializeStoresAndWebsites;

/**
 * Assign default website to default stock
 */
class InitializeWebsiteDefaultSock implements SchemaPatchInterface
{
    /**
     * @var AssignWebsiteToDefaultStock
     */
    private $assignWebsiteToDefaultStock;
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * Initialize dependencies.
     *
     * @param AssignWebsiteToDefaultStock $assignWebsiteToDefaultStock
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        AssignWebsiteToDefaultStock $assignWebsiteToDefaultStock,
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->assignWebsiteToDefaultStock = $assignWebsiteToDefaultStock;
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * @inheritDoc
     */
    public function apply()
    {
        $this->assignWebsiteToDefaultStock->execute($this->getDefaultWebsiteCode());

        return $this;
    }

    /**
     * Get the default website
     *
     * @return string
     */
    private function getDefaultWebsiteCode(): string
    {
        $websiteTable = $this->moduleDataSetup->getTable('store_website');
        $connection = $this->moduleDataSetup->getConnection();
        $select = $connection->select()->from($websiteTable, ['code'])->where('is_default=?', 1);
        return $connection->fetchOne($select);
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies()
    {
        return [
            InitializeStoresAndWebsites::class
        ];
    }

    /**
     * @inheritDoc
     */
    public function getAliases()
    {
        return [];
    }
}
