<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\InventoryCatalog\Model\Cache\ProductIdsBySkusStorage;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;

/**
 * @inheritdoc
 */
class GetProductIdsBySkusCache implements GetProductIdsBySkusInterface
{
    /**
     * @var GetProductIdsBySkus
     */
    private $getProductIdsBySkus;

    /**
     * @var ProductIdsBySkusStorage
     */
    private $cache;

    /**
     * @param GetProductIdsBySkus $getProductIdsBySkus
     * @param ProductIdsBySkusStorage $cache
     */
    public function __construct(
        GetProductIdsBySkus $getProductIdsBySkus,
        ProductIdsBySkusStorage $cache
    ) {
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->cache = $cache;
    }

    /**
     * @inheritdoc
     */
    public function execute(array $skus): array
    {
        $idsBySkus = [];
        $loadSkus = [];
        foreach ($skus as $sku) {
            $id = $this->cache->get((string) $sku);
            if ($id !== null) {
                $idsBySkus[$sku] = $id;
            } else {
                $loadSkus[] = $sku;
                $idsBySkus[$sku] = null;
            }
        }
        if ($loadSkus) {
            $loadedIdsBySkus = $this->getProductIdsBySkus->execute($loadSkus);
            foreach ($loadedIdsBySkus as $sku => $id) {
                $idsBySkus[$sku] = (int) $id;
                $this->cache->set((string) $sku, (int) $id);
            }
        }

        return $idsBySkus;
    }
}
