<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCache\Plugin\InventoryIndexer\Queue\Reservation\UpdateSalabilityStatus;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCache\Model\FlushCacheByProductIds;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryIndexer\Model\Queue\UpdateIndexSalabilityStatus;

/**
 * Clean cache for corresponding products after stock status update.
 */
class CacheFlush
{
    /**
     * @var FlushCacheByProductIds
     */
    private $flushCacheByIds;

    /**
     * @var GetProductIdsBySkusInterface
     */
    private $getProductIdsBySkus;

    /**
     * @param FlushCacheByProductIds $flushCacheByIds
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     */
    public function __construct(
        FlushCacheByProductIds $flushCacheByIds,
        GetProductIdsBySkusInterface $getProductIdsBySkus
    ) {
        $this->flushCacheByIds = $flushCacheByIds;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
    }

    /**
     * Flush cache after reindex.
     *
     * @param UpdateIndexSalabilityStatus $subject
     * @param array $skusAffected
     *
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(UpdateIndexSalabilityStatus $subject, array $skusAffected)
    {
        if ($skus = array_keys($skusAffected)) {
            try {
                $this->flushCacheByIds->execute($this->getProductIdsBySkus->execute($skus));
            } catch (NoSuchEntityException $e) { // phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
                // Do nothing.
            }
        }

        return $skusAffected;
    }
}
