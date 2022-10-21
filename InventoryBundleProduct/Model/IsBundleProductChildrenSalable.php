<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProduct\Model;

use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;

class IsBundleProductChildrenSalable
{
    /**
     * @var \Magento\Bundle\Model\Product\Type
     */
    private $bundleType;

    /**
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @var GetProductIdsBySkusInterface
     */
    private $getProductIdsBySkus;

    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @param \Magento\Bundle\Model\Product\Type $bundleType
     * @param AreProductsSalableInterface $areProductsSalable
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     */
    public function __construct(
        \Magento\Bundle\Model\Product\Type $bundleType,
        AreProductsSalableInterface $areProductsSalable,
        GetProductIdsBySkusInterface $getProductIdsBySkus,
        GetSkusByProductIdsInterface $getSkusByProductIds
    ) {
        $this->bundleType = $bundleType;
        $this->areProductsSalable = $areProductsSalable;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->getSkusByProductIds = $getSkusByProductIds;
    }

    /**
     * Get bundle product salable status based on selections salable status
     *
     * Returns TRUE if:
     *
     *  - All options are optional: at least one selection is salable
     *  - Some options are required: at least one selection is salable in each required option
     *
     * @param string $sku
     * @param int $stockId
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(string $sku, int $stockId): bool
    {
        $productId = $this->getProductIdsBySkus->execute([$sku])[$sku];
        $childrenIds = $this->bundleType->getChildrenIds($productId, true);
        $childrenSkus = $this->getSkusByProductIds->execute(array_merge(...array_values($childrenIds)));
        $isSalable = false;
        foreach ($childrenIds as $childrenIdsPerOption) {
            $isSalable = false;
            foreach ($childrenIdsPerOption as $id) {
                $results = $this->areProductsSalable->execute([$childrenSkus[$id]], $stockId);
                $result = reset($results);
                if ($result && $result->isSalable()) {
                    $isSalable = true;
                    break;
                }
            }
            if (!$isSalable) {
                break;
            }
        }

        return $isSalable;
    }
}
