<?php
/**
 * Copyright :copyright: Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Integration;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryCatalogApi\Api\SourceAssignInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class SourceAssignTest extends TestCase
{
    /**
     * @var SourceAssignInterface
     */
    private $sourceAssign;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    public function setUp()
    {
        parent::setUp();
        $this->sourceAssign = Bootstrap::getObjectManager()->get(SourceAssignInterface::class);
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
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/source_items_on_default_source.php
     * @magentoDbIsolation enabled
     */
    public function testSourceAssignment()
    {
        $this->sourceAssign->execute('SKU-1', 'eu-1');
        $this->sourceAssign->execute('SKU-2', 'eu-1');

        $sourceItemCodes = $this->getSourceItemCodesBySku('SKU-1');
        self::assertContains(
            'eu-1',
            $sourceItemCodes,
            'Mass source assignment failed with a single source item'
        );

        $sourceItemCodes = $this->getSourceItemCodesBySku('SKU-2');
        self::assertContains(
            'eu-1',
            $sourceItemCodes,
            'Mass source assignment failed with a single source item'
        );
    }
}
