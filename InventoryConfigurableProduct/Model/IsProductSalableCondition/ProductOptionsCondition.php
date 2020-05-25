<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\InventoryConfigurableProduct\Model\IsProductSalableCondition;

use Magento\Catalog\Model\ProductRepository;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\InventoryConfigurableProduct\Model\IsAnyConfigurableOptionSalable;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;

/**
 * Are configurable product option in stock condition.
 */
class ProductOptionsCondition implements IsProductSalableInterface
{
    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var IsAnyConfigurableOptionSalable
     */
    private $isAnyConfigurableOptionSalable;

    /**
     * @param ProductRepository $productRepository
     * @param IsAnyConfigurableOptionSalable $isAnyConfigurableOptionSalable
     */
    public function __construct(
        ProductRepository $productRepository,
        IsAnyConfigurableOptionSalable $isAnyConfigurableOptionSalable
    ) {
        $this->productRepository = $productRepository;
        $this->isAnyConfigurableOptionSalable = $isAnyConfigurableOptionSalable;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId): bool
    {
        $status = true;
        $product = $this->productRepository->get($sku);

        if ($product->getTypeId() === Configurable::TYPE_CODE) {
            $status = $this->isAnyConfigurableOptionSalable->execute($product, $stockId);
        }
        return $status;
    }
}
