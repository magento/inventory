<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Model\IsProductSalableCondition;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;

class IsConfigurableProductSalable implements IsProductSalableInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    private $repository;

    /**
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @var Configurable
     */
    private $configurable;

    /**
     * @param ProductRepositoryInterface $repository
     * @param AreProductsSalableInterface $areProductsSalable
     * @param Configurable $configurable
     */
    public function __construct(
        ProductRepositoryInterface $repository,
        AreProductsSalableInterface $areProductsSalable,
        Configurable $configurable
    ) {
        $this->repository = $repository;
        $this->areProductsSalable = $areProductsSalable;
        $this->configurable = $configurable;
    }

    /**
     * Is configurable product salable.
     *
     * @param string $sku
     * @param int $stockId
     *
     * @return bool
     */
    public function execute(string $sku, int $stockId): bool
    {
        try {
            $status = false;
            $product = $this->repository->get($sku);
            if ($product->getTypeId() === Configurable::TYPE_CODE) {
                /** @noinspection PhpParamsInspection */
                $options = $this->configurable->getConfigurableOptions($product);
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
        } catch (NoSuchEntityException $e) {
            $status = false;
        }

        return $status;
    }
}
