<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryBundleProduct\Model\IsProductSalableCondition;

use Magento\Bundle\Model\Product\Type;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryBundleProduct\Model\GetBundleProductStockStatus;
use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;

/**
 * Check if bundle product is salable with bundle options.
 */
class IsBundleSaleableCondition implements IsProductSalableInterface
{
    /**
     * @var Type
     */
    private $bundleProductType;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var GetBundleProductStockStatus
     */
    private $getBundleProductStockStatus;

    /**
     * @var GetProductTypesBySkusInterface
     */
    private $getProductTypesBySkus;

    /**
     * @param Type $type
     * @param ProductRepositoryInterface $repository
     * @param GetBundleProductStockStatus $getBundleProductStockStatus
     * @param GetProductTypesBySkusInterface $getProductTypesBySkus
     */
    public function __construct(
        Type $type,
        ProductRepositoryInterface $repository,
        GetBundleProductStockStatus $getBundleProductStockStatus,
        GetProductTypesBySkusInterface $getProductTypesBySkus
    ) {
        $this->bundleProductType = $type;
        $this->productRepository = $repository;
        $this->getBundleProductStockStatus = $getBundleProductStockStatus;
        $this->getProductTypesBySkus = $getProductTypesBySkus;
    }

    /**
     * Is product salable for bundle product.
     *
     * @param string $sku
     * @param int $stockId
     *
     * @return bool
     */
    public function execute(string $sku, int $stockId): bool
    {
        $status = true;
        try {
            $types = $this->getProductTypesBySkus->execute([$sku]);
            if (!isset($types[$sku]) || $types[$sku] !== Type::TYPE_CODE) {
                return $status;
            }

            $product = $this->productRepository->get($sku);
            /** @noinspection PhpParamsInspection */
            $options = $this->bundleProductType->getOptionsCollection($product);
            $status = $this->getBundleProductStockStatus->execute(
                $product,
                $options->getItems(),
                $stockId
            );
        } catch (LocalizedException $e) {
            $status = false;
        }

        return $status;
    }
}
