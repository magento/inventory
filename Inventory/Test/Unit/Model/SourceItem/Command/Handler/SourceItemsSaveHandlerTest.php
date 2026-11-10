<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Inventory\Test\Unit\Model\SourceItem\Command\Handler;

use Magento\Framework\Validation\ValidationResult;
use Magento\Inventory\Model\IsProductAssignedToStock\CacheStorage;
use Magento\Inventory\Model\ResourceModel\GetCanonicalSourceCodes;
use Magento\Inventory\Model\ResourceModel\SourceItem\SaveMultiple;
use Magento\Inventory\Model\SourceItem\Command\Handler\SourceItemsSaveHandler;
use Magento\Inventory\Model\SourceItem\Validator\SourceItemsValidator;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SourceItemsSaveHandlerTest extends TestCase
{
    /**
     * @var SourceItemsValidator|Stub
     */
    private $sourceItemsValidator;

    /**
     * @var SaveMultiple|Stub
     */
    private $saveMultiple;

    /**
     * @var CacheStorage|Stub
     */
    private $cacheStorage;

    /**
     * @var GetCanonicalSourceCodes|MockObject
     */
    private $getCanonicalSourceCodes;

    /**
     * @var SourceItemsSaveHandler
     */
    private $handler;

    protected function setUp(): void
    {
        $this->sourceItemsValidator = $this->createStub(SourceItemsValidator::class);
        $this->saveMultiple = $this->createStub(SaveMultiple::class);
        $this->cacheStorage = $this->createStub(CacheStorage::class);
        $this->getCanonicalSourceCodes = $this->createMock(GetCanonicalSourceCodes::class);

        $validationResult = $this->createStub(ValidationResult::class);
        $validationResult->method('isValid')->willReturn(true);
        $this->sourceItemsValidator->method('validate')->willReturn($validationResult);

        $this->handler = new SourceItemsSaveHandler(
            $this->sourceItemsValidator,
            $this->saveMultiple,
            $this->createStub(LoggerInterface::class),
            $this->cacheStorage,
            $this->getCanonicalSourceCodes
        );
    }

    public function testExecuteReplacesMismatchedCaseSourceCodeWithCanonical(): void
    {
        $sourceItem = $this->createMock(SourceItemInterface::class);
        $sourceItem->method('getSourceCode')->willReturn('Warehouse_Bravo');
        $sourceItem->method('getSku')->willReturn('sku-1');
        $sourceItem->expects(self::once())
            ->method('setSourceCode')
            ->with('warehouse_bravo');

        $this->getCanonicalSourceCodes->method('execute')
            ->with(['Warehouse_Bravo'])
            ->willReturn(['warehouse_bravo' => 'warehouse_bravo']);

        $this->handler->execute([$sourceItem]);
    }

    public function testExecuteLeavesUnmatchedSourceCodeUntouched(): void
    {
        $sourceItem = $this->createMock(SourceItemInterface::class);
        $sourceItem->method('getSourceCode')->willReturn('unknown_source');
        $sourceItem->method('getSku')->willReturn('sku-1');
        $sourceItem->expects(self::never())->method('setSourceCode');

        $this->getCanonicalSourceCodes->method('execute')
            ->with(['unknown_source'])
            ->willReturn([]);

        $this->handler->execute([$sourceItem]);
    }
}
