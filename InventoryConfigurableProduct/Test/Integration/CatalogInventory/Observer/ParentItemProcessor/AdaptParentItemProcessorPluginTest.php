<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Test\Integration\CatalogInventory\Observer\ParentItemProcessor;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\ConfigurableProduct\Test\Fixture\Attribute as AttributeFixture;
use Magento\ConfigurableProduct\Test\Fixture\Product as ConfigurableProductFixture;
use Magento\InventoryApi\Test\Fixture\SourceItem as SourceItemFixture;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class AdaptParentItemProcessorPluginTest extends TestCase
{
    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    /**
     * @var StockItemRepositoryInterface
     */
    private $stockItemRepository;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @inheirtDoc
     */
    protected function setUp(): void
    {
        $this->fixtures = Bootstrap::getObjectManager()->get(DataFixtureStorageManager::class)->getStorage();
        $this->productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
        $this->stockItemRepository = Bootstrap::getObjectManager()->get(StockItemRepositoryInterface::class);
        $this->areProductsSalable = Bootstrap::getObjectManager()->get(AreProductsSalableInterface::class);
    }

    /**
     * @magentoDataFixture Magento_InventoryApi::Test/_files/sources.php
     */
    #[
        DbIsolation(true),
        DataFixture(ProductFixture::class, as: 'p1'),
        DataFixture(AttributeFixture::class, as: 'attr'),
        DataFixture(
            ConfigurableProductFixture::class,
            ['_options' => ['$attr$'],'_links' => ['$p1$']],
            'conf1'
        ),
        DataFixture(SourceItemFixture::class, ['sku' => '$p1.sku$', 'source_code' => 'default'])
    ]
    public function testChildStockUpdateForMultiSourceChangesParantSalability(): void
    {
        $stockId = 1;
        $child = $this->productRepository->get($this->fixtures->get('p1')->getSku());
        $childStockItem = $this->stockItemRepository->get(
            $child->getExtensionAttributes()->getStockItem()->getItemId()
        );
        $childStockItem->setIsInStock(false);
        $childStockItem->save();

        $result = $this->areProductsSalable->execute([$this->fixtures->get('conf1')->getSku()], $stockId);
        $result = current($result);
        $this->assertFalse($result->isSalable());

        $child = $this->productRepository->get($this->fixtures->get('p1')->getSku());
        $child->getExtensionAttributes()->getStockItem()->setIsInStock(true);
        $child->save();

        $result = $this->areProductsSalable->execute([$this->fixtures->get('conf1')->getSku()], $stockId);
        $result = current($result);
        $this->assertTrue($result->isSalable());
    }
}
