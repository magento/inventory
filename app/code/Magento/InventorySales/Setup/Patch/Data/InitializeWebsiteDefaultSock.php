<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\InventorySales\Setup\Operation\AssignWebsiteToDefaultStock;

class InitializeWebsiteDefaultSock implements DataPatchInterface
{
    /**
     * @var AssignWebsiteToDefaultStock
     */
    private $assignWebsiteToDefaultStock;

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
