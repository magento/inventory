<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

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
     * @var array
     */
    private $cache = [];

    /**
     * @param GetSkusByProductIds $getSkusByProductIds
     */
    public function __construct(
        GetSkusByProductIds $getSkusByProductIds
    ) {
        $this->getSkusByProductIds = $getSkusByProductIds;
    }

    /**
     * @inheritdoc
     */
    public function execute(array $productIds): array
    {
        $skusByIds = [];
        $loadIds = [];
        foreach ($productIds as $productId) {
            if (isset($this->cache[$productId])) {
                $skusByIds[$productId] = $this->cache[$productId];
            } else {
                $loadIds[] = $productId;
                $skusByIds[$productId] = null;
            }
        }
        if ($loadIds) {
            $loadedSkuByIds = $this->getSkusByProductIds->execute($loadIds);
            foreach ($loadedSkuByIds as $productId => $sku) {
                $skusByIds[$productId] = $sku;
                $this->cache[$productId] = $sku;
            }
        }

        return $skusByIds;
    }

    /**
     * Saves id/sku pair into cache
     *
     * @param int $id
     * @param string $sku
     */
    public function save(int $id, string $sku): void
    {
        $this->cache[$id] = $sku;
    }
}
