<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryBundleProduct\Model\IsProductSalableCondition;

use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magento\Bundle\Model\Product\Type;
use Magento\InventoryBundleProduct\Model\GetBundleProductStockStatus;

class ProductOptionsCondition implements IsProductSalableInterface
{
    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var Type
     */
    private $type;

    /**
     * @var GetBundleProductStockStatus
     */
    private $getBundleProductStockStatus;

    /**
     * @param Type $type
     * @param ProductRepository $productRepository
     * @param GetBundleProductStockStatus $getBundleProductStockStatus
     */
    public function __construct(
        Type $type,
        ProductRepository $productRepository,
        GetBundleProductStockStatus $getBundleProductStockStatus
    ) {
        $this->type = $type;
        $this->productRepository = $productRepository;
        $this->getBundleProductStockStatus = $getBundleProductStockStatus;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId): bool
    {
        $status = true;
        $product = $this->productRepository->get($sku);

        if ($product->getTypeId() === Type::TYPE_CODE) {
            $options = $this->type->getOptionsCollection($product);
            try {
                $status = $this->getBundleProductStockStatus->execute($product, $options->getItems(), $stockId);
            } catch (LocalizedException $e) {
                $status = false;
            }
        }
        return $status;
    }
}
