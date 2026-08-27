<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */

declare(strict_types=1);

namespace Magento\InventoryApi\Model;

class CachePool implements CacheInterface
{
    /**
     * @param CacheInterface[] $pool
     */
    public function __construct(
        private readonly array $pool = []
    ) {
        // Ensures that all items in the pool implement the interface
        array_map(
            static fn (CacheInterface $cache) => $cache,
            $this->pool
        );
    }

    /**
     * @inheritDoc
     */
    public function warmup(array $skus, int $stockId): void
    {
        foreach ($this->pool as $cache) {
            $cache->warmup($skus, $stockId);
        }
    }
    
    /**
     * @inheritDoc
     */
    public function clean(array $skus, ?int $stockId): void
    {
        foreach ($this->pool as $cache) {
            $cache->clean($skus, $stockId);
        }
    }
}
