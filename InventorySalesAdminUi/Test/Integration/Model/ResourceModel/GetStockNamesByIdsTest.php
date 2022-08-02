<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesAdminUi\Test\Integration\Model\ResourceModel;

use Magento\InventorySalesAdminUi\Model\ResourceModel\GetStockNamesByIds;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class GetStockNamesByIdsTest extends TestCase
{
    /**
     * @var GetStockNamesByIds
     */
    private $getStockNamesByIds;

    protected function setUp(): void
    {
        $this->getStockNamesByIds = Bootstrap::getObjectManager()
            ->create(GetStockNamesByIds::class);
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     */
    public function testExecute()
    {
        $stockIds = [
            10,
            20,
        ];
        $expectedStockNames = [
            10 => 'EU-stock',
            20 => 'US-stock',
        ];
        $stockNames = $this->getStockNamesByIds->execute($stockIds);
        self::assertEquals($expectedStockNames, $stockNames);
    }
}
