<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Model\IsProductSalableCondition;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\InventoryCatalogApi\Model\GetProductIdsBySkusInterface;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;

/**
 * Service which checks whether any configurable product child is salable in a given Stock
 */
class IsConfigurableProductChildrenSalable
{
    /**
     * @var Configurable
     */
    private $configurable;

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
     * @param Configurable $configurable
     * @param AreProductsSalableInterface $areProductsSalable
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     */
    public function __construct(
        Configurable $configurable,
        AreProductsSalableInterface $areProductsSalable,
        GetProductIdsBySkusInterface $getProductIdsBySkus,
        GetSkusByProductIdsInterface $getSkusByProductIds
    ) {
        $this->configurable = $configurable;
        $this->areProductsSalable = $areProductsSalable;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->getSkusByProductIds = $getSkusByProductIds;
    }

    /**
     * Get configurable product salable status based on children products salable status
     *
     * Returns TRUE if:
     *
     *  - at least one child is salable
     *
     * @param string $sku
     * @param int $stockId
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(string $sku, int $stockId): bool
    {
        $isSalable = false;
        $productId = $this->getProductIdsBySkus->execute([$sku])[$sku];
        $ids = $this->configurable->getChildrenIds($productId);
        $childrenSkus = $this->getSkusByProductIds->execute($ids[0]);
        // check associated products salability one by one rather in batch for performance reason
        foreach ($childrenSkus as $childSku) {
            $results = $this->areProductsSalable->execute([$childSku], $stockId);
            $result = reset($results);
            if ($result && $result->isSalable()) {
                $isSalable = true;
                break;
            }
        }

        return $isSalable;
    }
}
