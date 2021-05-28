<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryReservationCli\Test\Unit\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\InventoryReservationCli\Command\CreateCompensations;
use Magento\InventoryReservationCli\Command\Input\GetCommandlineStandardInput;
use Magento\InventoryReservationCli\Command\Input\GetReservationFromCompensationArgument;
use Magento\InventoryReservationsApi\Model\AppendReservationsInterface;
use Magento\InventoryReservationsApi\Model\ReservationInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class to test Create compensations functionality
 */
class CreateCompensationsTests extends TestCase
{
    /**
     * @var CreateCompensations
     */
    private $createCompensationsCommand;

    /**
     * @var GetCommandlineStandardInput|MockObject
     */
    private $getCommandlineStandardInput;

    /**
     * @var GetReservationFromCompensationArgument|MockObject
     */
    private $getReservationFromCompensationArgument;

    /**
     * @var AppendReservationsInterface|MockObject
     */
    private $appendReservations;

    /**
     * @var State|MockObject
     */
    private $appState;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->getCommandlineStandardInput = $this->createMock(GetCommandlineStandardInput::class);
        $this->getReservationFromCompensationArgument = $this->createMock(
            GetReservationFromCompensationArgument::class
        );
        $this->appendReservations = $this->createMock(AppendReservationsInterface::class);
        $this->appState = $this->createMock(State::class);

        $this->createCompensationsCommand = new CreateCompensations(
            $this->getCommandlineStandardInput,
            $this->getReservationFromCompensationArgument,
            $this->appendReservations,
            $this->appState
        );
    }

    /**
     * Test to execute the command
     */
    public function testExecute(): void
    {
        $sku = 'test-sku';
        $qty = 10.0;
        $stockId = 11;
        $argument = 'compensation-argument-value';

        $this->appState->expects($this->once())->method('setAreaCode')->with(Area::AREA_GLOBAL);

        $outputMock = $this->createMock(OutputInterface::class);
        $outputMock->expects($this->exactly(2))
            ->method('writeln');

        $inputMock = $this->createMock(InputInterface::class);
        $inputMock->method('getArgument')->willReturn([$argument]);

        $compensationMock = $this->createMock(ReservationInterface::class);
        $compensationMock->method('getSku')->willReturn($sku);
        $compensationMock->method('getQuantity')->willReturn($qty);
        $compensationMock->method('getStockId')->willReturn($stockId);
        $this->getReservationFromCompensationArgument->method('execute')
            ->with($argument)
            ->willReturn($compensationMock);

        $this->appendReservations->expects($this->once())->method('execute')->with([$compensationMock]);

        $this->assertEquals(0, $this->createCompensationsCommand->execute($inputMock, $outputMock));
    }
}
