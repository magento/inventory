<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\ObjectManager\ResetAfterRequestInterface;

/**
 * Acquire lock for inventory operations to prevent race conditions
 */
class AcquireInventoryLock implements ResetAfterRequestInterface
{
    /**
     * Lock timeout in seconds
     */
    private const LOCK_TIMEOUT = 10;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var array
     */
    private $acquiredLocks = [];

    /**
     * @var AdapterInterface|null
     */
    private $connection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @inheritdoc
     */
    public function _resetState(): void
    {
        $this->connection = null;
        $this->acquiredLocks = [];
    }

    /**
     * Get database connection
     *
     * @return AdapterInterface
     */
    private function getConnection(): AdapterInterface
    {
        if ($this->connection === null) {
            $this->connection = $this->resourceConnection->getConnection();
        }
        return $this->connection;
    }

    /**
     * Acquire lock for SKU and stock combination
     *
     * @param string $sku
     * @param int $stockId
     * @return bool
     */
    public function execute(string $sku, int $stockId): bool
    {
        $lockName = $this->getLockName($sku, $stockId);
        $connection = $this->getConnection();

        $result = (bool)$connection->fetchOne(
            $connection->select()->from(
                new Expression('(SELECT GET_LOCK(?, ?) as lock_result)'),
                ['lock_result']
            ),
            [$lockName, self::LOCK_TIMEOUT]
        );

        if ($result) {
            $this->acquiredLocks[$lockName] = true;
        }

        return $result;
    }

    /**
     * Release lock for SKU and stock combination
     *
     * @param string $sku
     * @param int $stockId
     * @return bool
     */
    public function release(string $sku, int $stockId): bool
    {
        $lockName = $this->getLockName($sku, $stockId);
        $connection = $this->getConnection();

        $result = (bool)$connection->fetchOne(
            $connection->select()->from(
                new Expression('(SELECT RELEASE_LOCK(?) as lock_result)'),
                ['lock_result']
            ),
            [$lockName]
        );

        unset($this->acquiredLocks[$lockName]);

        return $result;
    }

    /**
     * Release all acquired locks
     *
     * @return void
     */
    public function releaseAll(): void
    {
        if (empty($this->acquiredLocks)) {
            return;
        }

        try {
            $connection = $this->getConnection();
            foreach (array_keys($this->acquiredLocks) as $lockName) {
                $connection->fetchOne(
                    $connection->select()->from(
                        new Expression('(SELECT RELEASE_LOCK(?) as lock_result)'),
                        ['lock_result']
                    ),
                    [$lockName]
                );
            }

        } catch (\Throwable $e) { //phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
            // Locks will be automatically released by MySQL when the connection closes
        } finally {
            $this->acquiredLocks = [];
        }
    }

    /**
     * Generate lock name for SKU and stock
     *
     * @param string $sku
     * @param int $stockId
     * @return string
     */
    private function getLockName(string $sku, int $stockId): string
    {
        // phpcs:ignore Magento2.Security.InsecureFunction
        return sprintf('inv_lock_%d_%s', $stockId, md5($sku));
    }

    /**
     * Destructor to ensure locks are released
     */
    public function __destruct()
    {
        $this->releaseAll();
    }
}
