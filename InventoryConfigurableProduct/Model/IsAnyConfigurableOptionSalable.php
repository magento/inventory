<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;

/**
 * Verify if configurable product has salable option service.
 */
class IsAnyConfigurableOptionSalable
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
     * @param ProductRepository $productRepository
     * @param AreProductsSalableInterface $areProductsSalable
     */
    public function __construct(
        Configurable $type,
        AreProductsSalableInterface $areProductsSalable
    ) {
        $this->type = $type;
        $this->areProductsSalable = $areProductsSalable;
    }

    /**
     * Verify if any configurable product options is salable for given stock.
     *
     * @param ProductInterface $product
     * @param int $stockId
     * @return bool
     */
    public function execute(ProductInterface $product, int $stockId): bool
    {
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

        return $status;
    }
}
