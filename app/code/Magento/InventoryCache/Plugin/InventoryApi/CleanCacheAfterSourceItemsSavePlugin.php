<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCache\Plugin\InventoryApi;

use Magento\Framework\App\Cache\TypeListInterface as CacheTypeListInterface;
use Magento\Framework\Indexer\IndexerInterfaceFactory;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;

/**
 * Reindex after source items save plugin
 */
class CleanCacheAfterSourceItemsSavePlugin
{
    /**
     * @var CacheTypeListInterface
     */
    private $cacheTypeList;

    /**
     * @param CacheTypeListInterface $cacheTypeList
     */
    public function __construct(CacheTypeListInterface $cacheTypeList)
    {
        $this->cacheTypeList = $cacheTypeList;
    }

    /**
     * @param SourceItemsSaveInterface $subject
     * @param void $result
     * @param SourceItemInterface[] $sourceItems
     * @return void
     */
    public function afterExecute(
        SourceItemsSaveInterface $subject,
        $result,
        array $sourceItems
    ) {
        $cacheTypesToInvalidate = [
            'full_page',
        ];

        foreach ($cacheTypesToInvalidate as $cacheType) {
            $this->cacheTypeList->invalidate($cacheType);
        }
    }
}
