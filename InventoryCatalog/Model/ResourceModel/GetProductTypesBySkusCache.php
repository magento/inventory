<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model\ResourceModel;

use Magento\InventoryCatalog\Model\Cache\ProductTypesBySkusStorage;
use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;

/**
 * @inheritdoc
 */
class GetProductTypesBySkusCache implements GetProductTypesBySkusInterface
{
    /**
     * @var GetProductTypesBySkus
     */
    private $getProductTypesBySkus;

    /**
     * @var ProductTypesBySkusStorage
     */
    private $cache;

    /**
     * @param GetProductTypesBySkus $getProductTypesBySkus
     * @param ProductTypesBySkusStorage $cache
     */
    public function __construct(
        GetProductTypesBySkus $getProductTypesBySkus,
        ProductTypesBySkusStorage $cache
    ) {
        $this->getProductTypesBySkus = $getProductTypesBySkus;
        $this->cache = $cache;
    }

    /**
     * @inheritdoc
     */
    public function execute(array $skus): array
    {
        $typesBySkus = [];
        $loadSkus = [];
        foreach ($skus as $sku) {
            $type = $this->cache->get((string) $sku);
            if ($type !== null) {
                $typesBySkus[$sku] = $type;
            } else {
                $loadSkus[] = $sku;
                $typesBySkus[$sku] = null;
            }
        }
        if ($loadSkus) {
            $loadedTypesBySkus = $this->getProductTypesBySkus->execute($loadSkus);
            foreach ($loadedTypesBySkus as $sku => $type) {
                $typesBySkus[$sku] = (string) $type;
                $this->cache->set((string) $sku, (string) $type);
            }
        }

        return $typesBySkus;
    }
}
