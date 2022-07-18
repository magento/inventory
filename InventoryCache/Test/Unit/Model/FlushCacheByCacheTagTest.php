<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCache\Test\Unit\Model;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\EntityManager\EventManager;
use Magento\Framework\Indexer\CacheContext;
use Magento\Framework\Indexer\CacheContextFactory;
use Magento\InventoryCache\Model\FlushCacheByCacheTag;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FlushCacheByCacheTagTest extends TestCase
{
    /**
     * @var CacheContextFactory|MockObject
     */
    private $cacheContextFactory;

    /**
     * @var EventManager|MockObject
     */
    private $eventManager;

    /**
     * @var CacheInterface|MockObject
     */
    private $appCache;

    /**
     * @var FlushCacheByCacheTag
     */
    private $model;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheContextFactory = $this->createMock(CacheContextFactory::class);
        $this->eventManager = $this->createMock(EventManager::class);
        $this->appCache = $this->createMock(CacheInterface::class);
        $this->model = new FlushCacheByCacheTag(
            $this->cacheContextFactory,
            $this->eventManager,
            $this->appCache
        );
    }

    /**
     * Checks that cache is not cleaned with empty tags which cleans all caches
     *
     * @return void
     */
    public function testExecuteWithEmptyEntityIds(): void
    {
        $this->cacheContextFactory->expects($this->never())->method('create');
        $this->eventManager->expects($this->never())->method('dispatch');
        $this->appCache->expects($this->never())->method('clean');
        $this->model->execute('test', []);
    }

    /**
     * Checks that cache is not cleaned with empty tags which cleans all caches
     *
     * @return void
     */
    public function testExecuteWithDeferredCacheContext(): void
    {
        $cacheContextMock = $this->createMock(CacheContext::class);
        $cacheContextMock->method('getIdentities')
            ->willReturn([]);
        $this->cacheContextFactory->expects($this->once())
            ->method('create')
            ->willReturn($cacheContextMock);
        $this->appCache->expects($this->never())->method('clean');
        $this->model->execute('test', ['1', '2']);
    }
}
