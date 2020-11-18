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
        // ['conf1-red', 'conf1-blue', 'conf2-red', 'simple1']

        // ['conf1-red' => 12, 'conf1-blue' => 13, 'conf2-red' => 15, 'simple1' => 1]
        $productIdsOfSku = $this->getProductIdsBySkus->execute($skus);

        // [12 => 14, 12 => 20, 13 => 14, 15 => 17] values are parents
        $parentIdsOfChildIds = $this->productRelationResource->getRelationsByChildren($productIdsOfSku);

        // [14 => 'conf1', 17 => 'conf2', 20 => 'conf3']
        $parentSkusOfIds = $this->getSkusByProductIds->execute(array_unique($parentIdsOfChildIds));

        // ['conf1-red' => [], ... 'simple1' => []] prepare resulting array
        $parentSkusOfChildSkus = array_fill_keys($skus, []);
        foreach ($skus as $sku) {
            $skuId = $productIdsOfSku[$sku];

            if (isset($parentIdsOfChildIds[$skuId])) {
                $parentId = $parentIdsOfChildIds[$skuId];
                $parentSkusOfChildSkus[$sku][] = $parentSkusOfIds[$parentId];
            }
        }

        // ['conf-red' => ['conf1', 'conf3'], 'conf-blue' => ['conf1'], 'conf-2red' => ['conf2'], 'simple1' => []]
        return $parentSkusOfChildSkus;
    }
}
