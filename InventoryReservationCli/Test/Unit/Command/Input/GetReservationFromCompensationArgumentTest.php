<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryReservationCli\Test\Unit\Command\Input;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\InventoryReservationCli\Command\Input\GetReservationFromCompensationArgument;
use Magento\InventoryReservationsApi\Model\ReservationBuilderInterface;
use Magento\InventoryReservationsApi\Model\ReservationInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Exception\InvalidArgumentException;

/**
 * Test parsing of the compensation argument printed by inventory:reservation:list-inconsistencies -r
 */
class GetReservationFromCompensationArgumentTest extends TestCase
{
    /**
     * @var OrderRepositoryInterface|MockObject
     */
    private $orderRepository;

    /**
     * @var ReservationBuilderInterface|MockObject
     */
    private $reservationBuilder;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;

    /**
     * @var GetReservationFromCompensationArgument
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->reservationBuilder = $this->createMock(ReservationBuilderInterface::class);
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $serializer = $this->createStub(SerializerInterface::class);
        $serializer->method('serialize')->willReturn('{}');

        $this->model = new GetReservationFromCompensationArgument(
            $this->orderRepository,
            $this->reservationBuilder,
            $serializer,
            $this->searchCriteriaBuilder
        );
    }

    /**
     * @param string $argument
     * @param string $incrementId
     * @param string $sku
     * @param float $quantity
     * @param int $stockId
     * @return void
     */
    #[DataProvider('argumentDataProvider')]
    public function testExecute(string $argument, string $incrementId, string $sku, float $quantity, int $stockId): void
    {
        $this->searchCriteriaBuilder->expects($this->once())
            ->method('addFilter')
            ->with('increment_id', $incrementId, 'eq')
            ->willReturnSelf();
        $this->searchCriteriaBuilder->method('create')->willReturn($this->createStub(SearchCriteria::class));

        $order = $this->createStub(OrderInterface::class);
        $order->method('getEntityId')->willReturn(1);
        $order->method('getIncrementId')->willReturn($incrementId);
        $searchResult = $this->createStub(OrderSearchResultInterface::class);
        $searchResult->method('getItems')->willReturn([$order]);
        $this->orderRepository->expects($this->once())->method('getList')->willReturn($searchResult);

        $reservation = $this->createStub(ReservationInterface::class);
        $this->reservationBuilder->expects($this->once())->method('setSku')->with($sku)->willReturnSelf();
        $this->reservationBuilder->expects($this->once())->method('setQuantity')->with($quantity)->willReturnSelf();
        $this->reservationBuilder->expects($this->once())->method('setStockId')->with($stockId)->willReturnSelf();
        $this->reservationBuilder->method('setMetadata')->willReturnSelf();
        $this->reservationBuilder->method('build')->willReturn($reservation);

        $this->assertSame($reservation, $this->model->execute($argument));
    }

    /**
     * @return array
     */
    public static function argumentDataProvider(): array
    {
        return [
            'simple sku' => ['000000123:SKU-1:-1.000000:1', '000000123', 'SKU-1', -1.0, 1],
            'sku with colons' => ['000000123:123456; SN: 12345:-1.000000:1', '000000123', '123456; SN: 12345', -1.0, 1],
            'sku ending with a number after a colon' => ['000000124:ABC:5:2.500000:10', '000000124', 'ABC:5', 2.5, 10],
        ];
    }

    /**
     * @return void
     */
    public function testExecuteWithInvalidArgument(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->searchCriteriaBuilder->expects($this->never())->method('addFilter');
        $this->orderRepository->expects($this->never())->method('getList');
        $this->reservationBuilder->expects($this->never())->method('build');

        $this->model->execute('000000123:SKU-1:-1.000000');
    }
}
