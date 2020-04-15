<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\InventoryCatalog\Model\ResourceModel\GetProductStatus;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;

/**
 * Get product status service.
 */
class GetProductStatusBySku
{
    /**
     * @var ProductAttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @var GetProductStatus
     */
    private $getProductStatus;

    /**
     * @var GetProductIdsBySkusInterface
     */
    private $getProductIdsBySkus;

    /**
     * @param ProductAttributeRepositoryInterface $attributeRepository
     * @param GetProductStatus $getProductStatus
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     */
    public function __construct(
        ProductAttributeRepositoryInterface $attributeRepository,
        GetProductStatus $getProductStatus,
        GetProductIdsBySkusInterface $getProductIdsBySkus
    ) {
        $this->attributeRepository = $attributeRepository;
        $this->getProductStatus = $getProductStatus;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
    }

    /**
     * Retrieve product status by sku.
     *
     * @param string $sku
     * @return int
     */
    public function execute(string $sku): int
    {
        try {
            $statusAttribute = $this->attributeRepository->get(ProductInterface::STATUS);
            $productId = $this->getProductIdsBySkus->execute([$sku])[$sku];

            return $this->getProductStatus->execute((int)$statusAttribute->getAttributeId(), (int)$productId);
        } catch (\Exception $e) {
            return 0;
        }
    }
}
