<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Test\Unit\Plugin\Model\Product\Type\Configurable;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryConfigurableProduct\Plugin\Model\Product\Type\Configurable\IsSalablePlugin;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class IsSalablePluginTest extends TestCase
{
    private const CURRENT_STORE_ID = 1;

    /**
     * @var IsSalablePlugin
     */
    private IsSalablePlugin $plugin;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var Configurable|MockObject
     */
    private $configurableMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->configurableMock = $this->createMock(Configurable::class);

        $storeMock = $this->createMock(StoreInterface::class);
        $storeMock->method('getId')->willReturn(self::CURRENT_STORE_ID);
        $this->storeManagerMock->method('getStore')->willReturn($storeMock);

        $this->plugin = new IsSalablePlugin($this->storeManagerMock);
    }

    public function testLoadedIsSalableIsUsedWithoutTouchingTheCore(): void
    {
        $product = $this->createProduct(['status' => Status::STATUS_ENABLED, 'is_salable' => '1']);

        $this->assertTrue($this->plugin->aroundIsSalable($this->configurableMock, $this->failingProceed(), $product));
    }

    public function testLoadedIsSalableZeroMakesProductNotSalable(): void
    {
        $product = $this->createProduct(['status' => Status::STATUS_ENABLED, 'is_salable' => '0']);

        $this->assertFalse($this->plugin->aroundIsSalable($this->configurableMock, $this->failingProceed(), $product));
    }

    public function testLoadedIsSalableNullMakesProductNotSalable(): void
    {
        $product = $this->createProduct(['status' => Status::STATUS_ENABLED, 'is_salable' => null]);

        $this->assertFalse($this->plugin->aroundIsSalable($this->configurableMock, $this->failingProceed(), $product));
    }

    public function testDisabledProductIsNotSalable(): void
    {
        $product = $this->createProduct(['status' => Status::STATUS_DISABLED, 'is_salable' => '1']);

        $this->assertFalse($this->plugin->aroundIsSalable($this->configurableMock, $this->failingProceed(), $product));
    }

    public function testProductWithoutLoadedIsSalableIsDelegated(): void
    {
        $product = $this->createProduct(['status' => Status::STATUS_ENABLED]);

        $this->assertTrue(
            $this->plugin->aroundIsSalable($this->configurableMock, static fn () => true, $product)
        );
    }

    public function testStoreFilterOfAnotherStoreIsDelegated(): void
    {
        $product = $this->createProduct(['status' => Status::STATUS_ENABLED, 'is_salable' => '1']);
        $otherStore = $this->createMock(Store::class);
        $otherStore->method('getId')->willReturn(7);
        $this->configurableMock->method('getStoreFilter')->willReturn($otherStore);

        $this->assertFalse(
            $this->plugin->aroundIsSalable($this->configurableMock, static fn () => false, $product)
        );
    }

    public function testStoreFilterOfCurrentStoreKeepsTheFastPath(): void
    {
        $product = $this->createProduct(['status' => Status::STATUS_ENABLED, 'is_salable' => '1']);
        $currentStore = $this->createMock(Store::class);
        $currentStore->method('getId')->willReturn(self::CURRENT_STORE_ID);
        $this->configurableMock->method('getStoreFilter')->willReturn($currentStore);

        $this->assertTrue($this->plugin->aroundIsSalable($this->configurableMock, $this->failingProceed(), $product));
    }

    public function testIntegerStoreFilterOfCurrentStoreKeepsTheFastPath(): void
    {
        $product = $this->createProduct(['status' => Status::STATUS_ENABLED, 'is_salable' => '1']);
        $this->configurableMock->method('getStoreFilter')->willReturn(self::CURRENT_STORE_ID);

        $this->assertTrue($this->plugin->aroundIsSalable($this->configurableMock, $this->failingProceed(), $product));
    }

    public function testMissingScopeIsDelegated(): void
    {
        $product = $this->createProduct(['status' => Status::STATUS_ENABLED, 'is_salable' => '1'], null);
        $this->configurableMock->method('getStoreFilter')->willReturn(null);

        $this->assertFalse(
            $this->plugin->aroundIsSalable($this->configurableMock, static fn () => false, $product)
        );
    }

    public function testStoreResolutionFailureFallsBackToTheCore(): void
    {
        $product = $this->createProduct(['status' => Status::STATUS_ENABLED, 'is_salable' => '1']);
        $this->configurableMock->method('getStoreFilter')
            ->willThrowException(new NoSuchEntityException(__('no store')));

        $this->assertTrue(
            $this->plugin->aroundIsSalable($this->configurableMock, static fn () => true, $product)
        );
    }

    /**
     * Build a product stub carrying the given data.
     *
     * @param array $data
     * @param int|null $storeId
     * @return Product|MockObject
     */
    private function createProduct(array $data, ?int $storeId = self::CURRENT_STORE_ID)
    {
        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStoreId', 'getSku', 'getStatus', 'hasData', 'getData'])
            ->getMock();
        $product->method('getStoreId')->willReturn($storeId);
        $product->method('getSku')->willReturn('sku-1');
        $product->method('getStatus')->willReturn($data['status']);
        $product->method('hasData')->with('is_salable')->willReturn(array_key_exists('is_salable', $data));
        $product->method('getData')->with('is_salable')->willReturn($data['is_salable'] ?? null);

        return $product;
    }

    /**
     * A $proceed that must never be reached.
     *
     * @return callable
     */
    private function failingProceed(): callable
    {
        return function () {
            $this->fail('The core implementation must not be reached');
        };
    }
}
