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
use Magento\InventoryCatalogApi\Model\GetParentSkusByChildrenSkusInterface;

/**
 * @inheritdoc
 */
class GetParentSkusByChildrenSkus implements GetParentSkusByChildrenSkusInterface
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
        $parentSkus = [];
        $childIds = $this->getProductIdsBySkus->execute($skus);
        $parentIds = $this->productRelationResource->getRelationsByChildren($childIds);

        if ($parentIds) {
            $parentSkus = $this->getSkusByProductIds->execute(array_unique($parentIds));
        }

        return $parentSkus;
    }
}
