<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;

/**
 * Is configurable product salable considering configurable options service.
 */
class IsConfigurableProductSalable
{
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
     * @param AreProductsSalableInterface $areProductsSalable
     */
    public function __construct(Configurable $type, AreProductsSalableInterface $areProductsSalable)
    {
        $this->type = $type;
        $this->areProductsSalable = $areProductsSalable;
    }

    /**
     * Verify configurable product salable status considering configurable options.
     *
     * @param ProductInterface $product
     * @param int $stockId
     * @return bool
     */
    public function execute(ProductInterface $product, int $stockId): bool
    {
        $salable = false;
        $options = $this->type->getConfigurableOptions($product);
        $skus = [[]];
        foreach ($options as $attribute) {
            $skus[] = array_column($attribute, 'sku');
        }
        $skus = array_merge(...$skus);
        $results = $this->areProductsSalable->execute($skus, $stockId);
        foreach ($results as $result) {
            if ($result->isSalable()) {
                $salable = true;
                break;
            }
        }

        return $salable;
    }
}
