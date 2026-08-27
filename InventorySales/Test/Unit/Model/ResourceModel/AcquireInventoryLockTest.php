<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Unit\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\InventorySales\Model\ResourceModel\AcquireInventoryLock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Test for AcquireInventoryLock
 */
class AcquireInventoryLockTest extends TestCase
{
    /**
     * @var AcquireInventoryLock
     */
    private $model;

    /**
     * @var ResourceConnection|MockObject
     */
    private $resourceConnectionMock;

    /**
     * @var AdapterInterface|MockObject
     */
    private $connectionMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->resourceConnectionMock = $this->createMock(ResourceConnection::class);
        $this->connectionMock = $this->createMock(AdapterInterface::class);

        $this->resourceConnectionMock->method('getConnection')
            ->willReturn($this->connectionMock);

        $this->model = new AcquireInventoryLock(
            $this->resourceConnectionMock
        );
    }

    /**
     * Ensure locks are cleared before object destruction
     *
     * @return void
     */
    protected function tearDown(): void
    {
        // Manually clear locks to prevent destructor from trying to release with mocks
        if ($this->model) {
            try {
                $this->model->releaseAll();
                // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
            } catch (\Throwable $e) {
            }
        }

        parent::tearDown();
    }

    /**
     * Test successful lock acquisition
     *
     * @return void
     */
    public function testExecuteAcquiresLockSuccessfully(): void
    {
        $sku = 'TEST-SKU';
        $stockId = 1;

        $selectMock = $this->createMock(Select::class);
        $selectMock->method('from')->willReturnSelf();

        $this->connectionMock->method('select')
            ->willReturn($selectMock);

        $this->connectionMock->method('fetchOne')
            ->willReturn(1); // Lock acquired successfully

        $result = $this->model->execute($sku, $stockId);

        $this->assertTrue($result);
    }

    /**
     * Test failed lock acquisition
     *
     * @return void
     */
    public function testExecuteFailsToAcquireLock(): void
    {
        $sku = 'TEST-SKU';
        $stockId = 1;

        $selectMock = $this->createMock(Select::class);
        $selectMock->method('from')->willReturnSelf();

        $this->connectionMock->method('select')
            ->willReturn($selectMock);

        $this->connectionMock->method('fetchOne')
            ->willReturn(0); // Lock failed

        $result = $this->model->execute($sku, $stockId);

        $this->assertFalse($result);
    }

    /**
     * Test successful lock release
     *
     * @return void
     */
    public function testReleaseReleasesLockSuccessfully(): void
    {
        $sku = 'TEST-SKU';
        $stockId = 1;

        $selectMock = $this->createMock(Select::class);
        $selectMock->method('from')->willReturnSelf();

        $this->connectionMock->method('select')
            ->willReturn($selectMock);

        $this->connectionMock->method('fetchOne')
            ->willReturn(1); // Lock acquired and released

        // Acquire lock first
        $this->model->execute($sku, $stockId);

        // Release lock
        $result = $this->model->release($sku, $stockId);

        $this->assertTrue($result);
    }

    /**
     * Test lock name generation consistency
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function testLockNameIsConsistent(): void
    {
        $sku = 'TEST-SKU';
        $stockId = 1;

        $selectMock = $this->createMock(Select::class);
        $selectMock->method('from')->willReturnSelf();

        $this->connectionMock->method('select')
            ->willReturn($selectMock);

        $capturedLockName1 = null;
        $capturedLockName2 = null;

        $this->connectionMock->method('fetchOne')
            ->willReturnCallback(function ($select, $bind) use (&$capturedLockName1, &$capturedLockName2) {
                if ($capturedLockName1 === null) {
                    $capturedLockName1 = $bind[0];
                } elseif ($capturedLockName2 === null) {
                    $capturedLockName2 = $bind[0];
                }
                return 1;
            });

        // Acquire and release should use same lock name
        $this->model->execute($sku, $stockId);
        $this->model->release($sku, $stockId);

        $this->assertEquals($capturedLockName1, $capturedLockName2);
    }

    /**
     * Test multiple locks can be acquired
     *
     * @return void
     */
    public function testMultipleLocksCanBeAcquired(): void
    {
        $sku1 = 'SKU-1';
        $sku2 = 'SKU-2';
        $stockId = 1;

        $selectMock = $this->createMock(Select::class);
        $selectMock->method('from')->willReturnSelf();

        $this->connectionMock->method('select')
            ->willReturn($selectMock);

        $this->connectionMock->method('fetchOne')
            ->willReturn(1);

        $result1 = $this->model->execute($sku1, $stockId);
        $result2 = $this->model->execute($sku2, $stockId);

        $this->assertTrue($result1);
        $this->assertTrue($result2);
    }

    /**
     * Test that _resetState clears cached connection and acquired locks
     *
     * @return void
     */
    public function testResetStateClearsCachedConnectionAndLocks(): void
    {
        $selectMock = $this->createMock(Select::class);
        $selectMock->method('from')->willReturnSelf();

        $this->connectionMock->method('select')->willReturn($selectMock);
        $this->connectionMock->method('fetchOne')->willReturn(1);

        $this->model->execute('SKU-1', 1);
        $this->model->_resetState();

        $this->resourceConnectionMock->expects($this->once())
            ->method('getConnection')
            ->willReturn($this->connectionMock);

        $result = $this->model->execute('SKU-2', 1);
        $this->assertTrue($result);
    }
}
