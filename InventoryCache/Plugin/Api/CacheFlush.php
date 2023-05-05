<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCache\Plugin\Api;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\InventoryCache\Model\FlushCacheByProductIds;

class CacheFlush
{
    /**
     * @var FlushCacheByProductIds
     */
    private FlushCacheByProductIds $flushCacheByIds;

    /**
     * @param FlushCacheByProductIds $flushCacheByIds
     */
    public function __construct(FlushCacheByProductIds $flushCacheByIds)
    {
        $this->flushCacheByIds = $flushCacheByIds;
    }

    /**
     * Clean product and child product cache after API save
     *
     * @param ProductRepository $subject
     * @param ProductInterface $result
     * @param ProductInterface $product
     * @param bool $saveOptions
     * @return ProductInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(
        ProductRepository  $subject,
        ProductInterface $result,
        ProductInterface $product,
        bool $saveOptions
    ): ProductInterface {
        $childProductIds = $result->getTypeInstance()->getChildrenIds($result->getId());
        $productIdsToFlush[] = [$result->getId()];
        foreach ($childProductIds as $productIds) {
            if (empty($productIds)) {
                continue;
            }
            $productIdsToFlush[] = $productIds;
        }
        $productIdsToFlush = array_merge(...$productIdsToFlush);
        $this->flushCacheByIds->execute($productIdsToFlush);

        return $result;
    }
}
