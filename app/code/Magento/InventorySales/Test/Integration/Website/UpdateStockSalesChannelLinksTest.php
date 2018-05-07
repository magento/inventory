<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Integration\Website;

use Magento\InventorySales\Model\GetAssignedStockIdForWebsiteInterface;
use Magento\Store\Model\Website;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 */
class UpdateStockSalesChannelLinksTest extends TestCase
{
    /**
     * @var GetAssignedStockIdForWebsiteInterface
     */
    private $getAssignedStockIdForWebsite;

    /**
     * @var Website
     */
    private $websiteModel;

    protected function setUp()
    {
        $this->getAssignedStockIdForWebsite =
            Bootstrap::getObjectManager()->get(GetAssignedStockIdForWebsiteInterface::class);
        $this->websiteModel =
            Bootstrap::getObjectManager()->get(Website::class);
    }

    /**
     * Tests correct inventory_stock_sales_channel updating when Website Code is changed.
     *
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/websites_with_stores.php
     * @magentoDataFixture ../../../../app/code/Magento/InventorySalesApi/Test/_files/stock_website_sales_channels.php
     * @dataProvider updateWebsiteCodeDataProvider
     * @magentoDbIsolation disabled
     */
    public function testUpdateWebsiteCode($oldWebsiteCode, $newWebsiteCode)
    {
        $originalStockId = $this->getAssignedStockIdForWebsite->execute($oldWebsiteCode);

        $website = $this->websiteModel->load($oldWebsiteCode, 'code');
        $website->setCode($newWebsiteCode);
        $website->save();

        // We need specific assertion if the char cases are the only difference between old and new codes.
        if (strcasecmp($oldWebsiteCode, $newWebsiteCode)) {
            self::assertNull($this->getAssignedStockIdForWebsite->execute($oldWebsiteCode));
        } else {
            self::assertNotNull($this->getAssignedStockIdForWebsite->execute($oldWebsiteCode));
        }
        self::assertEquals(
            $originalStockId,
            $this->getAssignedStockIdForWebsite->execute($newWebsiteCode)
        );

        // Needed for correct rollback.
        $website->setCode($oldWebsiteCode);
        $website->save();
    }

    /**
     * Data provider for testUpdateWebsiteCode.
     *
     * @return array
     */
    public function updateWebsiteCodeDataProvider()
    {
        return [
            'Update Default Website Code' => [
                'base',
                'updated_base',
            ],
            'Update Custom Website Code change' => [
                'global_website',
                'updated_global_website',
            ],
            'Update Custom Website Code capitalize' => [
                'global_website',
                'Global_Website',
            ],
        ];
    }
}
