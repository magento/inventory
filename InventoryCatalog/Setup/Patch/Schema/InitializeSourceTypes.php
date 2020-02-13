<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Setup\Patch\Schema;

use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\InventoryCatalog\Setup\Operation\CreateDefaultSourceTypes;
use Magento\InventoryCatalog\Setup\Operation\AssignDefaultSourceToRegularType;
use Magento\InventoryCatalog\Setup\Patch\Schema\InitializeDefaultStock;

/**
 * Patch schema with information about source types
 */
class InitializeSourceTypes implements SchemaPatchInterface
{

    /**
     * @var CreateDefaultSourceTypes
     */
    private $createDefaultSourceTypes;

    /**
     * @var AssignDefaultSourceToRegularType
     */
    private $assignDefaultSourceToRegularType;

    /**
     * @param CreateDefaultSourceTypes $createDefaultSourceTypes
     * @param AssignDefaultSourceToRegularType $assignDefaultSourceToRegularType
     */
    public function __construct(
        CreateDefaultSourceTypes $createDefaultSourceTypes,
        AssignDefaultSourceToRegularType $assignDefaultSourceToRegularType
    ) {
        $this->createDefaultSourceTypes = $createDefaultSourceTypes;
        $this->assignDefaultSourceToRegularType = $assignDefaultSourceToRegularType;
    }

    /**
     * @inheritDoc
     */
    public function apply()
    {
        $this->createDefaultSourceTypes->execute();
        $this->assignDefaultSourceToRegularType->execute();

        return $this;
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies()
    {
        return [
            InitializeDefaultStock::class
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
