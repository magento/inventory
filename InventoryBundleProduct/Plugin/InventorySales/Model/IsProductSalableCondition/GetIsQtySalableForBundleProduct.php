<?php
/**
 * Copyright 2021 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProduct\Plugin\InventorySales\Model\IsProductSalableCondition;

use Magento\Bundle\Model\Product\Type;
use Magento\InventoryBundleProduct\Model\IsBundleProductChildrenSalable;
use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\InventorySalesApi\Model\GetIsQtySalableInterface;

class GetIsQtySalableForBundleProduct
{
    /**
     * @var IsBundleProductChildrenSalable
     */
    private $isBundleProductChildrenSalable;

    /**
     * @var GetProductTypesBySkusInterface
     */
    private $getProductTypesBySkus;

    /**
     * @param IsBundleProductChildrenSalable $isBundleProductChildrenSalable
     * @param GetProductTypesBySkusInterface $getProductTypesBySkus
     */
    public function __construct(
        IsBundleProductChildrenSalable $isBundleProductChildrenSalable,
        GetProductTypesBySkusInterface $getProductTypesBySkus
    ) {
        $this->isBundleProductChildrenSalable = $isBundleProductChildrenSalable;
        $this->getProductTypesBySkus = $getProductTypesBySkus;
    }

    /**
     * Check bundle product salable status based on selections salable status
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
        if ($this->getProductTypesBySkus->execute([$sku])[$sku] === Type::TYPE_CODE) {
            $isSalable = $this->isBundleProductChildrenSalable->execute($sku, $stockId);
        }
        return $isSalable;
    }
}
