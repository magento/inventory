<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Unit\Model;

use Magento\Framework\DataObject;
use Magento\Framework\DataObject\Factory as ObjectFactory;
use Magento\Framework\Locale\FormatInterface;
use Magento\InventoryApi\Api\Data\StockInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventorySales\Model\GetBackorder;
use Magento\InventorySales\Model\GetBackorderQty;
use Magento\InventorySales\Model\IsProductSalableCondition\BackOrderNotifyCustomerCondition;
use Magento\InventorySalesApi\Api\AreProductsSalableForRequestedQtyInterface;
use Magento\InventorySalesApi\Api\Data\IsProductSalableForRequestedQtyRequestInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\IsProductSalableForRequestedQtyResultInterface;
use Magento\InventorySalesApi\Api\Data\ProductSalabilityErrorInterface;
use Magento\InventorySalesApi\Api\Data\ProductSalableResultInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @see GetBackorder
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GetBackorderTest extends TestCase
{
    /**
     * @var BackOrderNotifyCustomerCondition|MockObject
     */
    private $backOrderNotifyCustomerCondition;

    /**
     * @var GetBackorderQty|MockObject
     */
    private $getBackorderQty;

    /**
     * @var GetBackorder
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $objectFactory = $this->createStub(ObjectFactory::class);
        $objectFactory->method('create')->willReturnCallback(fn () => new DataObject());

        $salableResult = $this->createStub(IsProductSalableForRequestedQtyResultInterface::class);
        $salableResult->method('isSalable')->willReturn(true);
        $areProductsSalable = $this->createStub(AreProductsSalableForRequestedQtyInterface::class);
        $areProductsSalable->method('execute')->willReturn([$salableResult]);

        $getSkusByProductIds = $this->createStub(GetSkusByProductIdsInterface::class);
        $getSkusByProductIds->method('execute')->willReturn([1 => 'simple']);

        $stock = $this->createStub(StockInterface::class);
        $stock->method('getStockId')->willReturn(1);
        $stockResolver = $this->createStub(StockResolverInterface::class);
        $stockResolver->method('execute')->willReturn($stock);

        $website = $this->createStub(WebsiteInterface::class);
        $website->method('getCode')->willReturn('base');
        $storeManager = $this->createStub(StoreManagerInterface::class);
        $storeManager->method('getWebsite')->willReturn($website);

        $this->backOrderNotifyCustomerCondition = $this->createMock(BackOrderNotifyCustomerCondition::class);
        $this->getBackorderQty = $this->createMock(GetBackorderQty::class);

        $this->model = new GetBackorder(
            $objectFactory,
            $this->createStub(FormatInterface::class),
            $areProductsSalable,
            $this->createStub(IsProductSalableForRequestedQtyRequestInterfaceFactory::class),
            $getSkusByProductIds,
            $stockResolver,
            $storeManager,
            $this->backOrderNotifyCustomerCondition,
            $this->getBackorderQty
        );
    }

    /**
     * Stock 10, the same product in two quote items of 20 each: the backorders are 10 and 20, not 10 and 30.
     *
     * @param float $itemQty
     * @param float $qtyToCheck
     * @param float $backorderQtyForQtyToCheck
     * @param bool $notifyCustomer
     * @param float|null $expectedBackorders
     * @param string|null $expectedMessage
     * @return void
     */
    #[DataProvider('executeDataProvider')]
    public function testExecute(
        float $itemQty,
        float $qtyToCheck,
        float $backorderQtyForQtyToCheck,
        bool $notifyCustomer,
        ?float $expectedBackorders,
        ?string $expectedMessage
    ): void {
        $this->getBackorderQty->expects($this->once())
            ->method('execute')
            ->with('simple', 1, $qtyToCheck)
            ->willReturn($backorderQtyForQtyToCheck);
        $notifyResult = $this->createStub(ProductSalableResultInterface::class);
        $notifyResult->method('getErrors')
            ->willReturn($notifyCustomer ? [$this->createStub(ProductSalabilityErrorInterface::class)] : []);
        $this->backOrderNotifyCustomerCondition->expects($this->once())
            ->method('execute')
            ->with('simple', 1, $qtyToCheck)
            ->willReturn($notifyResult);

        $result = $this->model->execute(1, $itemQty, $qtyToCheck, null);

        $this->assertFalse($result->getHasError());
        $this->assertSame($expectedBackorders, $result->getItemBackorders());
        $this->assertSame($expectedMessage, $result->getMessage() === null ? null : (string)$result->getMessage());
    }

    /**
     * @return array
     */
    public static function executeDataProvider(): array
    {
        $message = 'We don\'t have as many quantity as you requested, but we\'ll back order the remaining %1.';

        return [
            'first item, part of it is in stock' => [20.0, 20.0, 10.0, true, 10.0, str_replace('%1', '10', $message)],
            'second item, nothing left in stock' => [20.0, 40.0, 30.0, true, 20.0, str_replace('%1', '20', $message)],
            'second item, customer is not notified' => [20.0, 40.0, 30.0, false, 20.0, null],
            'enough stock' => [5.0, 5.0, 0.0, false, null, null],
        ];
    }
}
