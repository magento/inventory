<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryLogging\Test\Unit\Plugin\Inventory\Model\SourceItem\Command\Handler;

use Magento\Framework\Event\ManagerInterface;
use Magento\Inventory\Model\SourceItem;
use Magento\Inventory\Model\SourceItem\Command\Handler\SourceItemsSaveHandler;
use Magento\InventoryLogging\Plugin\Inventory\Model\SourceItem\Command\Handler\SourceItemsSaveHandlerPlugin;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SourceItemsSaveHandlerPluginTest extends TestCase
{
    /** @var ManagerInterface|MockObject */
    private ManagerInterface $eventManager;

    /**
     * @var SourceItemsSaveHandlerPlugin
     */
    private SourceItemsSaveHandlerPlugin $plugin;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->eventManager = $this->createMock(ManagerInterface::class);
        $this->plugin = new SourceItemsSaveHandlerPlugin($this->eventManager);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testAfterExecuteReturnsEarlyOnEmptyArray(): void
    {
        $this->eventManager
            ->expects($this->never())
            ->method('dispatch');

        $subject = $this->createMock(SourceItemsSaveHandler::class);
        $result = 'original_result';

        $actual = $this->plugin->afterExecute($subject, $result, []);

        $this->assertSame($result, $actual);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testAfterExecuteDispatchesForEachSourceItemAndReturnsResult(): void
    {
        $item1 = $this->createMock(SourceItem::class);
        $item2 = $this->createMock(SourceItem::class);

        $actualCalls = [];
        $this->eventManager
            ->expects($this->exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function (string $eventName, array $data) use (&$actualCalls) {
                $actualCalls[] = [$eventName, $data];
                return null;
            });

        $subject = $this->createMock(SourceItemsSaveHandler::class);
        $result = new \stdClass();

        $actualResult = $this->plugin->afterExecute($subject, $result, [$item1, $item2]);
        $this->assertSame($result, $actualResult);
        $expectedCalls = [
            ['model_save_after', ['object' => $item1]],
            ['model_save_after', ['object' => $item2]],
        ];
        $this->assertSame($expectedCalls, $actualCalls);
    }

    /**
     * @param mixed $invalidItem
     * @return void
     * @throws Exception
     * @dataProvider invalidItemProvider
     */
    public function testAfterExecuteThrowsForNonAbstractModelItems(mixed $invalidItem): void
    {
        $this->eventManager
            ->expects(self::never())
            ->method('dispatch');

        $subject = $this->createMock(SourceItemsSaveHandler::class);
        $this->plugin->afterExecute($subject, 'irrelevant', [$invalidItem]);
    }

    /**
     * @return array
     */
    public static function invalidItemProvider(): array
    {
        return [
            'null'         => [null],
            'int'          => [123],
            'string'       => ['sku-1'],
            'array'        => [[]],
            'stdClass'     => [new \stdClass()],
            'bool'         => [true],
            'float'        => [1.23],
        ];
    }
}
