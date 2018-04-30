<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Test\Integration\SourceItem;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Inventory\Model\GetStockItemDataInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Tests Stock Item validation.
 *
 * @magentoAppIsolation enabled
 * @magentoDbIsolation enabled
 */
class StockItemValidationTest extends TestCase
{
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->searchCriteriaBuilder = Bootstrap::getObjectManager()->get(SearchCriteriaBuilder::class);
        $this->sourceItemRepository = Bootstrap::getObjectManager()->get(SourceItemRepositoryInterface::class);
    }

    /**
     * Tests Stock Item quantity and status when quantity is not set while saving.
     *
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items_blank_quantity.php
     *
     * @dataProvider stockItemBlankQuantityDataProvider
     */
    public function testStockItemBlankQuantity($sourceCode, $sku, $quantity, $status)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(SourceItemInterface::SOURCE_CODE, $sourceCode)
            ->addFilter(SourceItemInterface::SKU, $sku)
            ->create();
        $sourceItemSearchResult = $this->sourceItemRepository->getList($searchCriteria);
        $stockItems = $sourceItemSearchResult->getItems();

        foreach ($stockItems as $sourceItem) {
            self::assertEquals(
                $quantity,
                $sourceItem->getQuantity()
            );
            self::assertEquals(
                $status,
                $sourceItem->getStatus()
            );
        }
    }

    /**
     * Data provider for testStockItemBlankQuantity.
     *
     * @return array
     */
    public function stockItemBlankQuantityDataProvider()
    {
        return [
            [
                'source_code' => 'eu-1',
                'sku' => 'SKU-1',
                'quantity' => 0,
                'status' => 1
            ],
            [
                'source_code' => 'eu-2',
                'sku' => 'SKU-1',
                'quantity' => 0,
                'status' => 0
            ],
            [
                'source_code' => 'eu-3',
                'sku' => 'SKU-1',
                'quantity' => 0,
                'status' => 1
            ],
            [
                'source_code' => 'eu-disabled',
                'sku' => 'SKU-1',
                'quantity' => 0,
                'status' => 0
            ],
        ];
    }
}
