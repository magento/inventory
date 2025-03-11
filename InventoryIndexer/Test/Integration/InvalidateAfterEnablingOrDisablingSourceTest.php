<?php
/**
 * Copyright 2024 Adobe
 * All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Test\Integration;

use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryIndexer\Indexer\InventoryIndexer;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Tests Indexer invalidation after Source enabled or disabled.
 */
class InvalidateAfterEnablingOrDisablingSourceTest extends TestCase
{
    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepository;

    /**
     * @var IndexerInterface
     */
    private $indexer;

    protected function setUp(): void
    {
        $this->sourceRepository = Bootstrap::getObjectManager()->get(SourceRepositoryInterface::class);
        /** @var IndexerRegistry $indexerRegistry */
        $indexerRegistry = Bootstrap::getObjectManager()->get(IndexerRegistry::class);
        $this->indexer = $indexerRegistry->get(InventoryIndexer::INDEXER_ID);
    }

    /**
     * Tests Source enabling and disabling when both Stocks and Source Items are connected to current Source.
     *
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     *
     * @dataProvider indexerInvalidationDataProvider
     * @param string $sourceCode
     * @param bool $enable
     * @param bool $expectedValid
     *
     * @magentoDbIsolation disabled
     */
    public function testIndexerInvalidation(string $sourceCode, bool $enable, bool $expectedValid)
    {
        $this->setSourceEnabledStatus($sourceCode, $enable);

        $this->assertEquals($expectedValid, $this->indexer->isValid());
    }

    /**
     * @return array
     */
    public static function indexerInvalidationDataProvider(): array
    {
        return [
            ['eu-1', true, true],
            ['eu-1', false, false],
            ['eu-disabled', true, false],
            ['eu-disabled', false, true],
        ];
    }

    /**
     * Tests Source enabling and disabling when no Stocks or Source Items are connected to Source.
     *
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     *
     * @dataProvider sourceDoesNotHaveAllRelationsDataProvider
     * @param string $sourceCode
     * @param bool $enable
     * @param bool $expectedValid
     */
    public function testIndexerInvalidationIfSourceDoesNotHaveAnyRelations(
        string $sourceCode,
        bool $enable,
        bool $expectedValid
    ) {
        $this->setSourceEnabledStatus($sourceCode, $enable);

        $this->assertEquals($expectedValid, $this->indexer->isValid());
    }

    /**
     * Tests Source enabling and disabling when no Stocks are connected to current Source.
     *
     * @magentoDataFixture Magento_InventoryApi::Test/_files/products.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/source_items.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     *
     * @dataProvider sourceDoesNotHaveAllRelationsDataProvider
     * @param string $sourceCode
     * @param bool $enable
     * @param bool $expectedValid
     */
    public function testIndexerInvalidationIfSourceDoesNotHaveStockLinks(
        string $sourceCode,
        bool $enable,
        bool $expectedValid
    ) {
        $this->setSourceEnabledStatus($sourceCode, $enable);

        $this->assertEquals($expectedValid, $this->indexer->isValid());
    }

    /**
     * Tests Source enabling and disabling when no Source Items are connected to current Source.
     *
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stocks.php
     * @magentoDataFixture Magento_InventoryApi::Test/_files/stock_source_links.php
     * @magentoDataFixture Magento_InventoryIndexer::Test/_files/reindex_inventory.php
     *
     * @dataProvider sourceDoesNotHaveAllRelationsDataProvider
     * @param string $sourceCode
     * @param bool $enable
     * @param bool $expectedValid
     *
     * @magentoDbIsolation disabled
     */
    public function testIndexerInvalidationIfSourceDoesNotHaveSourceItems(
        string $sourceCode,
        bool $enable,
        bool $expectedValid
    ) {
        $this->setSourceEnabledStatus($sourceCode, $enable);

        $this->assertEquals($expectedValid, $this->indexer->isValid());
    }

    /**
     * @return array
     */
    public static function sourceDoesNotHaveAllRelationsDataProvider(): array
    {
        return [
            ['eu-1', true, true],
            ['eu-1', false, true],
            ['eu-disabled', true, true],
            ['eu-disabled', false, true],
        ];
    }

    /**
     * @param string $sourceCode
     * @param bool $enable
     * @return void
     */
    private function setSourceEnabledStatus(string $sourceCode, bool $enable)
    {
        $source = $this->sourceRepository->get($sourceCode);
        $source->setEnabled($enable);
        $this->sourceRepository->save($source);
    }
}
