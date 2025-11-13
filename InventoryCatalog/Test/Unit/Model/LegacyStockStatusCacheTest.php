<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Unit\Model;

use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockStatusCriteriaInterface;
use Magento\CatalogInventory\Api\StockStatusCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockStatusRepositoryInterface;
use Magento\InventoryCatalog\Model\Cache\LegacyStockStatusStorage;
use Magento\InventoryCatalog\Model\LegacyStockStatusCache;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test class for LegacyStockStatusCache
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class LegacyStockStatusCacheTest extends TestCase
{
    /** @var StockStatusRepositoryInterface|MockObject */
    private $stockStatusRepository;

    /** @var StockStatusCriteriaInterfaceFactory|MockObject */
    private $criteriaFactory;

    /** @var StockConfigurationInterface|MockObject */
    private $stockConfiguration;

    /** @var LegacyStockStatusStorage|MockObject */
    private $legacyStockStatusStorage;

    /** @var LegacyStockStatusCache */
    private $model;

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        $this->stockStatusRepository = $this->createMock(StockStatusRepositoryInterface::class);
        $this->criteriaFactory = $this->createMock(StockStatusCriteriaInterfaceFactory::class);
        $this->stockConfiguration = $this->createMock(StockConfigurationInterface::class);
        $this->legacyStockStatusStorage = $this->createMock(LegacyStockStatusStorage::class);

        $this->model = new LegacyStockStatusCache(
            $this->stockStatusRepository,
            $this->criteriaFactory,
            $this->stockConfiguration,
            $this->legacyStockStatusStorage
        );
    }

    /**
     * @return void
     */
    public function testExecuteSkipsAlreadyCachedProducts(): void
    {
        $productId = 100;
        $scopeId = 1;

        $this->stockConfiguration
            ->method('getDefaultScopeId')
            ->willReturn($scopeId);

        $this->legacyStockStatusStorage
            ->method('get')
            ->with($productId, $scopeId);

        $this->stockStatusRepository
            ->expects($this->never())
            ->method('getList');

        $this->model->execute([$productId]);
    }

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testExecuteFetchesAndCachesForNonCachedProducts(): void
    {
        $productId = 101;
        $scopeId = 1;

        $this->stockConfiguration
            ->method('getDefaultScopeId')
            ->willReturn($scopeId);

        $this->legacyStockStatusStorage
            ->method('get');

        $criteria = $this->createMock(StockStatusCriteriaInterface::class);
        $this->criteriaFactory
            ->method('create')
            ->willReturn($criteria);

        $collectionMock = $this->createConfiguredMock(
            \Magento\Framework\Api\SearchResults::class,
            ['getItems' => []]
        );

        $this->stockStatusRepository
            ->expects($this->exactly(0))
            ->method('getList')
            ->with($criteria)
            ->willReturn($collectionMock);

        $this->model->execute([$productId]);
    }
}
