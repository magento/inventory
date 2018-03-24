<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCache\Plugin\InventoryApi;

use Magento\Framework\App\Cache\TypeListInterface as CacheTypeListInterface;
use Magento\InventoryApi\Api\StockSourceLinksDeleteInterface;

/**
 * Invalidate InventoryIndexer
 */
class CleanCacheAfterStockSourceLinksDeletePlugin
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
     * @param StockSourceLinksDeleteInterface $subject
     * @return void
     */
    public function afterExecute(StockSourceLinksDeleteInterface $subject)
    {
        $cacheTypesToInvalidate = [
            'full_page',
        ];

        foreach ($cacheTypesToInvalidate as $cacheType) {
            $this->cacheTypeList->invalidate($cacheType);
        }
    }
}
