<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Integration\IsProductSalable;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryConfigurationApi\Api\GetSourceConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\SaveSourceConfigurationInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class BackorderConditionTest extends TestCase
{
    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var SourceItemsSaveInterface
     */
    private $sourceItemsSave;

    /**
     * @var IsProductSalableInterface
     */
    private $isProductSalable;

    /**
     * @var GetSourceConfigurationInterface
     */
    private $getSourceConfiguration;

    /**
     * @var SaveSourceConfigurationInterface
     */
    private $saveSourceConfiguration;

    protected function setUp()
    {
        $this->sourceItemRepository = Bootstrap::getObjectManager()->get(SourceItemRepositoryInterface::class);
        $this->searchCriteriaBuilder = Bootstrap::getObjectManager()->get(SearchCriteriaBuilder::class);
        $this->sourceItemsSave = Bootstrap::getObjectManager()->get(SourceItemsSaveInterface::class);
        $this->isProductSalable = Bootstrap::getObjectManager()->get(IsProductSalableInterface::class);
        $this->getSourceConfiguration = Bootstrap::getObjectManager()->get(GetSourceConfigurationInterface::class);
        $this->saveSourceConfiguration = Bootstrap::getObjectManager()->get(SaveSourceConfigurationInterface::class);
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     *
     * @magentoDbIsolation disabled
     */
    public function testBackorderedZeroQtyProductIsSalable()
    {
        $sourceItemConfiguration = $this->getSourceConfiguration->forSourceItem('SKU-2', 'us-1');
        $sourceItemConfiguration->setBackorders(1);
        $this->saveSourceConfiguration->forSourceItem('SKU-2', 'us-1', $sourceItemConfiguration);

        $sourceItem = $this->getSourceItemBySku('SKU-2');
        $sourceItem->setQuantity(-15);
        $this->sourceItemsSave->execute([$sourceItem]);

        $this->assertTrue($this->isProductSalable->execute('SKU-2', 20));
        $this->assertTrue($this->isProductSalable->execute('SKU-2', 30));
    }

    /**
     * @param string $sku
     * @return SourceItemInterface
     */
    private function getSourceItemBySku(string $sku): SourceItemInterface
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('sku', $sku)
            ->create();
        $sourceItemSearchResult = $this->sourceItemRepository->getList($searchCriteria);

        return current($sourceItemSearchResult->getItems());
    }
}
