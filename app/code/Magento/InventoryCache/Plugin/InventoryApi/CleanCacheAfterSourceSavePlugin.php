<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCache\Plugin\InventoryApi;

use Magento\Framework\App\Cache\TypeListInterface as CacheTypeListInterface;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;

/**
 * Clean cache after source save plugin
 */
class CleanCacheAfterSourceSavePlugin
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
     * @param SourceRepositoryInterface $subject
     * @param void $result
     * @param SourceInterface $source
     * @return void
     */
    public function afterSave(
        SourceRepositoryInterface $subject,
        $result,
        SourceInterface $source
    ) {
        $cacheTypesToInvalidate = [
            'full_page',
        ];

        foreach ($cacheTypesToInvalidate as $cacheType) {
            $this->cacheTypeList->invalidate($cacheType);
        }
    }
}
