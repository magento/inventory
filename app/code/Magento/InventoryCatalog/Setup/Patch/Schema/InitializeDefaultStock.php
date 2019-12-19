<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\InventoryCatalog\Setup\Operation\AssignDefaultSourceToDefaultStock;
use Magento\InventoryCatalog\Setup\Operation\CreateDefaultSource;
use Magento\InventoryCatalog\Setup\Operation\CreateDefaultStock;
use Magento\InventoryCatalog\Setup\Operation\CreateDefaultSourceTypes;
use Magento\InventoryCatalog\Setup\Operation\AssignDefaultSourceToRegularType;

/**
 * Patch schema with information about default stock
 */
class InitializeDefaultStock implements SchemaPatchInterface
{
    /**
     * @var CreateDefaultSource
     */
    private $createDefaultSource;

    /**
     * @var CreateDefaultStock
     */
    private $createDefaultStock;

    /**
     * @var AssignDefaultSourceToDefaultStock
     */
    private $assignDefaultSourceToDefaultStock;

    /**
     * @var CreateDefaultSourceTypes
     */
    private $createDefaultSourceTypes;

    /**
     * @var AssignDefaultSourceToRegularType
     */
    private $assignDefaultSourceToRegularType;

    /**
     * @param CreateDefaultSource $createDefaultSource
     * @param CreateDefaultStock $createDefaultStock
     * @param AssignDefaultSourceToDefaultStock $assignDefaultSourceToDefaultStock
     * @param CreateDefaultSourceTypes $createDefaultSourceTypes
     * @param AssignDefaultSourceToRegularType $assignDefaultSourceToRegularType
     */
    public function __construct(
        CreateDefaultSource $createDefaultSource,
        CreateDefaultStock $createDefaultStock,
        AssignDefaultSourceToDefaultStock $assignDefaultSourceToDefaultStock,
        CreateDefaultSourceTypes $createDefaultSourceTypes,
        AssignDefaultSourceToRegularType $assignDefaultSourceToRegularType
    ) {
        $this->createDefaultSource = $createDefaultSource;
        $this->createDefaultStock = $createDefaultStock;
        $this->assignDefaultSourceToDefaultStock = $assignDefaultSourceToDefaultStock;
        $this->createDefaultSourceTypes = $createDefaultSourceTypes;
        $this->assignDefaultSourceToRegularType = $assignDefaultSourceToRegularType;
    }

    /**
     * @inheritDoc
     */
    public function apply()
    {
        $this->createDefaultSource->execute();
        $this->createDefaultSourceTypes->execute();
        $this->createDefaultStock->execute();
        $this->assignDefaultSourceToRegularType->execute();
        $this->assignDefaultSourceToDefaultStock->execute();

        return $this;
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases()
    {
        return [];
    }
}
