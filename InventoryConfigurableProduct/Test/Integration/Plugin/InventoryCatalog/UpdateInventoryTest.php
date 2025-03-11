<?php
/**
 * Copyright 2024 Adobe
 * All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Test\Integration\Plugin\InventoryCatalog;

use Magento\Catalog\Test\Fixture\Product;
use Magento\CatalogInventory\Model\Stock;
use Magento\ConfigurableProduct\Test\Fixture\Attribute as AttributeFixture;
use Magento\ConfigurableProduct\Test\Fixture\Product as ConfigurableProductFixture;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\InventoryCatalog\Model\UpdateInventory;
use Magento\InventoryCatalog\Model\UpdateInventory\InventoryDataFactory;
use Magento\InventoryIndexer\Model\IsProductSalable;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class UpdateInventoryTest extends TestCase
{
    /**
     * @var UpdateInventory
     */
    private $updateInventory;

    /**
     * @var InventoryDataFactory
     */
    private $inventoryDataFactory;

    /**
     * @var IsProductSalable
     */
    private $isProductSalable;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->updateInventory = Bootstrap::getObjectManager()->get(UpdateInventory::class);
        $this->inventoryDataFactory = Bootstrap::getObjectManager()->get(InventoryDataFactory::class);
        $this->isProductSalable = Bootstrap::getObjectManager()->get(IsProductSalable::class);
        $this->serializer = Bootstrap::getObjectManager()->get(SerializerInterface::class);
        $this->fixtures = DataFixtureStorageManager::getStorage();
    }

    #[
        DataFixture(Product::class, as: 'sp', count: 4),
        DataFixture(AttributeFixture::class, as: 'attr'),
        DataFixture(
            ConfigurableProductFixture::class,
            ['_options' => ['$attr$'], '_links' => ['$sp1$', '$sp2$']],
            'cp1'
        ),
        DataFixture(
            ConfigurableProductFixture::class,
            ['_options' => ['$attr$'], '_links' => ['$sp3$', '$sp4$']],
            'cp2'
        )
    ]
    /**
     * @dataProvider massUpdateConfigurableProductsStockStatusDataProvider
     */
    public function testMassUpdateConfigurableProductsStockStatus(array $products): void
    {
        $skus = array_map(
            fn (string $fixture) => $this->fixtures->get($fixture)->getSku(),
            array_combine($products, $products)
        );
        $cp1Sku = $this->fixtures->get('cp1')->getSku();
        $cp2Sku = $this->fixtures->get('cp2')->getSku();
        $this->assertTrue($this->isProductSalable->execute($cp1Sku, Stock::DEFAULT_STOCK_ID));
        $this->assertTrue($this->isProductSalable->execute($cp2Sku, Stock::DEFAULT_STOCK_ID));

        $inventory = [
            'is_in_stock' => 0
        ];
        $data = $this->inventoryDataFactory->create(
            [
                'skus' => array_values($skus),
                'data' => $this->serializer->serialize($inventory),
            ]
        );
        $this->updateInventory->execute($data);
        $this->assertFalse($this->isProductSalable->execute($cp1Sku, Stock::DEFAULT_STOCK_ID));
        $this->assertFalse($this->isProductSalable->execute($cp2Sku, Stock::DEFAULT_STOCK_ID));

        $inventory = [
            'is_in_stock' => 1
        ];
        $data = $this->inventoryDataFactory->create(
            [
                'skus' => array_values($skus),
                'data' => $this->serializer->serialize($inventory),
            ]
        );
        $this->updateInventory->execute($data);
        $this->assertTrue($this->isProductSalable->execute($cp1Sku, Stock::DEFAULT_STOCK_ID));
        $this->assertTrue($this->isProductSalable->execute($cp2Sku, Stock::DEFAULT_STOCK_ID));
    }

    /**
     * @return array[]
     */
    public static function massUpdateConfigurableProductsStockStatusDataProvider(): array
    {
        return [
            [['sp1', 'sp2', 'sp3', 'sp4', 'cp1', 'cp2']],
            [['sp1', 'sp2', 'sp3', 'sp4']],
        ];
    }
}
