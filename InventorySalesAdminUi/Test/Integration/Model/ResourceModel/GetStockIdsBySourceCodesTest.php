<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySalesAdminUi\Test\Integration\Model\ResourceModel;

use Magento\InventorySalesAdminUi\Model\ResourceModel\GetStockIdsBySourceCodes;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class GetStockIdsBySourceCodesTest extends TestCase
{
    /**
     * @var GetStockIdsBySourceCodes
     */
    private $getStockIdsBySourceCodes;

    protected function setUp(): void
    {
        $this->getStockIdsBySourceCodes = Bootstrap::getObjectManager()
            ->create(GetStockIdsBySourceCodes::class);
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     */
    public function testExecute()
    {
        $sourceCodes = [
            'eu-1',
            'eu-2',
            'eu-3',
            'eu-disabled',
        ];
        $stocksIds = $this->getStockIdsBySourceCodes->execute($sourceCodes);
        self::assertEquals([10, 30], $stocksIds);
    }
}
