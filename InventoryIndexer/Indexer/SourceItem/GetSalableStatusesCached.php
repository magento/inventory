<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Indexer\SourceItem;

class GetSalableStatusesCached
{
    /**
     * @var array
     */
    private array $cache = [];

    /**
     * @var GetSalableStatuses
     */
    private GetSalableStatuses $getSalableStatuses;

    /**
     * @param GetSalableStatuses $getSalableStatuses
     */
    public function __construct(GetSalableStatuses $getSalableStatuses)
    {
        $this->getSalableStatuses = $getSalableStatuses;
    }

    /**
     * Get salable statuses for products based on affected source items
     *
     * @param array $sourceItemIds
     * @param string $additionalCacheKey
     * @return array
     */
    public function execute(array $sourceItemIds, string $additionalCacheKey = '') : array
    {
        $cacheKey = $this->getCacheKey($sourceItemIds, $additionalCacheKey);

        if (!array_key_exists($cacheKey, $this->cache)) {
            $this->cache[$cacheKey] = $this->getSalableStatuses->execute($sourceItemIds);
        }

        return $this->cache[$cacheKey];
    }

    /**
     * Returns cache key for source items ids and extra key
     *
     * @param array $sourceItemIds
     * @param string $extraKey
     * @return string
     */
    private function getCacheKey(array $sourceItemIds, string $extraKey)
    {
        sort($sourceItemIds);
        $sourceItemIds = array_unique($sourceItemIds);

        return sha1(implode('|', $sourceItemIds) . '_' . $extraKey);
    }
}
