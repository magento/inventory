<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\InventoryConfigurableProduct\Model\IsProductSalableCondition;

use Magento\Catalog\Model\ProductRepository;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;

class ProductOptionsCondition implements IsProductSalableInterface
{
    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var Configurable
     */
    private $type;

    /**
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @param Configurable $type
     * @param ProductRepository $productRepository
     * @param AreProductsSalableInterface $areProductsSalable
     */
    public function __construct(
        Configurable $type,
        ProductRepository $productRepository,
        AreProductsSalableInterface $areProductsSalable
    ) {
        $this->type = $type;
        $this->productRepository = $productRepository;
        $this->areProductsSalable = $areProductsSalable;
    }
    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId): bool
    {
        $status = true;
        $product = $this->productRepository->get($sku);

        if ($product->getTypeId() === Configurable::TYPE_CODE) {
            $status = false;
            $options = $this->type->getConfigurableOptions($product);
            $skus = [[]];
            foreach ($options as $attribute) {
                $skus[] = array_column($attribute, 'sku');
            }
            $skus = array_merge(...$skus);
            $results = $this->areProductsSalable->execute($skus, $stockId);
            foreach ($results as $result) {
                if ($result->isSalable()) {
                    $status = true;
                    break;
                }
            }
        }
        return $status;
    }
}
