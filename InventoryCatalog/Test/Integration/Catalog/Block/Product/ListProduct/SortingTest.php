<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Integration\Catalog\Block\Product\ListProduct;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\ListProduct;
use Magento\Catalog\Block\Product\ProductList\Toolbar;
use Magento\Catalog\Test\Fixture\Category as CategoryFixture;
use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\CatalogInventory\Model\Configuration;
use Magento\ConfigurableProduct\Test\Fixture\Attribute as AttributeFixture;
use Magento\ConfigurableProduct\Test\Fixture\Product as ConfigurableProductFixture;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorage;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Tests for products sorting on category page.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @magentoDbIsolation disabled
 * @magentoAppIsolation enabled
 */
class SortingTest extends TestCase
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var ListProduct
     */
    private $block;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var LayoutInterface
     */
    private $layout;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var DataFixtureStorage
     */
    private $fixtures;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->layout = $this->objectManager->get(LayoutInterface::class);
        $this->layout->createBlock(Toolbar::class, 'product_list_toolbar');
        $this->block = $this->layout->createBlock(ListProduct::class)->setToolbarBlockName('product_list_toolbar');
        $this->categoryRepository = $this->objectManager->get(CategoryRepositoryInterface::class);
        $this->productRepository = $this->objectManager->create(ProductRepositoryInterface::class);
        $this->fixtures = $this->objectManager->get(DataFixtureStorageManager::class)->getStorage();
        parent::setUp();
    }

    /**
     * Renders block to apply sorting.
     *
     * @param CategoryInterface $category
     * @param string $direction
     * @return void
     * @throws LocalizedException
     */
    private function renderBlock(CategoryInterface $category, string $direction): void
    {
        $this->block->getLayer()->setCurrentCategory($category);
        $this->block->setDefaultDirection($direction);
        $this->block->toHtml();
    }

    /**
     * Checks product list block correct sorting.
     *
     * @param string $sortBy
     * @param array $expectation
     * @return void
     */
    private function assertBlockSorting(string $sortBy, array $expectation): void
    {
        $this->assertArrayHasKey($sortBy, $this->block->getAvailableOrders());
        $this->assertEquals($sortBy, $this->block->getSortBy());
        $this->assertEquals($expectation, $this->block->getLoadedProductCollection()->getColumnValues('sku'));
    }

    /**
     * Test product list ordered by product name with out-of-stock configurable product options.
     *
     * @dataProvider productListWithShowOutOfStockSortOrderDataProvider
     * @param string $sortBy
     * @param string $direction
     * @param array $expected
     * @return void
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws StateException|LocalizedException
     */
    #[
        Config(Configuration::XML_PATH_SHOW_OUT_OF_STOCK, 1, ScopeInterface::SCOPE_STORE, 'default'),
        DataFixture(CategoryFixture::class, ['name' => 'Category1', 'parent_id' => '2'], 'c11'),
        DataFixture(
            ProductFixture::class,
            ['sku' => 'simple_1', 'price' => 35, 'category_ids' => ['$c11.id$']],
            'simple product1'
        ),
        DataFixture(
            ProductFixture::class,
            ['sku' => 'simple_2', 'price' => 40, 'category_ids' => ['$c11.id$']],
            'simple product2'
        ),
        DataFixture(
            ProductFixture::class,
            ['sku' => 'simple_10', 'price' => 45, 'category_ids' => ['$c11.id$'], 'visibility' => 1],
            'p1'
        ),
        DataFixture(
            ProductFixture::class,
            ['sku' => 'simple_20', 'price' => 45, 'category_ids' => ['$c11.id$'], 'visibility' => 1],
            'p2'
        ),
        DataFixture(
            ProductFixture::class,
            ['sku' => 'simple_30', 'price' => 50, 'category_ids' => ['$c11.id$'], 'visibility' => 1],
            'p3'
        ),
        DataFixture(
            ProductFixture::class,
            ['sku' => 'simple_40', 'price' => 50, 'category_ids' => ['$c11.id$'], 'visibility' => 1],
            'p4'
        ),
        DataFixture(AttributeFixture::class, as: 'attr'),
        DataFixture(
            ConfigurableProductFixture::class,
            [
                'sku' => 'configurable1',
                'category_ids' => ['$c11.id$'],
                '_options' => ['$attr$'],
                '_links' => ['$p1$', '$p2$']
            ],
            'conf1'
        ),
        DataFixture(
            ConfigurableProductFixture::class,
            [
                'sku' => 'configurable2',
                'category_ids' => ['$c11.id$'],
                '_options' => ['$attr$'],
                '_links' => ['$p3$', '$p4$']
            ],
            'conf2'
        )
    ]
    public function testProductListMoveOutOfStockToBottom(
        string $sortBy,
        string $direction,
        array $expected
    ): void {
        $categoryId = $this->fixtures->get('c11')->getId();

        /** @var CategoryInterface $category */
        $category = $this->categoryRepository->get($categoryId);
        if ($category->getId()) {
            $category->setAvailableSortBy(['position', 'name', 'price']);
            $category->addData(['available_sort_by' => 'position,name,price', 'automatic_sorting' => 2]);
            $category->setDefaultSortBy($sortBy);
            $this->categoryRepository->save($category);
        }

        foreach (['simple_10', 'simple_20', 'configurable1'] as $sku) {
            $product = $this->productRepository->get($sku);
            $product->setStockData(['is_in_stock' => 0]);
            $this->productRepository->save($product);
        }

        $this->renderBlock($category, $direction);
        $this->assertBlockSorting($sortBy, $expected);
    }

    /**
     * Product list with out-of-stock sort order data provider
     *
     * @return array
     */
    public function productListWithShowOutOfStockSortOrderDataProvider(): array
    {
        return [
            'default_order_price_asc' => [
                'sort' => 'price',
                'direction' => 'ASC',
                'expectation' => ['simple_1', 'simple_2', 'configurable2', 'configurable1'],
            ],
            'default_order_price_desc' => [
                'sort' => 'price',
                'direction' => 'DESC',
                'expectation' => ['configurable2', 'simple_2', 'simple_1', 'configurable1'],
            ],
            'default_order_name_asc' => [
                'sort' => 'name',
                'direction' => 'ASC',
                'expectation' => ['configurable2', 'simple_1', 'simple_2', 'configurable1'],
            ],
            'default_order_name_desc' => [
                'sort' => 'name',
                'direction' => 'DESC',
                'expectation' => ['simple_2', 'simple_1', 'configurable2', 'configurable1'],
            ],
        ];
    }
}
