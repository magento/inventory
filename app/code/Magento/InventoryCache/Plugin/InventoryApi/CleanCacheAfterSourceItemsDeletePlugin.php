<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCache\Plugin\InventoryApi;

use Magento\Framework\App\Cache\TypeListInterface as CacheTypeListInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemsDeleteInterface;

/**
 * Reindex after source items delete plugin
 */
class CleanCacheAfterSourceItemsDeletePlugin
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
     * @param SourceItemsDeleteInterface $subject
     * @param void $result
     * @param SourceItemInterface[] $sourceItems
     * @return void
     */
    public function afterExecute(
        SourceItemsDeleteInterface $subject,
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
