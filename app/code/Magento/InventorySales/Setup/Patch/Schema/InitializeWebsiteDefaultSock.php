<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Setup\Patch\Schema;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Validation\ValidationException;
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
     * @throws CouldNotSaveException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws ValidationException
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
