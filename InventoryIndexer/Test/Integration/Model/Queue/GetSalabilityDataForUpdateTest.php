<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Test\Integration\Model\Queue;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Framework\App\ResourceConnection;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
use Magento\InventoryIndexer\Model\Queue\GetSalabilityDataForUpdate;
use Magento\InventoryIndexer\Model\Queue\ReservationData;
use Magento\TestFramework\Fixture\AppIsolation;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class GetSalabilityDataForUpdateTest extends TestCase
{
    /**
     * @var DataFixtureStorage
     */
    private DataFixtureStorage $fixtures;

    /**
     * @var GetSalabilityDataForUpdate
     */
    private GetSalabilityDataForUpdate $model;

    /**
     * @var DefaultStockProviderInterface
     */
    private DefaultStockProviderInterface $defaultStockProvider;

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    protected function setUp(): void
    {
        $this->fixtures = DataFixtureStorageManager::getStorage();
        $objectManager = Bootstrap::getObjectManager();
        $this->model = $objectManager->get(GetSalabilityDataForUpdate::class);
        $this->defaultStockProvider = $objectManager->get(DefaultStockProviderInterface::class);
        $this->resourceConnection = $objectManager->get(ResourceConnection::class);
    }

    #[
        DbIsolation(false),
        AppIsolation(true),
        DataFixture(ProductFixture::class, ['sku' => 'existing-simple'], 'existingProduct'),
    ]
    public function testExecuteSkipsMissingProducts(): void
    {
        $stockId = (int) $this->defaultStockProvider->getId();
        $reservationData = new ReservationData(['existing-simple', 'missing-sku'], $stockId);

        $result = $this->model->execute($reservationData);

        self::assertIsArray($result);
        self::assertArrayNotHasKey('missing-sku', $result);
    }

    #[
        DbIsolation(false),
        AppIsolation(true),
        DataFixture(ProductFixture::class, ['sku' => 'with-stock-status'], 'withStatus'),
        DataFixture(ProductFixture::class, ['sku' => 'without-stock-status'], 'withoutStatus'),
    ]
    public function testExecuteHandlesSkuWithoutCurrentStockStatus(): void
    {
        $stockId = (int) $this->defaultStockProvider->getId();

        $productWithoutStatus = $this->fixtures->get('withoutStatus');
        $connection = $this->resourceConnection->getConnection();
        $stockStatusTable = $this->resourceConnection->getTableName('cataloginventory_stock_status');
        $connection->delete($stockStatusTable, ['product_id = ?' => (int)$productWithoutStatus->getId()]);

        $reservationData = new ReservationData(['with-stock-status', 'without-stock-status'], $stockId);

        $result = $this->model->execute($reservationData);

        self::assertIsArray($result);
    }
}
