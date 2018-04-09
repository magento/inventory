<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Integration\Model;

use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
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

    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    protected function setUp()
    {
        parent::setUp();

        $this->migrateToMultiSource = Bootstrap::getObjectManager()->get(MigrateToMultiSource::class);
        $this->sourceItemsRepository = Bootstrap::getObjectManager()->get(SourceItemRepositoryInterface::class);
        $this->searchCriteriaBuilderFactory = Bootstrap::getObjectManager()->get(SearchCriteriaBuilderFactory::class);
        $this->sourceRepository = Bootstrap::getObjectManager()->get(SourceRepositoryInterface::class);
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryCatalog/Test/_files/source_items_on_default_source.php
     */
    public function testExecute()
    {
        $skus = ['SKU-1', 'SKU-2'];
        $defaultSource = 'default';
        $newSource = 'source-code-1';

        $this->migrateToMultiSource->execute($skus, $newSource);

        $sourceItemsInDefault = $this->findSourceItemsWithSkuInSouceWithCode($skus, $defaultSource);
        $sourceItemsInNewSource = $this->findSourceItemsWithSkuInSouceWithCode($skus, $newSource);

        self::assertCount(0, $sourceItemsInDefault, 'Source items should have been migrated out of default');
        self::assertCount(2, $sourceItemsInNewSource, 'Source items should have been migrated to the new source');
    }

    /**
     * @param string[] $skus
     * @param string $sourceCode
     * @return SourceItemInterface[]
     */
    public function findSourceItemsWithSkuInSouceWithCode(array $skus, string $sourceCode): array
    {
        $criteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteria = $criteriaBuilder
            ->addFilter(SourceItemInterface::SKU, $skus, 'in')
            ->addFilter(SourceItemInterface::SOURCE_CODE, $sourceCode)
            ->create();

        return $this->sourceItemsRepository->getList($searchCriteria)->getItems();
    }

}