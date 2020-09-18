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
use Magento\InventorySalesApi\Api\IsProductSalableInterface;

class IsBundleSaleableCondition implements IsProductSalableInterface
{
    /**
     * @var Type
     */
    private $bundleProductType;

    /**
     * @var ProductRepositoryInterface
     */
    private $repository;

    /**
     * @var GetBundleProductStockStatus
     */
    private $getBundleProductStockStatus;

    /**
     * @param Type $type
     * @param ProductRepositoryInterface $repository
     * @param GetBundleProductStockStatus $getBundleProductStockStatus
     */
    public function __construct(
        Type $type,
        ProductRepositoryInterface $repository,
        GetBundleProductStockStatus $getBundleProductStockStatus
    ) {
        $this->bundleProductType = $type;
        $this->repository = $repository;
        $this->getBundleProductStockStatus = $getBundleProductStockStatus;
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
        $status = false;
        try {
            $product = $this->repository->get($sku);
            if ($product->getTypeId() === Type::TYPE_CODE) {
                /** @noinspection PhpParamsInspection */
                $options = $this->bundleProductType->getOptionsCollection($product);
                $status = (int)$this->getBundleProductStockStatus->execute(
                    $product,
                    $options->getItems(),
                    $stockId
                );
            }
        } catch (LocalizedException $e) {
            $status = false;
        }

        return $status;
    }
}
