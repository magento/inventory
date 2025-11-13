<?php
/**
 * Copyright 2025 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\Catalog\Model\ResourceModel\Product\Relation;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventoryCatalogApi\Model\GetChildrenSkusOfParentSkusInterface;

/**
 * @inheritdoc
 */
class GetChildrenSkusOfParentSkus implements GetChildrenSkusOfParentSkusInterface
{
    /**
     * @param Relation $productRelationResource
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     */
    public function __construct(
        private readonly Relation $productRelationResource,
        private readonly GetProductIdsBySkusInterface $getProductIdsBySkus,
        private readonly GetSkusByProductIdsInterface $getSkusByProductIds
    ) {
    }

    /**
     * @inheritdoc
     */
    public function execute(array $skus): array
    {
        if (!$skus) {
            return [];
        }

        $parentIds = $this->getProductIdsBySkus->execute($skus);
        $childIdsOfParentIds = $this->productRelationResource->getRelationsByParent(array_values($parentIds));
        $flatChildIds = array_merge([], ...$childIdsOfParentIds);
        $childSkus = $flatChildIds ? $this->getSkusByProductIds->execute(array_unique($flatChildIds)) : [];

        $childSkusOfParentSkus = [];
        foreach ($skus as $sku) {
            $parentId = $parentIds[$sku];
            $childSkusOfParentSkus[$sku] = array_map(
                fn ($childId) => $childSkus[$childId],
                $childIdsOfParentIds[$parentId] ?? []
            );
        }

        return $childSkusOfParentSkus;
    }
}
