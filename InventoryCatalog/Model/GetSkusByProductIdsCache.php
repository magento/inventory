<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\InventoryCatalog\Model\Cache\ProductSkusByIdsStorage;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;

/**
 * @inheritdoc
 */
class GetSkusByProductIdsCache implements GetSkusByProductIdsInterface
{
    /**
     * @var GetSkusByProductIds
     */
    private $getSkusByProductIds;

    /**
     * @var ProductSkusByIdsStorage
     */
    private $cache;

    /**
     * @param GetSkusByProductIds $getSkusByProductIds
     * @param ProductSkusByIdsStorage $cache
     */
    public function __construct(
        GetSkusByProductIds $getSkusByProductIds,
        ProductSkusByIdsStorage $cache
    ) {
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->cache = $cache;
    }

    /**
     * @inheritdoc
     */
    public function execute(array $productIds): array
    {
        $skusByIds = [];
        $loadIds = [];
        foreach ($productIds as $productId) {
            $sku = $this->cache->get((int) $productId);
            if ($sku !== null) {
                $skusByIds[$productId] = $sku;
            } else {
                $loadIds[] = $productId;
                $skusByIds[$productId] = null;
            }
        }
        if ($loadIds) {
            $loadedSkuByIds = $this->getSkusByProductIds->execute($loadIds);
            foreach ($loadedSkuByIds as $productId => $sku) {
                $skusByIds[$productId] = (string) $sku;
                $this->cache->set((int) $productId, (string) $sku);
            }
        }

        return $skusByIds;
    }
}
