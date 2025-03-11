<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Test\Integration\Model\Export;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\ImportExport\Model\Export;
use Magento\ImportExport\Model\Export\Adapter\Csv;
use Magento\InventoryImportExport\Model\Export\Sources;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class SourcesTest extends TestCase
{
    /**
     * @var Sources
     */
    private $exporter;

    /**
     * @var string
     */
    private $exportFilePath;

    /**
     * @var Filesystem\Directory\WriteInterface
     */
    private $directory;

    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->exportFilePath = uniqid('test-export_', false) . '.csv';

        $this->directory = $objectManager->get(Filesystem::class)->getDirectoryWrite(DirectoryList::VAR_IMPORT_EXPORT);
        $this->exporter = $objectManager->create(Sources::class);
        $this->exporter->setWriter($objectManager->create(
            Csv::class,
            ['destination' => $this->exportFilePath]
        ));
    }

    protected function tearDown(): void
    {
        $this->directory->delete($this->exportFilePath);
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @see https://app.hiptest.com/projects/69435/test-plan/folders/908874/scenarios/1411267
     */
    public function testExportWithoutAnyFiltering()
    {
        $this->exporter->setParameters([]);
        $this->exporter->export();

        $exportFullLines = file(
            implode(DIRECTORY_SEPARATOR, [__DIR__, '_files', 'export_full.csv']),
            FILE_IGNORE_NEW_LINES
        );

        $exportContent = $this->directory->readFile($this->exportFilePath);
        foreach ($exportFullLines as $expectedLine) {
            $this->assertStringContainsString($expectedLine, $exportContent);
        }
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @see https://app.hiptest.com/projects/69435/test-plan/folders/908874/scenarios/1411538
     */
    public function testExportWithSkuFilter()
    {
        $this->exporter->setParameters([
            Export::FILTER_ELEMENT_GROUP => [
                'sku' => 'SKU-1'
            ]
        ]);
        $this->exporter->export();

        $this->assertEquals(
            file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, '_files', 'export_filtered_by_sku.csv'])),
            $this->directory->readFile($this->exportFilePath)
        );
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     */
    public function testExportWithSkuFilterByLikeQuery()
    {
        $this->exporter->setParameters([
            Export::FILTER_ELEMENT_GROUP => [
                'sku' => 'U-1'
            ]
        ]);
        $this->exporter->export();

        $this->assertEquals(
            file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, '_files', 'export_filtered_by_sku.csv'])),
            $this->directory->readFile($this->exportFilePath)
        );
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     */
    public function testExportWithSourceFilter()
    {
        $this->exporter->setParameters([
            Export::FILTER_ELEMENT_GROUP => [
                'source_code' => 'eu'
            ]
        ]);
        $this->exporter->export();

        $this->assertEquals(
            file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, '_files', 'export_filtered_by_source.csv'])),
            $this->directory->readFile($this->exportFilePath)
        );
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @dataProvider exportWithWebsiteFilterDataProvider
     * @param int $websiteId
     * @param string $expectedOutput
     */
    public function testExportWithWebsiteFilter(int $websiteId, string $expectedOutput)
    {
        $this->exporter->setParameters([
            Export::FILTER_ELEMENT_GROUP => [
                'website_id' => [$websiteId]
            ]
        ]);
        $this->exporter->export();

        $this->assertEquals(
            file_get_contents(implode(DIRECTORY_SEPARATOR, [__DIR__, '_files', $expectedOutput])),
            $this->directory->readFile($this->exportFilePath)
        );
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     */
    public function testExportFilteredWithoutStatusColumn()
    {
        $this->exporter->setParameters([
            Export::FILTER_ELEMENT_GROUP => [
                'sku' => 'SKU-1',
                'status' => 1
            ],
            Export::FILTER_ELEMENT_SKIP => [
                'status'
            ]
        ]);
        $this->exporter->export();

        $this->assertEquals(
            file_get_contents(implode(DIRECTORY_SEPARATOR, [
                __DIR__,
                '_files',
                'export_filtered_without_status_column.csv'
            ])),
            $this->directory->readFile($this->exportFilePath)
        );
    }

    public static function exportWithWebsiteFilterDataProvider()
    {
        return [
            [0, 'export_empty.csv'],
        ];
    }
}
