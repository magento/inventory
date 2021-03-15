<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCache\Model;

/**
 * Clean cache for given category ids.
 */
class FlushCacheByCategoryIds
{
    /**
     * @var string
     */
    private $categoryCacheTag;

    /**
     * @var FlushCacheByCacheTag
     */
    private $flushCacheByCacheTag;

    /**
     * @param string $categoryCacheTag
     * @param FlushCacheByCacheTag $flushCacheByCacheTag
     */
    public function __construct(
        string $categoryCacheTag,
        FlushCacheByCacheTag $flushCacheByCacheTag
    ) {
        $this->categoryCacheTag = $categoryCacheTag;
        $this->flushCacheByCacheTag = $flushCacheByCacheTag;
    }

    /**
     * Clean cache for given category ids.
     *
     * @param array $categoryIds
     * @return void
     */
    public function execute(array $categoryIds): void
    {
        $this->flushCacheByCacheTag->execute($this->categoryCacheTag, $categoryIds);
    }
}
