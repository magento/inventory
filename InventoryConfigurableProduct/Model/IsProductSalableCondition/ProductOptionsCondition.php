<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Model\IsProductSalableCondition;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\InventoryConfigurableProduct\Model\IsConfigurableProductSalable;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;

/**
 * Verify bundle product options salable status.
 */
class ProductOptionsCondition implements IsProductSalableInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var IsConfigurableProductSalable
     */
    private $isConfigurableProductSalable;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param IsConfigurableProductSalable $isConfigurableProductSalable
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        IsConfigurableProductSalable $isConfigurableProductSalable
    ) {
        $this->productRepository = $productRepository;
        $this->isConfigurableProductSalable = $isConfigurableProductSalable;
    }

    /**
     * @inheritDoc
     */
    public function execute(string $sku, int $stockId): bool
    {
        $product = $this->productRepository->get($sku);
        if ($product->getTypeId() !== Configurable::TYPE_CODE) {
            return true;
        }

        return $this->isConfigurableProductSalable->execute($product, $stockId);
    }
}
