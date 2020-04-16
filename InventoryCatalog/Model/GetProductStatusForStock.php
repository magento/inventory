<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\InventoryCatalog\Model\ResourceModel\GetProductStatusByProductIdAndStockId;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;

/**
 * Get product status for given stock service.
 */
class GetProductStatusForStock
{
    /**
     * @var GetProductStatusByProductIdAndStockId
     */
    private $getProductStatus;

    /**
     * @var GetProductIdsBySkusInterface
     */
    private $getProductIdsBySkus;

    /**
     * @param ProductAttributeRepositoryInterface $attributeRepository
     * @param GetProductStatusByProductIdAndStockId $getProductStatus
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     */
    public function __construct(
        GetProductStatusByProductIdAndStockId $getProductStatus,
        GetProductIdsBySkusInterface $getProductIdsBySkus
    ) {
        $this->getProductStatus = $getProductStatus;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
    }

    /**
     * Retrieve product status by sku.
     *
     * @param string $sku
     * @param int $stockId
     * @return int
     */
    public function execute(string $sku, int $stockId): int
    {
        try {
            $productId = $this->getProductIdsBySkus->execute([$sku])[$sku];
            return $this->getProductStatus->execute((int)$productId, $stockId);
        } catch (\Exception $e) {
            return 0;
        }
    }
}
