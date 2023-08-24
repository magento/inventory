<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Test\Integration\Model\Import;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\Source\Csv;
use Magento\ImportExport\Model\ResourceModel\Import\Data;
use Magento\ImportExport\Model\ResourceModel\Import\Data as ImportData;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryImportExport\Model\Import\Sources;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SourcesTest extends TestCase
{
    /**
     * @var Sources
     */
    private $importer;

    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $importDataMock;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Import
     */
    private $import;

    /**
     * @var Data
     */
    private $dataSourceModel;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->importDataMock = $this->getMockBuilder(ImportData::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->importer = Bootstrap::getObjectManager()->create(Sources::class, [
            'importData' => $this->importDataMock
        ]);
        $this->filesystem = Bootstrap::getObjectManager()->get(
            Filesystem::class
        );

        $this->sourceItemRepository = Bootstrap::getObjectManager()->create(SourceItemRepositoryInterface::class);
        $this->sourceRepository = Bootstrap::getObjectManager()->create(SourceRepositoryInterface::class);
        $this->searchCriteriaBuilder = Bootstrap::getObjectManager()->create(SearchCriteriaBuilder::class);
        $this->import = Bootstrap::getObjectManager()->create(Import::class);
        $this->dataSourceModel = Bootstrap::getObjectManager()->create(Data::class);
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     */
    public function testValidateRowExpectsInvalidRow()
    {
        $rowData = $this->buildRowDataArray('us-2', 'SKU-55', 33, 1);
        $result = $this->importer->validateRow($rowData, 2);
        $this->assertNotTrue($result, 'Expect result FALSE as given source ID is not present in database.');
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     */
    public function testValidateRowExpectsValidRow()
    {
        $rowData = $this->buildRowDataArray('eu-2', 'SKU-1', 33, 1);
        $result = $this->importer->validateRow($rowData, 2);
        $this->assertTrue($result, 'Expect result TRUE as given data is valid.');
    }

    public function testImportDataWithWrongBehavior()
    {
        $this->importer->setParameters([
            'behavior' => 'WrongBehavior'
        ]);

        $this->assertEquals($this->importer->getBehavior(), \Magento\ImportExport\Model\Import::getDefaultBehavior());
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     *
     * @magentoDbIsolation disabled
     * @see https://app.hiptest.com/projects/69435/test-plan/folders/908874/scenarios/1408728
     */
    public function testImportDataWithAppendBehavior(): void
    {
        $this->importer->setParameters([
            'behavior' => Import::BEHAVIOR_APPEND
        ]);

        $searchCriteria = $this->searchCriteriaBuilder->create();
        $beforeImportData = $this->getSourceItemList($searchCriteria);
        $importer = $this->getImporter(Import::BEHAVIOR_APPEND, '/_files/stock_sources.csv');
        $errors = $importer->validateData();
        $this->assertTrue($errors->getErrorsCount() == 0);
        $this->import->setEntity('stock_sources');
        $this->import->importSource();
        /** @var $dataSourceModel \Magento\ImportExport\Model\ResourceModel\Import\Data */
        $bunch = $this->dataSourceModel->getNextBunch();
        $expectedData = $this->updateDataArrayByBunch($beforeImportData, $bunch);
        $afterImportData = $this->getSourceItemList($searchCriteria);
        $this->assertEquals($expectedData, $afterImportData);
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @see https://app.hiptest.com/projects/69435/test-plan/folders/908874/scenarios/1408729
     *
     * @magentoDbIsolation disabled
     */
    public function testImportDataWithDeleteBehavior()
    {
        $this->importer->setParameters([
            'behavior' => Import::BEHAVIOR_DELETE
        ]);

        $searchCriteria = $this->searchCriteriaBuilder->create();

        $bunch = [
            $this->buildRowDataArray('eu-1', 'SKU-1', 6.88, 1),
            $this->buildRowDataArray('eu-2', 'SKU-1', 5, 1),
        ];
        $this->importData($bunch);

        $afterImportData = $this->getSourceItemList($searchCriteria);

        $this->assertArrayNotHasKey('10-SKU-1', $afterImportData);
        $this->assertArrayNotHasKey('20-SKU-1', $afterImportData);
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @see https://app.hiptest.com/projects/69435/test-plan/folders/908874/scenarios/1465149
     *
     * @magentoDbIsolation disabled
     */
    public function testImportDataWithReplaceBehavior()
    {
        /** @see \Magento\InventoryImportExport\Model\Import\Command\Replace::execute */
        $this->importer->setParameters([
            'behavior' => Import::BEHAVIOR_REPLACE
        ]);

        $bunch = [
            $this->buildRowDataArray('eu-2', 'SKU-1', 5, 1),
            $this->buildRowDataArray('us-1', 'SKU-2', 15, 1),
        ];
        $this->importData($bunch);

        $searchCriteria = $this->searchCriteriaBuilder->create();
        $afterImportData = $this->getSourceItemList($searchCriteria);

        $this->assertArrayHasKey('eu-2-SKU-1', $afterImportData);
        $this->assertArrayHasKey('us-1-SKU-2', $afterImportData);
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     *
     * @magentoDbIsolation disabled
     * @magentoAppIsolation enabled
     */
    public function testImportDataWithReplaceBehaviorNoAffectOtherSources()
    {
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $beforeImportData = $this->getSourceItemList($searchCriteria);
        $this->assertArrayHasKey('eu-1-SKU-1', $beforeImportData);

        /** @see \Magento\InventoryImportExport\Model\Import\Command\Replace::execute */
        $this->importer->setParameters([
            'behavior' => Import::BEHAVIOR_REPLACE
        ]);

        $bunch = [
            $this->buildRowDataArray('eu-2', 'SKU-1', 20, 1),
            $this->buildRowDataArray('us-1', 'SKU-2', 15, 1),
        ];
        $this->importData($bunch);
        $afterImportData = $this->getSourceItemList($searchCriteria);

        // checks whether original source item which has not been imported stays in database
        $this->assertEquals($beforeImportData['eu-1-SKU-1'], $afterImportData['eu-1-SKU-1']);

        $this->assertArrayHasKey('eu-2-SKU-1', $afterImportData);
        $this->assertArrayHasKey('us-1-SKU-2', $afterImportData);
    }

    /**
     * Verify sample file import with Add/Update behaviour.
     *
     * @magentoDataFixture Magento_InventoryImportExport::Test/_files/products_sample_file.php
     * @magentoDataFixture Magento_InventoryImportExport::Test/_files/sources_sample_file.php
     * @magentoDataFixture Magento_InventoryImportExport::Test/_files/source_items_sample_file.php
     * @param array $expectedData
     * @dataProvider getSampleFileExpectedData()
     * @see https://app.hiptest.com/projects/69435/test-plan/folders/908874/scenarios/1418539
     */
    public function testAddUpdateWithSampleFile(array $expectedData): void
    {
        $importer = $this->getImporter(Import::BEHAVIOR_APPEND, '/_files/sample.csv');
        $errors = $importer->validateData();
        $this->assertTrue($errors->getErrorsCount() == 0);
        $importer->importData();
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(
            SourceItemInterface::SOURCE_CODE,
            ['source-1', 'source-2', 'default'],
            'in'
        )->addFilter(
            SourceItemInterface::SKU,
            ['sku1', 'sku2', 'sku3', 'sku4'],
            'in'
        )->create();
        $actualData = $this->getSourceItemList($searchCriteria);
        $this->assertEquals($expectedData, $actualData);
    }

    /**
     * Verify sample file import with Replace behaviour.
     *
     * @magentoDataFixture Magento_InventoryImportExport::Test/_files/products_sample_file.php
     * @magentoDataFixture Magento_InventoryImportExport::Test/_files/sources_sample_file.php
     * @magentoDataFixture Magento_InventoryImportExport::Test/_files/source_items_sample_file.php
     * @param array $expectedData
     * @dataProvider getSampleFileExpectedData()
     * @see https://app.hiptest.com/projects/69435/test-plan/folders/908874/scenarios/1465136
     */
    public function testReplaceWithSampleFile(array $expectedData): void
    {
        $importer = $this->getImporter(Import::BEHAVIOR_REPLACE, '/_files/sample.csv');
        $errors = $importer->validateData();
        $this->assertTrue($errors->getErrorsCount() == 0);
        $importer->importData();
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(
            SourceItemInterface::SOURCE_CODE,
            ['source-1', 'source-2', 'default'],
            'in'
        )->addFilter(
            SourceItemInterface::SKU,
            ['sku1', 'sku2', 'sku3', 'sku4'],
            'in'
        )->create();
        $actualData = $this->getSourceItemList($searchCriteria);
        $this->assertEquals($expectedData, $actualData);
    }

    /**
     * Verify sample file import with Delete behaviour.
     *
     * @magentoDataFixture Magento_InventoryImportExport::Test/_files/products_sample_file.php
     * @magentoDataFixture Magento_InventoryImportExport::Test/_files/sources_sample_file.php
     * @magentoDataFixture Magento_InventoryImportExport::Test/_files/source_items_sample_file.php
     * @throws LocalizedException
     * @magentoDbIsolation enabled
     * @see https://app.hiptest.com/projects/69435/test-plan/folders/908874/scenarios/1465136
     */
    public function testImportSourceWithAppendBehavior()
    {
        $importer = $this->getImporter(Import::BEHAVIOR_APPEND, '/_files/sample.csv');
        $errors = $importer->validateData();
        $this->assertTrue($errors->getErrorsCount() == 0);

        $this->import->setEntity('stock_sources');
        $this->import->importSource();
        $createdItemsCount = $this->import->getCreatedItemsCount();
        $updatedItemsCount = $this->import->getUpdatedItemsCount();
        $deletedItemsCount = $this->import->getDeletedItemsCount();
        $this->assertEquals(2, $createdItemsCount);
        $this->assertEquals(2, $updatedItemsCount);
        $this->assertEquals(0, $deletedItemsCount);
    }

    /**
     * Provides test data for Add/Update and Replace import test with sample file.
     *
     * @return array
     */
    public function getSampleFileExpectedData(): array
    {
        return [
            [
                [
                    'default-sku1' => [
                        'source_code' => 'default',
                        'sku' => 'sku1',
                        'quantity' => 10.1,
                        'status' => 1,
                    ],
                    'default-sku2' => [
                        'source_code' => 'default',
                        'sku' => 'sku2',
                        'quantity' => 10.55,
                        'status' => 1,
                    ],
                    'default-sku3' => [
                        'source_code' => 'default',
                        'sku' => 'sku3',
                        'quantity' => 100.0,
                        'status' => 1,
                    ],
                    'default-sku4' => [
                        'source_code' => 'default',
                        'sku' => 'sku4',
                        'quantity' => 100.0,
                        'status' => 1,
                    ],
                    'source-1-sku1' => [
                        'source_code' => 'source-1',
                        'sku' => 'sku1',
                        'quantity' => 100.0,
                        'status' => 1,
                    ],
                    'source-1-sku2' => [
                        'source_code' => 'source-1',
                        'sku' => 'sku2',
                        'quantity' => 100.0,
                        'status' => 1,
                    ],
                    'source-2-sku1' => [
                        'source_code' => 'source-2',
                        'sku' => 'sku1',
                        'quantity' => 100.0,
                        'status' => 1,
                    ],
                    'source-2-sku2' => [
                        'source_code' => 'source-2',
                        'sku' => 'sku2',
                        'quantity' => 100.0,
                        'status' => 1,
                    ],
                    'source-1-sku3' => [
                        'source_code' => 'source-1',
                        'sku' => 'sku3',
                        'quantity' => 10.0,
                        'status' => 1,
                    ],
                    'source-2-sku4' => [
                        'source_code' => 'source-2',
                        'sku' => 'sku4',
                        'quantity' => 15.0,
                        'status' => 1,
                    ],
                ]
            ]
        ];
    }

    /**
     * Verify sample file import with Delete behaviour.
     *
     * @magentoDataFixture Magento_InventoryImportExport::Test/_files/products_sample_file.php
     * @magentoDataFixture Magento_InventoryImportExport::Test/_files/sources_sample_file.php
     * @magentoDataFixture Magento_InventoryImportExport::Test/_files/source_items_sample_file.php
     * @param array $expectedData
     * @dataProvider getSampleFileExpectedDataDeleteBehavior()
     * @see https://app.hiptest.com/projects/69435/test-plan/folders/908874/scenarios/1465136
     */
    public function testDeleteWithSampleFile(array $expectedData): void
    {
        $importer = $this->getImporter(Import::BEHAVIOR_DELETE, '/_files/sample.csv');
        $errors = $importer->validateData();
        $this->assertTrue($errors->getErrorsCount() == 0);
        $importer->importData();
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(
            SourceItemInterface::SOURCE_CODE,
            ['source-1', 'source-2', 'default'],
            'in'
        )->addFilter(
            SourceItemInterface::SKU,
            ['sku1', 'sku2', 'sku3', 'sku4'],
            'in'
        )->create();
        $actualData = $this->getSourceItemList($searchCriteria);
        $this->assertEquals($expectedData, $actualData);
    }

    /**
     * Provides test data for Delete import test with sample file.
     *
     * @return array
     */
    public function getSampleFileExpectedDataDeleteBehavior(): array
    {
        return [
            [
                [
                    'default-sku3' => [
                        'source_code' => 'default',
                        'sku' => 'sku3',
                        'quantity' => 100.0,
                        'status' => 1,
                    ],
                    'default-sku4' => [
                        'source_code' => 'default',
                        'sku' => 'sku4',
                        'quantity' => 100.0,
                        'status' => 1,
                    ],
                    'source-1-sku1' => [
                        'source_code' => 'source-1',
                        'sku' => 'sku1',
                        'quantity' => 100.0,
                        'status' => 1,
                    ],
                    'source-1-sku2' => [
                        'source_code' => 'source-1',
                        'sku' => 'sku2',
                        'quantity' => 100.0,
                        'status' => 1,
                    ],
                    'source-2-sku1' => [
                        'source_code' => 'source-2',
                        'sku' => 'sku1',
                        'quantity' => 100.0,
                        'status' => 1,
                    ],
                    'source-2-sku2' => [
                        'source_code' => 'source-2',
                        'sku' => 'sku2',
                        'quantity' => 100.0,
                        'status' => 1,
                    ],
                ]
            ]
        ];
    }

    /**
     * @param string $sourceCode
     * @param string $sku
     * @param int $qty
     * @param int $status
     * @return array
     */
    private function buildRowDataArray($sourceCode, $sku, $qty, $status)
    {
        return [
            Sources::COL_SOURCE_CODE => $sourceCode,
            Sources::COL_SKU => $sku,
            Sources::COL_QTY => $qty,
            Sources::COL_STATUS => $status,
        ];
    }

    /**
     * @param SourceItemInterface[] $sourceItems
     * @return array
     */
    private function buildDataArray(array $sourceItems)
    {
        $comparableArray = [];
        foreach ($sourceItems as $sourceItem) {
            $key = sprintf('%s-%s', $sourceItem->getSourceCode(), $sourceItem->getSku());
            $comparableArray[$key] = $this->buildRowDataArray(
                $sourceItem->getSourceCode(),
                $sourceItem->getSku(),
                $sourceItem->getQuantity(),
                $sourceItem->getStatus()
            );
        }
        return $comparableArray;
    }

    /**
     * @param array $data
     * @param array $bunch
     * @return array
     */
    private function updateDataArrayByBunch(array $data, array $bunch)
    {
        foreach ($bunch as $bunchData) {
            $key = sprintf('%s-%s', $bunchData[Sources::COL_SOURCE_CODE], $bunchData[Sources::COL_SKU]);
            $data[$key] = $this->buildRowDataArray(
                $bunchData[Sources::COL_SOURCE_CODE],
                $bunchData[Sources::COL_SKU],
                number_format((float)$bunchData[Sources::COL_QTY], 4),
                $bunchData[Sources::COL_STATUS]
            );
        }
        return $data;
    }

    /**
     * @param \Magento\Framework\Api\SearchCriteria $searchCriteria
     * @return array
     */
    private function getSourceItemList(SearchCriteria $searchCriteria)
    {
        $sourceItems = $this->sourceItemRepository->getList($searchCriteria);
        return $this->buildDataArray($sourceItems->getItems());
    }

    /**
     * @param array $bunch
     * @return void
     */
    private function importData(array $bunch)
    {
        $this->importDataMock->expects($this->any())
            ->method('getNextBunch')
            ->will($this->onConsecutiveCalls($bunch, false));

        $this->importer->importData();
    }

    /**
     * Get source importer for sample file.
     *
     * @param string $behavior
     * @return Sources
     */
    private function getImporter(string $behavior, $path): Sources
    {
        $pathToFile = __DIR__ . $path;
        $importer = Bootstrap::getObjectManager()->create(Sources::class);
        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::ROOT);
        $source = Bootstrap::getObjectManager()->create(
            Csv::class,
            [
                'file' => $pathToFile,
                'directory' => $directory
            ]
        );
        $importer->setParameters([
            'behavior' => $behavior
        ]);
        $importer->setSource($source);

        return $importer;
    }
}
