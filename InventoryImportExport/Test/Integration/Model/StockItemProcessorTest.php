<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Test\Integration\Model;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\CatalogImportExport\Model\StockItemProcessorInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Validation\ValidationException;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\Data\SourceItemSearchResultsInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceItemsDeleteInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Tests for StockItemProcessor class.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 */
class StockItemProcessorTest extends TestCase
{
    /** @var ObjectManagerInterface */
    private $objectManager;

    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    /**
     * @var DefaultSourceProviderInterface
     */
    private $defaultSourceProvider;

    /**
     * @var StockItemProcessorInterface
     */
    private $importer;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $searchCriteriaBuilderFactory;

    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    /**
     * @var GetProductIdsBySkusInterface $productIdBySku
     */
    private $productIdBySku;

    /**
     * Setup Test for Stock Item Importer
     */
    public function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->fixtures = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();

        $this->defaultSourceProvider = $this->objectManager->get(
            DefaultSourceProviderInterface::class
        );
        $this->importer = $this->objectManager->get(
            StockItemProcessorInterface::class
        );
        $this->searchCriteriaBuilderFactory = $this->objectManager->get(
            SearchCriteriaBuilderFactory::class
        );
        $this->sourceItemRepository = $this->objectManager->get(
            SourceItemRepositoryInterface::class
        );
        $this->productIdBySku = $this->objectManager->get(
            GetProductIdsBySkusInterface::class
        );
    }

    /**
     * Tests Source Item Import of default source should use
     * MSI Plugin on Magento\Catalog\ImportExport\Model\StockItemProcessor::process()
     *
     *
     * @return void
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws ValidationException
     * @throws NoSuchEntityException
     */
    #[
        DataFixture(ProductFixture::class, ['sku' => 'SKU-1']),
    ]
    public function testSourceItemImportWithDefaultSource(): void
    {
        $productId = $this->productIdBySku->execute(['SKU-1'])['SKU-1'];
        $stockData = [
            'SKU-1' => [
                'qty' => 1,
                'is_in_stock' => SourceItemInterface::STATUS_IN_STOCK,
                'product_id' => $productId,
                'website_id' => 0,
                'stock_id' => 1,
            ]
        ];
        $importedData = [
            'SKU-1' => [
                'sku' => 'SKU-1',
                'name' => 'New Name',
                'qty' => 1,
                'is_in_stock' => SourceItemInterface::STATUS_IN_STOCK,
            ]
        ];

        $this->importer->process($stockData, $importedData);

        $sourceCode = $this->defaultSourceProvider->getCode();
        $compareData = $this->buildDataArray($this->getSourceItemList('SKU-1', $sourceCode)->getItems());
        $expectedData = [
            SourceItemInterface::SKU => 'SKU-1',
            SourceItemInterface::QUANTITY => 1.0,
            SourceItemInterface::SOURCE_CODE => $sourceCode,
            SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
        ];

        $this->assertArrayHasKey('SKU-1', $compareData);
        $this->assertSame($expectedData, $compareData['SKU-1'][$sourceCode]);
    }

    /**
     * Get List of Source Items which match SKU and Source ID of dummy data
     *
     * @param string $sku
     * @param string|null $sourceCode
     * @return SourceItemSearchResultsInterface
     */
    private function getSourceItemList(string $sku, ?string $sourceCode = null): SourceItemSearchResultsInterface
    {
        /** @var SearchCriteriaBuilder $searchCriteria */
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();

        $searchCriteriaBuilder->addFilter(
            SourceItemInterface::SKU,
            $sku
        );

        if ($sourceCode) {
            $searchCriteriaBuilder->addFilter(
                SourceItemInterface::SOURCE_CODE,
                $sourceCode
            );
        }

        /** @var SearchCriteria $searchCriteria */
        $searchCriteria = $searchCriteriaBuilder->create();

        return $this->sourceItemRepository->getList($searchCriteria);
    }

    /**
     * @param SourceItemInterface[] $sourceItems
     * @return array
     */
    private function buildDataArray(array $sourceItems)
    {
        $comparableArray = [];
        foreach ($sourceItems as $sourceItem) {
            $inventorySourceItem[$sourceItem->getSourceCode()] = [
                SourceItemInterface::SKU => $sourceItem->getSku(),
                SourceItemInterface::QUANTITY => $sourceItem->getQuantity(),
                SourceItemInterface::SOURCE_CODE => $sourceItem->getSourceCode(),
                SourceItemInterface::STATUS => $sourceItem->getStatus(),
            ];
            $comparableArray[$sourceItem->getSku()] = $inventorySourceItem;
        }

        return $comparableArray;
    }

    /**
     * Test for update existing product when source item having non-default source code.
     *
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_with_source_link.php
     *
     * @return void
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws ValidationException
     */
    #[
        DataFixture(ProductFixture::class, ['sku' => 'simple'], 'p1'),
    ]
    public function testSourceItemImportWithNonDefaultExistingSources(): void
    {
        $sku = $this->fixtures->get('p1')->getSku();
        $productId = $this->fixtures->get('p1')->getId();
        $sourceCode = 'source-code-1';
        $quantity = 25.0;

        // Unassign created product from default Source
        $this->unassignInventorySourceItems($sku);

        // Add new inventory source item other than default source
        $this->addInventorySourceItem($sourceCode, $sku, $quantity);

        // Now try to add
        $stockData = [
            'simple' => [
                'qty' => 0,
                'is_in_stock' => SourceItemInterface::STATUS_OUT_OF_STOCK,
                'product_id' => $productId,
                'website_id' => 0,
                'stock_id' => 1,
            ]
        ];
        $importedData = [
            'simple' => [
                'sku' => 'simple',
                'name' => 'New Name',
            ]
        ];

        $this->importer->process($stockData, $importedData);

        $compareData = $this->buildDataArray($this->getSourceItemList($sku, $sourceCode)->getItems());
        $expectedData = [
            SourceItemInterface::SKU => $sku,
            SourceItemInterface::QUANTITY => $quantity,
            SourceItemInterface::SOURCE_CODE => $sourceCode,
            SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
        ];

        $this->assertArrayHasKey($sku, $compareData);
        $this->assertArrayNotHasKey($this->defaultSourceProvider->getCode(), $compareData[$sku]);
        $this->assertSame($expectedData, $compareData[$sku][$sourceCode]);
    }

    /**
     * Delete all the source items for existing product which matches the given SKU
     *
     * @param string $sku
     * @return void
     */
    private function unassignInventorySourceItems(string $sku): void
    {
        $sourceItemsDelete = $this->objectManager->get(SourceItemsDeleteInterface::class);
        $sourceItems = $this->getSourceItemList($sku)->getItems();
        if (count($sourceItems)) {
            $sourceItemsDelete->execute($sourceItems);
        }
    }

    /**
     * Add a new source item entry for existing product
     *
     * @param string $sourceCode
     * @param string $sku
     * @param float|int $qty
     * @return void
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws ValidationException
     */
    private function addInventorySourceItem(string $sourceCode, string $sku, float|int $qty): void
    {
        /** @var DataObjectHelper $dataObjectHelper */
        $dataObjectHelper = $this->objectManager->create(DataObjectHelper::class);
        $data = [
            SourceItemInterface::SOURCE_CODE => $sourceCode,
            SourceItemInterface::SKU => $sku,
            SourceItemInterface::QUANTITY => $qty,
            SourceItemInterface::STATUS => SourceItemInterface::STATUS_IN_STOCK,
        ];

        /** @var SourceItemInterfaceFactory $sourceItemFactory */
        $sourceItemFactory = $this->objectManager->get(SourceItemInterfaceFactory::class);
        $sourceItem = $sourceItemFactory->create();
        $dataObjectHelper->populateWithArray($sourceItem, $data, SourceItemInterface::class);

        /** @var SourceItemsSaveInterface $sourceItemSave */
        $sourceItemSave = $this->objectManager->create(SourceItemsSaveInterface::class);
        $sourceItemSave->execute([$sourceItem]);
    }
}
