<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Integration\Bulk;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Test\Fixture\SourceItem as SourceItemFixture;
use Magento\InventoryCatalogApi\Api\BulkSourceUnassignInterface;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class SourceUnassignTest extends TestCase
{
    /**
     * @var BulkSourceUnassignInterface
     */
    private $bulkSourceUnassign;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->bulkSourceUnassign = Bootstrap::getObjectManager()->get(BulkSourceUnassignInterface::class);
        $this->searchCriteriaBuilder = Bootstrap::getObjectManager()->get(SearchCriteriaBuilder::class);
        $this->sourceItemRepository = Bootstrap::getObjectManager()->get(SourceItemRepositoryInterface::class);
    }

    /**
     * @param string $sku
     * @return array
     */
    private function getSourceItemCodesBySku(string $sku): array
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(SourceItemInterface::SKU, $sku)
            ->create();
        $sourceItems = $this->sourceItemRepository->getList($searchCriteria)->getItems();

        $res = [];
        foreach ($sourceItems as $sourceItem) {
            $res[] = $sourceItem->getSourceCode();
        }

        return $res;
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDbIsolation enabled
     */
    public function testBulkSourceUnassignment()
    {
        $skus = ['SKU-1', 'SKU-2', 'SKU-3', 'SKU-4'];
        $sources = ['eu-1', 'eu-2', 'eu-3'];
        $count = $this->bulkSourceUnassign->execute($skus, $sources);

        self::assertEquals(
            5, // Overall 5 deletions
            $count,
            'Products source un-assignment count do not match'
        );

        foreach ($skus as $sku) {
            $sourceItemCodes = $this->getSourceItemCodesBySku($sku);
            foreach ($sources as $source) {
                self::assertNotContains(
                    $source,
                    $sourceItemCodes,
                    'Mass source un-assignment failed'
                );
            }
        }

        $skus = ['SKU-1', 'SKU-2', 'SKU-3', 'SKU-4'];
        $sources = ['eu-1', 'eu-2', 'eu-3'];
        $count = $this->bulkSourceUnassign->execute($skus, $sources);

        self::assertEquals(
            0, // If we run it the second time on the same entries we should have 0 modifications
            $count,
            'Products source un-assignment involved unexpected entries'
        );
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     */
    #[
        DbIsolation(true),
        DataFixture(ProductFixture::class, ['sku' => '01234'], 'product1'),
        DataFixture(ProductFixture::class, ['sku' => '1234'], 'product2'),
        DataFixture(SourceItemFixture::class, ['sku' => '01234', 'source_code' => 'eu-1']),
        DataFixture(SourceItemFixture::class, ['sku' => '1234', 'source_code' => 'eu-1']),
    ]
    public function testBulkSourceUnAssignmentOfProductsWithNumericSku(): void
    {
        $skus = ['01234', '1234'];
        $sources = ['eu-1'];
        $count = $this->bulkSourceUnassign->execute($skus, $sources);

        $this->assertEquals(2, $count, 'Products source un-assignment count do not match');

        foreach ($skus as $sku) {
            $sourceItemCodes = $this->getSourceItemCodesBySku($sku);
            $this->assertNotContains($sources, $sourceItemCodes, 'Mass source un-assignment failed');
        }
    }
}
