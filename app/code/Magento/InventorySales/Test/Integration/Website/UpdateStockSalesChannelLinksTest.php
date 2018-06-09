<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Integration\Website;

use Magento\InventorySalesApi\Model\GetAssignedStockIdForWebsiteInterface;
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
     *
     * @param string $oldWebsiteCode
     * @param string $newWebsiteCode
     * @throws \Exception
     *
     * @dataProvider updateWebsiteCodeDataProvider
     *
     * @magentoDbIsolation disabled
     */
    public function testUpdateWebsiteCode(string $oldWebsiteCode, string $newWebsiteCode)
    {
        $originalStockId = $this->getAssignedStockIdForWebsite->execute($oldWebsiteCode);

        $this->updateWebsiteCode($oldWebsiteCode, $newWebsiteCode);

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
        $this->updateWebsiteCode($newWebsiteCode, $oldWebsiteCode);
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

    /**
     * @param string $oldWebsiteCode
     * @param string $newWebsiteCode
     * @throws \Exception
     */
    private function updateWebsiteCode(string $oldWebsiteCode, string $newWebsiteCode)
    {
        $website = $this->websiteModel->load($oldWebsiteCode, 'code');
        $website->setCode($newWebsiteCode);
        $website->save();
    }
}
