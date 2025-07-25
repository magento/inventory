<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\InventorySales\Setup\Operation\AssignWebsiteToDefaultStock;
use Magento\Store\Setup\Patch\Schema\InitializeStoresAndWebsites;

class InitializeWebsiteDefaultSock implements SchemaPatchInterface
{
    /**
     * @var AssignWebsiteToDefaultStock
     */
    private $assignWebsiteToDefaultStock;

    /**
     * @param AssignWebsiteToDefaultStock $assignWebsiteToDefaultStock
     */
    public function __construct(AssignWebsiteToDefaultStock $assignWebsiteToDefaultStock)
    {
        $this->assignWebsiteToDefaultStock = $assignWebsiteToDefaultStock;
    }

    /**
     * @inheritDoc
     */
    public function apply()
    {
        $this->assignWebsiteToDefaultStock->execute();

        return $this;
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
