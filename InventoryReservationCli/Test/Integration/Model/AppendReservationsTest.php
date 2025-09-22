<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryReservationCli\Test\Integration\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ObjectManager;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryReservationCli\Command\Input\GetReservationFromCompensationArgument;
use Magento\InventoryReservationCli\Model\GetSalableQuantityInconsistencies;
use Magento\InventoryReservationsApi\Model\AppendReservationsInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Verify compensations will be created correctly for missing reservations.
 */
class AppendReservationsTest extends TestCase
{
    /**
     * @var GetSalableQuantityInconsistencies
     */
    private $getSalableQuantityInconsistencies;

    /**
     * @var GetReservationFromCompensationArgument
     */
    private $getReservationFromCompensationArgument;

    /**
     * @var AppendReservationsInterface
     */
    private $appendReservations;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->getSalableQuantityInconsistencies = Bootstrap::getObjectManager()
            ->get(GetSalableQuantityInconsistencies::class);
        $this->getReservationFromCompensationArgument = Bootstrap::getObjectManager()
            ->get(GetReservationFromCompensationArgument::class);
        $this->appendReservations = Bootstrap::getObjectManager()->get(AppendReservationsInterface::class);
    }

    /**
     * Verify create-compensations command will correctly compensate qty for configurable product default stock.
     *
     * @magentoDataFixture Magento_InventoryReservationCli::Test/Integration/_files/incomplete_order_without_reservation_configurable_product.php
     *
     * @see https://app.hiptest.com/projects/69435/test-plan/folders/909285/scenarios/3529620
     * @return void
     */
    public function testCompensateMissingReservationsConfigurableProductDefaultStock(): void
    {
        $stockId = Bootstrap::getObjectManager()->get(DefaultStockProviderInterface::class)->getId();
        $inconsistencies = $this->getSalableQuantityInconsistencies();
        $items = reset($inconsistencies)->getItems();
        $argument = '100000001:simple_10:-' . $items['simple_10'] . ':' . $stockId;
        $reservation = $this->getReservationFromCompensationArgument->execute($argument);
        $this->appendReservations->execute([$reservation]);
        $inconsistencies = $this->getSalableQuantityInconsistencies();
        self::assertCount(0, $inconsistencies);
    }

    /**
     * Verify create-compensations will correctly compensate qty for configurable product custom stock.
     *
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/product_configurable.php
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/source_items_configurable.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoDataFixture Magento_InventoryShipping::Test/_files/create_quote_on_us_website.php
     * @magentoDataFixture Magento_InventoryShipping::Test/_files/order_configurable_product.php
     * @magentoDataFixture Magento_InventoryReservationCli::Test/Integration/_files/delete_reservations.php
     * @magentoDbIsolation disabled
     *
     * @see https://app.hiptest.com/projects/69435/test-plan/folders/909285/scenarios/3529695
     * @return void
     */
    public function testCompensateMissingReservationsConfigurableProductCustomStock(): void
    {
        $orderRepository = ObjectManager::getInstance()->get(OrderRepositoryInterface::class);
        $searchCriteriaBuilder = ObjectManager::getInstance()->get(SearchCriteriaBuilder::class);
        $searchCriteria = $searchCriteriaBuilder
            ->addFilter('increment_id', 'created_order_for_test')
            ->create();
        $order = current($orderRepository->getList($searchCriteria)->getItems());
        $inconsistencies = $this->getSalableQuantityInconsistencies();
        $items = reset($inconsistencies)->getItems();
        $argument = $order->getIncrementId() . ':simple_10:-' . $items['simple_10'] . ':20';
        $reservation = $this->getReservationFromCompensationArgument->execute($argument);
        $this->appendReservations->execute([$reservation]);
        $inconsistencies = $this->getSalableQuantityInconsistencies();
        self::assertCount(0, $inconsistencies);
    }

    /**
     * Verify create-compensations command handles SKUs with colons and semicolons correctly.
     * This tests the core functionality of PR #3404 and extends it to cover semicolons.
     *
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/websites_with_stores.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventorySalesApi::Test/_files/stock_website_sales_channels.php
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/product_configurable.php
     * @magentoDataFixture Magento_InventoryConfigurableProduct::Test/_files/source_items_configurable.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     * @magentoDataFixture Magento_InventoryShipping::Test/_files/create_quote_on_us_website.php
     * @magentoDataFixture Magento_InventoryShipping::Test/_files/order_configurable_product.php
     * @magentoDbIsolation disabled
     *
     * @return void
     */
    public function testCompensateMissingReservationsWithSpecialCharactersInSku(): void
    {
        $orderRepository = ObjectManager::getInstance()->get(OrderRepositoryInterface::class);
        $searchCriteriaBuilder = ObjectManager::getInstance()->get(SearchCriteriaBuilder::class);
        
        $searchCriteria = $searchCriteriaBuilder
            ->addFilter('increment_id', 'created_order_for_test')
            ->create();
        
        $order = current($orderRepository->getList($searchCriteria)->getItems());
        $this->assertNotFalse($order, 'Order should exist for testing');
        
        // Test various argument formats with SKUs containing colons and semicolons
        $testCases = [
            'simple_colon' => [
                'sku' => 'test:product:with:colons',
                'quantity' => 2.0,
                'stockId' => 1
            ],
            'complex_colon' => [
                'sku' => 'brand:category:product:variant',
                'quantity' => 1.0,
                'stockId' => 1
            ],
            'leading_colon' => [
                'sku' => ':product_with_leading_colon',
                'quantity' => 1.5,
                'stockId' => 1
            ],
            'simple_semicolon' => [
                'sku' => 'test;product;with;semicolons',
                'quantity' => 3.0,
                'stockId' => 1
            ],
            'complex_semicolon' => [
                'sku' => 'brand;category;product;variant',
                'quantity' => 2.5,
                'stockId' => 1
            ],
            'mixed_special_chars' => [
                'sku' => 'product:variant;version-1.0_test',
                'quantity' => 1.0,
                'stockId' => 1
            ],
            'leading_semicolon' => [
                'sku' => ';product_with_leading_semicolon',
                'quantity' => 0.5,
                'stockId' => 1
            ]
        ];
        
        foreach ($testCases as $testName => $testData) {
            $argument = sprintf(
                '%s:%s:-%s:%d',
                $order->getIncrementId(),
                $testData['sku'],
                $testData['quantity'],
                $testData['stockId']
            );
            
            try {
                $reservation = $this->getReservationFromCompensationArgument->execute($argument);
                
                // Verify the reservation was created correctly
                $this->assertInstanceOf(
                    \Magento\InventoryReservationsApi\Model\ReservationInterface::class,
                    $reservation,
                    "Failed to create reservation for test case: {$testName}"
                );
                
                $this->assertEquals(
                    $testData['sku'],
                    $reservation->getSku(),
                    "SKU mismatch for test case: {$testName}"
                );
                
                $this->assertEquals(
                    -$testData['quantity'],
                    $reservation->getQuantity(),
                    "Quantity mismatch for test case: {$testName}"
                );
                
                $this->assertEquals(
                    $testData['stockId'],
                    $reservation->getStockId(),
                    "Stock ID mismatch for test case: {$testName}"
                );
                
            } catch (\Exception $e) {
                $this->fail(
                    "Failed to process argument with special character SKU for test case '{$testName}': " . 
                    $e->getMessage() . " | Argument: {$argument}"
                );
            }
        }
    }

    /**
     * Load current Inconsistencies
     *
     * @return array
     */
    private function getSalableQuantityInconsistencies(): array
    {
        $items = [];
        foreach ($this->getSalableQuantityInconsistencies->execute() as $bunch) {
            $items += $bunch;
        }

        return $items;
    }
}
