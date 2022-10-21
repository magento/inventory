<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Plugin\InventorySales\Model\IsProductSalableCondition;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\InventoryConfigurableProduct\Model\IsProductSalableCondition\IsConfigurableProductChildrenSalable;
use Magento\InventorySalesApi\Model\GetIsQtySalableInterface;

class GetIsQtySalableForConfigurableProduct
{
    /**
     * @var GetProductTypesBySkusInterface
     */
    private $getProductTypesBySkus;

    /**
     * @var IsConfigurableProductChildrenSalable
     */
    private $isConfigurableProductChildrenSalable;

    /**
     * @param IsConfigurableProductChildrenSalable $isConfigurableProductChildrenSalable
     * @param GetProductTypesBySkusInterface $getProductTypesBySkus
     */
    public function __construct(
        IsConfigurableProductChildrenSalable $isConfigurableProductChildrenSalable,
        GetProductTypesBySkusInterface $getProductTypesBySkus
    ) {
        $this->getProductTypesBySkus = $getProductTypesBySkus;
        $this->isConfigurableProductChildrenSalable = $isConfigurableProductChildrenSalable;
    }

    /**
     * Check configurable product salable status based on selections salable status
     *
     * @param GetIsQtySalableInterface $getIsQtySalable
     * @param bool $isSalable
     * @param string $sku
     * @param int $stockId
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(
        GetIsQtySalableInterface $getIsQtySalable,
        bool $isSalable,
        string $sku,
        int $stockId
    ): bool {
        return $this->getProductTypesBySkus->execute([$sku])[$sku] === Configurable::TYPE_CODE
            ? $this->isConfigurableProductChildrenSalable->execute($sku, $stockId)
            : $isSalable;
    }
}
