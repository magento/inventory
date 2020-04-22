<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\InventoryCatalog\Model\ResourceModel\GetProductStatusByProductIdAndStoreId;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;

/**
 * Get product status for given stock service.
 */
class GetProductStatusBySkuAndStoreId
{
    /**
     * @var GetProductStatusByProductIdAndStoreId
     */
    private $getProductStatus;

    /**
     * @var GetProductIdsBySkusInterface
     */
    private $getProductIdsBySkus;

    /**
     * @param GetProductStatusByProductIdAndStoreId $getProductStatus
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     */
    public function __construct(
        GetProductStatusByProductIdAndStoreId $getProductStatus,
        GetProductIdsBySkusInterface $getProductIdsBySkus
    ) {
        $this->getProductStatus = $getProductStatus;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
    }

    /**
     * Retrieve product status by sku.
     *
     * @param string $sku
     * @param int $storeId
     * @return int
     */
    public function execute(string $sku, int $storeId): int
    {
        try {
            $productId = $this->getProductIdsBySkus->execute([$sku])[$sku];
            return $this->getProductStatus->execute((int)$productId, $storeId);
        } catch (\Exception $e) {
            return 0;
        }
    }
}
