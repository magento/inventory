<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCache\Model;

use Magento\Framework\EntityManager\EventManager;
use Magento\Framework\Indexer\CacheContextFactory;
use Magento\Framework\App\CacheInterface;

/**
 * Clean cache for given cache tag.
 */
class FlushCacheByCacheTag
{
    /**
     * @var CacheContextFactory
     */
    private $cacheContextFactory;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var CacheInterface
     */
    private $appCache;

    /**
     * @param CacheContextFactory $cacheContextFactory
     * @param EventManager $eventManager
     * @param CacheInterface $appCache
     */
    public function __construct(
        CacheContextFactory $cacheContextFactory,
        EventManager $eventManager,
        CacheInterface $appCache
    ) {
        $this->cacheContextFactory = $cacheContextFactory;
        $this->eventManager = $eventManager;
        $this->appCache = $appCache;
    }

    /**
     * Clean cache for given entity and entity ids.
     *
     * @param string $cacheTag
     * @param array $entityIds
     * @return void
     */
    public function execute(string $cacheTag, array $entityIds): void
    {
        if ($entityIds) {
            $cacheContext = $this->cacheContextFactory->create();
            $cacheContext->registerEntities($cacheTag, $entityIds);
            $this->eventManager->dispatch('clean_cache_by_tags', ['object' => $cacheContext]);
            $this->appCache->clean($cacheContext->getIdentities());
        }
    }
}
