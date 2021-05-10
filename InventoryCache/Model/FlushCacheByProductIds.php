<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCache\Model;

/**
 * Clean cache for given product ids.
 */
class FlushCacheByProductIds
{
    /**
     * @var string
     */
    private $productCacheTag;

    /**
     * @var FlushCacheByCacheTag
     */
    private $flushCacheByCacheTag;

    /**
     * @param string $productCacheTag
     * @param FlushCacheByCacheTag $flushCacheByCacheTag
     */
    public function __construct(
        string $productCacheTag,
        FlushCacheByCacheTag $flushCacheByCacheTag
    ) {
        $this->productCacheTag = $productCacheTag;
        $this->flushCacheByCacheTag = $flushCacheByCacheTag;
    }

    /**
     * Clean cache for given product ids.
     *
     * @param array $productIds
     * @return void
     */
    public function execute(array $productIds): void
    {
        $this->flushCacheByCacheTag->execute($this->productCacheTag, $productIds);
    }
}
