<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Test\Unit\Observer;

use Magento\Catalog\Model\Indexer\Product\Category\Processor;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\InventoryCatalog\Observer\ReindexCatalogProduct;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReindexCatalogProductTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var Processor|MockObject
     */
    private $indexProcessorMock;

    /**
     * @var ReindexCatalogProduct
     */
    private $observer;

    /**
     * Setup the test for Observer methods
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->indexProcessorMock = $this->getMockBuilder(
            Processor::class
        )->disableOriginalConstructor()->getMock();

        $this->observer = $this->objectManager->getObject(
            ReindexCatalogProduct::class,
            [
                'indexerProcessor' => $this->indexProcessorMock,
            ]
        );
    }

    /**
     * test Execute
     * @return void
     */
    public function testExecute() : void
    {
        $ids = [1, 2, 3, 4];

        /** @var Observer $observerData */
        $observerData = $this->objectManager->getObject(
            Observer::class,
            [
                'data' => ['entity_ids' => $ids],
            ]
        );

        $this->indexProcessorMock->expects($this->once())->method('reindexList')->with($ids);

        $this->observer->execute($observerData);
    }
}
