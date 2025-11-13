<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryLogging\Test\Unit\Plugin\Inventory\Model\SourceItem\Command;

use Magento\Inventory\Model\SourceItem;
use Magento\Framework\Event\ManagerInterface;
use Magento\Inventory\Model\SourceItem\Command\SourceItemsDelete;
use Magento\InventoryLogging\Plugin\Inventory\Model\SourceItem\Command\SourceItemsDeletePlugin;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SourceItemsDeletePluginTest extends TestCase
{
    /** @var ManagerInterface|MockObject */
    private ManagerInterface $eventManager;

    /**
     * @var SourceItemsDeletePlugin
     */
    private SourceItemsDeletePlugin $plugin;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->eventManager = $this->createMock(ManagerInterface::class);
        $this->plugin = new SourceItemsDeletePlugin($this->eventManager);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testAfterExecuteDoesNothingWhenNoSourceItems(): void
    {
        $this->eventManager
            ->expects($this->never())
            ->method('dispatch');

        $subject = $this->createMock(SourceItemsDelete::class);
        $this->plugin->afterExecute($subject, 'irrelevant_result', []);

        $this->addToAssertionCount(1);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function testAfterExecuteDispatchesDeleteEventForEachValidSourceItem(): void
    {
        $item1 = $this->createMock(SourceItem::class);
        $item2 = $this->createMock(SourceItem::class);

        $actualCalls = [];
        $this->eventManager
            ->expects(self::exactly(2))
            ->method('dispatch')
            ->willReturnCallback(function (string $eventName, array $data) use (&$actualCalls) {
                $actualCalls[] = [$eventName, $data];
                return null;
            });

        $subject = $this->createMock(SourceItemsDelete::class);
        $this->plugin->afterExecute($subject, 'ignored_result', [$item1, $item2]);

        $expectedCalls = [
            ['model_delete_after', ['object' => $item1]],
            ['model_delete_after', ['object' => $item2]],
        ];

        $this->assertSame(
            $expectedCalls,
            $actualCalls,
            'Expected one model_delete_after dispatch per source item with correct payload.'
        );
    }

    /**
     * @param mixed $invalidItem
     * @return void
     * @throws Exception
     * @dataProvider invalidItemProvider
     */
    public function testAfterExecuteThrowsIfSourceItemIsNotAbstractModel(mixed $invalidItem): void
    {
        $this->eventManager
            ->expects(self::never())
            ->method('dispatch');

        $subject = $this->createMock(SourceItemsDelete::class);
        $this->plugin->afterExecute($subject, 'whatever', [$invalidItem]);
    }

    /**
     * @return array
     */
    public static function invalidItemProvider(): array
    {
        return [
            'null'     => [null],
            'int'      => [123],
            'string'   => ['sku-1'],
            'array'    => [[]],
            'stdClass' => [new \stdClass()],
            'bool'     => [true],
            'float'    => [1.23],
        ];
    }
}
