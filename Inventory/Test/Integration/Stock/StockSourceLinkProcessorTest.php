<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Test\Integration\Stock;

use Magento\Backend\Model\Auth;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\InventoryAdminUi\Model\Stock\StockSourceLinkProcessor;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;
use Magento\InventoryApi\Api\GetStockSourceLinksInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Tests for \Magento\InventoryAdminUi\Model\Stock\StockSourceLinkProcessor.
 *
 * @magentoAppArea adminhtml
 */
class StockSourceLinkProcessorTest extends TestCase
{
    /**
     * @var StockSourceLinkProcessor
     */
    private $stockSourceLinkProcessor;

    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var GetStockSourceLinksInterface
     */
    private $getStockSourceLinks;

    /**
     * @var Auth
     */
    private $auth;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->searchCriteriaBuilder = $objectManager->get(SearchCriteriaBuilder::class);
        $this->getStockSourceLinks = $objectManager->get(GetStockSourceLinksInterface::class);
        $this->stockSourceLinkProcessor = $objectManager->get(StockSourceLinkProcessor::class);
        $this->sortOrderBuilder = $objectManager->get(SortOrderBuilder::class);
        $this->auth = $objectManager->get(Auth::class);
    }

    protected function tearDown(): void
    {
        $this->auth->logout();
        parent::tearDown();
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     */
    public function testProcess()
    {
        $this->auth->login(
            \Magento\TestFramework\Bootstrap::ADMIN_NAME,
            \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD
        );

        $linksData = $this->getLinksData();
        $stockId = 10;

        $this->stockSourceLinkProcessor->process($stockId, $linksData);

        $sortOrder = $this->sortOrderBuilder
            ->setField(StockSourceLinkInterface::PRIORITY)
            ->setAscendingDirection()
            ->create();
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(StockSourceLinkInterface::STOCK_ID, $stockId)
            ->addSortOrder($sortOrder)
            ->create();
        $searchResult = $this->getStockSourceLinks->execute($searchCriteria);

        self::assertCount(2, $searchResult->getItems());
    }

    /**
     * @magentoDataFixture Magento_InventoryAdminUi::Test/Integration/_files/user_assigned_to_stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     */
    public function testProcessUserWithoutPermissions()
    {
        $this->auth->login(
            'stocksAccessUser',
            \Magento\TestFramework\Bootstrap::ADMIN_PASSWORD
        );

        $linksData = $this->getLinksData();
        $stockId = 10;

        $this->expectException(\Magento\Framework\Exception\AuthorizationException::class);
        $this->expectExceptionMessage('It is not allowed to change sources');

        $this->stockSourceLinkProcessor->process($stockId, $linksData);
    }

    /**
     * @return array
     */
    private function getLinksData(): array
    {
        /**
         * eu-3 - should be updated
         * us-1 - should be added
         * eu-2, eu-disabled - should be removed
         */
        $linksData = [
            [
                StockSourceLinkInterface::SOURCE_CODE => 'eu-3',
                StockSourceLinkInterface::PRIORITY => 1,
            ],
            [
                StockSourceLinkInterface::SOURCE_CODE => 'us-1',
                StockSourceLinkInterface::PRIORITY => 2,
            ],
        ];

        return $linksData;
    }
}
