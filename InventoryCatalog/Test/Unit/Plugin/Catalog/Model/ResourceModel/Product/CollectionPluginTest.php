<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Unit\Plugin\Catalog\Model\ResourceModel\Product;

use Magento\Catalog\Helper\Data;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\InventoryCatalog\Plugin\Catalog\Model\ResourceModel\Product\CollectionPlugin;
use Magento\InventoryCatalogApi\Model\SortableBySaleabilityInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test class for set order on category product collection plugin
 */
class CollectionPluginTest extends TestCase
{
    /**
     * @var CollectionPlugin
     */
    private $plugin;

    /**
     * @var StockConfigurationInterface|MockObject
     */
    private $stockConfigurationMock;

    /**
     * @var Data|MockObject
     */
    private $categoryHelperMock;

    /**
     * @var Collection|MockObject
     */
    private $productCollectionMock;

    /**
     * @var Category|MockObject
     */
    private $categoryMock;

    /**
     * @var SortableBySaleabilityInterface|MockObject
     */
    private $sortableBySaleabilityProviderMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->stockConfigurationMock = $this->getMockBuilder(StockConfigurationInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryHelperMock = $this->createMock(Data::class);
        $this->productCollectionMock = $this->createMock(Collection::class);
        $this->categoryMock = $this->createMock(Category::class);
        $this->sortableBySaleabilityProviderMock =
            $this->getMockBuilder(SortableBySaleabilityInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->plugin = (new ObjectManager($this))->getObject(
            CollectionPlugin::class,
            [
                'stockConfiguration' => $this->stockConfigurationMock,
                'categoryHelper' => $this->categoryHelperMock
            ]
        );
    }

    /**
     * Test for `beforeSetOrder` when out of stock setting is enabled
     *
     * @param string $attribute
     * @param string $dir
     * @param int $automaticSorting
     * @return void
     * @dataProvider dataProviderForAutomaticSorting
     */
    public function testBeforeSetOrderWhenOutOfStockIsEnabled(
        string $attribute,
        string $dir,
        int $automaticSorting
    ): void {
        $this->stockConfigurationMock
            ->expects($this->once())
            ->method('isShowOutOfStock')
            ->willReturn(true);

        $this->productCollectionMock
            ->expects($this->once())
            ->method('getFlag')
            ->with('is_sorted_by_oos')
            ->willReturn(false);

        $this->productCollectionMock
            ->expects($this->exactly(3))
            ->method('setFlag')
            ->willReturnSelf();

        $this->categoryHelperMock
            ->expects($this->once())
            ->method('getCategory')
            ->willReturn($this->categoryMock);

        $this->categoryMock
            ->expects($this->any())
            ->method('getData')
            ->with('automatic_sorting')
            ->willReturn($automaticSorting);

        $this->sortableBySaleabilityProviderMock
            ->expects($this->any())
            ->method('isSortableBySaleability')
            ->willReturn(true);

        $this->productCollectionMock
            ->expects($this->any())
            ->method('setOrder')
            ->with('is_out_of_stock', 'DESC')
            ->willReturnSelf();

        $this->assertEquals(
            [$attribute, $dir],
            $this->plugin->beforeSetOrder($this->productCollectionMock, $attribute, $dir)
        );
    }

    /**
     * dataProvider for beforeSetOrder function
     *
     * @return array[]
     */
    public function dataProviderForAutomaticSorting(): array
    {
        $attribute = 'is_out_of_stock';
        $dir = 'DESC';

        return [
            'verify additional sort order when automatic_sorting set to outOfStockBottom' => [$attribute, $dir, 2],
            'verify no additional sort order when automatic_sorting is set to else' => [$attribute, $dir, 1],
        ];
    }

    /**
     * Test for `beforeSetOrder` when out of stock setting is disabled
     *
     * @return void
     */
    public function testBeforeSetOrderWhenOutOfStockIsDisabled(): void
    {
        $attribute = 'test';
        $dir = 'DESC';

        $this->stockConfigurationMock
            ->expects($this->once())
            ->method('isShowOutOfStock')
            ->willReturn(false);

        $this->assertEquals(
            [$attribute, $dir],
            $this->plugin->beforeSetOrder($this->productCollectionMock, $attribute, $dir)
        );
    }
}
