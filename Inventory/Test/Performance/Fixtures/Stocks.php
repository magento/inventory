<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Inventory\Test\Performance\Fixtures;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\InventoryApi\Api\Data\SourceInterfaceFactory;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\StockInterfaceFactory;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterface;
use Magento\InventoryApi\Api\Data\StockSourceLinkInterfaceFactory;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventoryApi\Api\StockSourceLinksSaveInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterfaceFactory;
use Magento\Setup\Fixtures\Fixture as AbstractFixture;
use Magento\Setup\Fixtures\FixtureModel;
use Magento\Setup\Fixtures\StoresFixture;

/**
 * Generate sources and stocks.  Assigns stocks to stores.
 * Supports next format:
 * <stocks>{amount of stocks}</stocks>
 * <min_sources_per_stock>{minimum amount of sources per stock}</min_sources_per_stock>
 * <max_sources_per_stock>{maximum amount of sources per stock}</max_sources_per_stock>
 *
 * When assigning the sources to the stocks, we assign between min_sources_per_stock and max_sources_per_stock amount
 * of sources to each stock.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Stocks extends AbstractFixture
{
    private const DEFAULT_SOURCE_COUNT = 1;

    private const DEFAULT_STOCK_COUNT = 1;

    private const DEFAULT_MIN_SOURCES_PER_STOCK = 1;

    private const DEFAULT_MAX_SOURCES_PER_STOCK = 3;

    /**
     * @var int
     */
    protected $priority = 783;

    /**
     * @var SourceInterfaceFactory
     */
    private $sourceInterfaceFactory;

    /**
     * @var SourceRepositoryInterface
     */
    private $sourceRepositoryInterface;

    /**
     * @var StockInterfaceFactory
     */
    private $stockInterfaceFactory;

    /**
     * @var StockRepositoryInterface
     */
    private $stockRepositoryInterface;

    /**
     * @var StockSourceLinkInterfaceFactory
     */
    private $stockSourceLinkInterfaceFactory;

    /**
     * @var StockSourceLinksSaveInterface
     */
    private $stockSourceLinksSaveInterface;

    /**
     * @var SalesChannelInterfaceFactory
     */
    private $salesChannelInterfaceFactory;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param FixtureModel $fixtureModel
     * @param SourceInterfaceFactory $sourceInterfaceFactory
     * @param SourceRepositoryInterface $sourceRepositoryInterface
     * @param StockInterfaceFactory $stockInterfaceFactory
     * @param StockRepositoryInterface $stockRepositoryInterface
     * @param StockSourceLinkInterfaceFactory $stockSourceLinkInterfaceFactory
     * @param StockSourceLinksSaveInterface $stockSourceLinksSaveInterface
     * @param SalesChannelInterfaceFactory $salesChannelInterfaceFactory
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        FixtureModel $fixtureModel,
        SourceInterfaceFactory $sourceInterfaceFactory,
        SourceRepositoryInterface $sourceRepositoryInterface,
        StockInterfaceFactory $stockInterfaceFactory,
        StockRepositoryInterface $stockRepositoryInterface,
        StockSourceLinkInterfaceFactory $stockSourceLinkInterfaceFactory,
        StockSourceLinksSaveInterface $stockSourceLinksSaveInterface,
        SalesChannelInterfaceFactory $salesChannelInterfaceFactory,
        ResourceConnection $resourceConnection
    ) {
        parent::__construct($fixtureModel);
        $this->sourceInterfaceFactory = $sourceInterfaceFactory;
        $this->sourceRepositoryInterface = $sourceRepositoryInterface;
        $this->stockInterfaceFactory = $stockInterfaceFactory;
        $this->stockRepositoryInterface = $stockRepositoryInterface;
        $this->stockSourceLinkInterfaceFactory = $stockSourceLinkInterfaceFactory;
        $this->stockSourceLinksSaveInterface = $stockSourceLinksSaveInterface;
        $this->salesChannelInterfaceFactory = $salesChannelInterfaceFactory;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $sourcesCount = (int)$this->fixtureModel->getValue('sources', self::DEFAULT_SOURCE_COUNT);
        $stocksCount = (int)$this->fixtureModel->getValue('stocks', self::DEFAULT_STOCK_COUNT);
        $minSourcesPerStock = (int)$this->fixtureModel->getValue(
            'min_sources_per_stock',
            self::DEFAULT_MIN_SOURCES_PER_STOCK
        );
        $maxSourcesPerStock = (int)$this->fixtureModel->getValue(
            'max_sources_per_stock',
            self::DEFAULT_MAX_SOURCES_PER_STOCK
        );
        if ($sourcesCount <= self::DEFAULT_SOURCE_COUNT
            && $stocksCount <= self::DEFAULT_STOCK_COUNT
        ) {
            return;
        }
        $sourceIds = $this->getSourceIds();
        $stockIds = $this->getStockIds();
        $newSourceIds = $this->generateSources($sourcesCount, $sourceIds);
        $newStocks = $this->generateStocksAndLinkToSources(
            $stocksCount,
            $minSourcesPerStock,
            $maxSourcesPerStock,
            $newSourceIds,
            $stockIds
        );
        $this->associateStocksToWebsites($newStocks);
    }

    /**
     * Gets the existing source ids
     * @return array
     */
    private function getSourceIds()
    {
        $sources = $this->sourceRepositoryInterface->getList();
        $sourceIds = [];
        foreach ($sources->getItems() as $source) {
            $sourceIds[] = $source->getId();
        }
        return $sourceIds;
    }

    /**
     * Gets the existing stock ids
     * @return array
     */
    private function getStockIds()
    {
        $stocks = $this->stockRepositoryInterface->getList();
        $stockIds = [];
        foreach ($stocks->getItems() as $source) {
            $stockIds[] = $source->getId();
        }
        return $stockIds;
    }

    /**
     * Generates new sources and returns new source ids.
     * @param array $sourceIds
     * @return array
     */
    private function generateSources($sourcesCount, array $sourceIds)
    {
        $existedSourcesCount = count($sourceIds);
        $newSourceIds = [];
        while ($existedSourcesCount < $sourcesCount) {
            $source = $this->sourceInterfaceFactory->create();
            $sourceCode = sprintf('source_%d', ++$existedSourcesCount);
            $sourceName = $sourceCode;
            $source->setName($sourceName);
            $source->setSourceCode($sourceCode);
            $source->setPostcode('12345');
            $source->setCountryId('abc');
            $this->sourceRepositoryInterface->save($source);
            $this->copySourceItemsFromDefaultIntoSource($sourceCode);
            $newSourceIds[] = $sourceCode;
        }
        return $newSourceIds;
    }

    private function copySourceItemsFromDefaultIntoSource($sourceCode)
    {
        $tableName = $this->resourceConnection->getTableName('inventory_source_item');
        $connection = $this->resourceConnection->getConnection();
        $selectForInsert = $connection
            ->select()
            ->from(
                ['inventory_source_item' => $tableName],
                [
                    SourceItemInterface::SOURCE_CODE => new \Zend_Db_Expr('\'' . $sourceCode . '\''),
                    SourceItemInterface::QUANTITY,
                    SourceItemInterface::STATUS,
                    SourceItemInterface::SKU,
                ]
            )
            ->where(SourceItemInterface::SOURCE_CODE . ' = ?', 'default');
        $sql = $connection->insertFromSelect(
            $selectForInsert,
            $tableName,
            [
                SourceItemInterface::SOURCE_CODE,
                SourceItemInterface::QUANTITY,
                SourceItemInterface::STATUS,
                SourceItemInterface::SKU,
            ],
            Mysql::INSERT_ON_DUPLICATE
        );
        $connection->query($sql);
    }

    private function getWebsiteCodesFromStoresFixture()
    {
        /** @var StoresFixture $storesFixture */
        $storesFixture = $this->fixtureModel->getFixtureByName(StoresFixture::class);
        return $storesFixture->getWebsiteCodes();
    }

    private function generateStocksAndLinkToSources(
        int $stocksCount,
        int $minSourcesPerStock,
        int $maxSourcesPerStock,
        array $newSourceIds,
        array $stockIds
    ) {
        $existedStocksCount = count($stockIds);
        $sourceCount = count($newSourceIds);
        $currentSourcesPerStock = $minSourcesPerStock;
        $sourceOffset = 0;
        $newStocks = [];
        if (($existedStocksCount < $stocksCount) && (0 == $sourceCount)) {
            throw new \Magento\Setup\Exception("Cannot increase stock count without also increasing source count.");
        }
        while ($existedStocksCount < $stocksCount) {
            $stock = $this->stockInterfaceFactory->create();
            $stockCode = ++$existedStocksCount;
            $stockName = "stock_" . $stockCode;
            $stock->setName($stockName);
            $stock->setStockId($stockCode);
            $this->stockRepositoryInterface->save($stock);
            $newStocks[] = $stock;
            $links = [];
            for ($i = 0; $i < $currentSourcesPerStock; $i++) {
                $links[] = $this->stockSourceLinkInterfaceFactory->create(['data' => [
                    StockSourceLinkInterface::STOCK_ID => $stockCode,
                    StockSourceLinkInterface::SOURCE_CODE => $newSourceIds[$sourceOffset],
                    StockSourceLinkInterface::PRIORITY => 1,
                ]]);
                $sourceOffset++;
                $sourceOffset %= $sourceCount;
            }
            $currentSourcesPerStock++;
            if ($currentSourcesPerStock > $maxSourcesPerStock) {
                $currentSourcesPerStock = $minSourcesPerStock;
            }
            $this->stockSourceLinksSaveInterface->execute($links);
        }
        return $newStocks;
    }

    private function associateStocksToWebsites(array $newStocks)
    {
        $newStocksCount = count($newStocks);
        if (0 == $newStocksCount) {
            return;
        }
        $websitesInStocks = [];
        $currentStockOffset = 0;
        foreach ($this->getWebsiteCodesFromStoresFixture() as $websiteCode) {
            if (!array_key_exists($currentStockOffset, $websitesInStocks)) {
                $websitesInStocks[$currentStockOffset] = [];
            }
            $websitesInStocks[$currentStockOffset][] = $websiteCode;
            $currentStockOffset++;
            $currentStockOffset %= $newStocksCount;
        }
        foreach ($websitesInStocks as $stockOffset => $websitesInStock) {
            $salesChannels = [];
            foreach ($websitesInStock as $website) {
                $salesChannel = $this->salesChannelInterfaceFactory->create();
                $salesChannel->setCode($website);
                $salesChannel->setType(SalesChannelInterface::TYPE_WEBSITE);
                $salesChannels[] = $salesChannel;
            }
            $stock = $newStocks[$stockOffset];
            $extensionAttributes = $stock->getExtensionAttributes();
            $extensionAttributes->setSalesChannels($salesChannels);
            $stock->setHasDataChanges(true);
            $this->stockRepositoryInterface->save($stock);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getActionTitle()
    {
        return 'Generating stocks and sources';
    }

    /**
     * {@inheritdoc}
     */
    public function introduceParamLabels()
    {
        return [
            'sources' => 'Number of sources',
            'stocks' => 'Number of stocks',
            'min_sources_per_stock' => 'Minumum number of sources per stock',
            'max_sources_per_stock' => 'Maximum number of sources per stock'
        ];
    }
}
