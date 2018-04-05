<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Model;

use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceItemsDeleteInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class MigrateToMultiSourceTest extends TestCase
{
    /**
     * @var SourceItemsDeleteInterface
     */
    private $sourceItemsDelete;

    /**
     * @var SourceItemsSaveInterface
     */
    private $sourceItemsSave;

    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    protected function setUp()
    {
        $this->sourceItemsDelete = Bootstrap::getObjectManager()->get(SourceItemsDeleteInterface::class);
        $this->sourceItemsSave = Bootstrap::getObjectManager()->get(SourceItemsSaveInterface::class);
        $this->sourceItemRepository = Bootstrap::getObjectManager()->get(SourceItemRepositoryInterface::class);
    }


    public function testMigrationWithNoSkus()
    {
    }
}