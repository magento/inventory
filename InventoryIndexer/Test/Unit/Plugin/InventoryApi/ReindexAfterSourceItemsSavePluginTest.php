<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Test\Unit\Plugin\InventoryApi;

use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryIndexer\Indexer\SourceItem\GetSourceItemIds;
use Magento\InventoryIndexer\Indexer\SourceItem\SourceItemIndexer;
use Magento\InventoryIndexer\Plugin\InventoryApi\ReindexAfterSourceItemsSavePlugin;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReindexAfterSourceItemsSavePluginTest extends TestCase
{
    /**
     * @var GetSourceItemIds|MockObject
     */
    private $getSourceItemIds;

    /**
     * @var SourceItemIndexer|MockObject
     */
    private $sourceItemIndexer;

    /**
     * @var DefaultSourceProviderInterface|MockObject
     */
    private $defaultSourceProvider;

    /**
     * @var SourceItemInterface|MockObject
     */
    private $sourceItem;

    /**
     * @var SourceItemsSaveInterface|MockObject
     */
    private $subject;

    /**
     * @var ReindexAfterSourceItemsSavePlugin
     */
    private $plugin;

    /**
     * @inheridoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->getSourceItemIds = $this->createMock(GetSourceItemIds::class);
        $this->sourceItemIndexer = $this->createMock(SourceItemIndexer::class);
        $this->defaultSourceProvider = $this->createMock(DefaultSourceProviderInterface::class);
        $this->sourceItem = $this->createMock(SourceItemInterface::class);
        $this->subject = $this->createMock(SourceItemsSaveInterface::class);
        $this->plugin = new ReindexAfterSourceItemsSavePlugin(
            $this->getSourceItemIds,
            $this->sourceItemIndexer,
            $this->defaultSourceProvider
        );
    }

    public function testAfterExecuteWithDefaultSource() : void
    {
        $defaultCode = 'default';
        $this->defaultSourceProvider->expects($this->once())
            ->method('getCode')
            ->willReturn($defaultCode);
        $this->sourceItem->expects($this->once())
            ->method('getSourceCode')
            ->willReturn($defaultCode);
        $this->getSourceItemIds->expects($this->once())
            ->method('execute')
            ->with([])
            ->willReturn([]);
        $this->sourceItemIndexer->expects($this->never())
            ->method('executeList');
        $this->plugin->afterExecute($this->subject, null, [$this->sourceItem]);
    }
}
