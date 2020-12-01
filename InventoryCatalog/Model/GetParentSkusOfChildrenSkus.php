<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCatalog\Model;

use Magento\Catalog\Model\ResourceModel\Product\Relation;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalogApi\Model\GetParentSkusOfChildrenSkusInterface;

/**
 * @inheritdoc
 */
class GetParentSkusOfChildrenSkus implements GetParentSkusOfChildrenSkusInterface
{
    /**
     * @var Relation
     */
    private $productRelationResource;

    /**
     * @var GetProductIdsBySkusInterface
     */
    private $getProductIdsBySkus;

    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @param Relation $productRelationResource
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     */
    public function __construct(
        Relation $productRelationResource,
        GetProductIdsBySkusInterface $getProductIdsBySkus,
        GetSkusByProductIdsInterface $getSkusByProductIds
    ) {
        $this->productRelationResource = $productRelationResource;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->getSkusByProductIds = $getSkusByProductIds;
    }

    /**
     * @inheritdoc
     */
    public function execute(array $skus): array
    {
        $childIdsOfSkus = $this->getProductIdsBySkus->execute($skus);
        $parentIdsOfChildIds = $this->productRelationResource->getRelationsByChildren($childIdsOfSkus);

        if (!$parentIdsOfChildIds) {
            return [];
        }

        $flatParentIds = array_merge(...$parentIdsOfChildIds);

        $parentSkusOfIds = $this->getSkusByProductIds->execute(array_unique($flatParentIds));
        $parentSkusOfChildSkus = array_fill_keys($skus, []);

        foreach ($skus as $sku) {
            $childId = $childIdsOfSkus[$sku];

            if (isset($parentIdsOfChildIds[$childId])) {
                foreach ($parentIdsOfChildIds[$childId] as $parentId) {
                    $parentSkusOfChildSkus[$sku][] = $parentSkusOfIds[$parentId];
                }
            }
        }

        return $parentSkusOfChildSkus;
    }
}
