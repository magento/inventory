<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Integration\Model;

use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryCatalog\Model\MigrateToMultiSource;
use PHPUnit\Framework\TestCase;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Inventory\Model\ResourceModel\GetAssignedStockIdsBySku;

class MigrateToMultiSourceTest extends TestCase
{
    /**
     * @var MigrateToMultiSource
     */
    private $migrateToMultiSource;

    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemsRepository;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->migrateToMultiSource = Bootstrap::getObjectManager()->get(MigrateToMultiSource::class);
        $this->sourceItemsRepository = Bootstrap::getObjectManager()->get(SourceItemRepositoryInterface::class);
        $this->searchCriteriaBuilderFactory = Bootstrap::getObjectManager()->get(SearchCriteriaBuilderFactory::class);
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/source_items_on_default_source.php
     */
    public function testExecute()
    {
        $this->migrateToMultiSource->execute(['SKU-1', 'SKU-2'], 'source-code-1');

        $criteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteria = $criteriaBuilder
            ->addFilter(SourceItemInterface::SKU, ['SKU-1', 'SKU-2'], 'in')
            ->addFilter(SourceItemInterface::SOURCE_CODE, 'source-code-1')
            ->create();

        $sourceItems = $this->sourceItemsRepository->getList($searchCriteria)->getItems();

        self::assertCount(2, $sourceItems);
    }
}