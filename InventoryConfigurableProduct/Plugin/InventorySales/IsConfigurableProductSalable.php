<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Plugin\InventorySales;

use Closure;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\InventorySales\Model\IsProductSalableCondition\IsProductSalableConditionChain;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;

/**
 * Check if configurable product is salable.
 * Moved from chain due to framework limitations (usage of 'AreProductsSalableInterface' provides cyclical dependency).
 */
class IsConfigurableProductSalable
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var AreProductsSalableInterface
     */
    private $areProductsSalable;

    /**
     * @var Configurable
     */
    private $configurableProductType;

    /**
     * @var GetProductTypesBySkusInterface
     */
    private $getProductTypesBySkus;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param AreProductsSalableInterface $areProductsSalable
     * @param Configurable $configurableProductType
     * @param GetProductTypesBySkusInterface $getProductTypesBySkus
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        AreProductsSalableInterface $areProductsSalable,
        Configurable $configurableProductType,
        GetProductTypesBySkusInterface $getProductTypesBySkus
    ) {
        $this->productRepository = $productRepository;
        $this->areProductsSalable = $areProductsSalable;
        $this->configurableProductType = $configurableProductType;
        $this->getProductTypesBySkus = $getProductTypesBySkus;
    }

    /**
     * Is configurable product salable.
     *
     * @param IsProductSalableConditionChain $subject
     * @param Closure $proceed
     * @param string $sku
     * @param int $stockId
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        IsProductSalableConditionChain $subject,
        Closure $proceed,
        string $sku,
        int $stockId
    ): bool {
        try {
            $types = $this->getProductTypesBySkus->execute([$sku]);

            $isProductSalable = $proceed($sku, $stockId);
            if (!isset($types[$sku]) || $types[$sku] !== Configurable::TYPE_CODE || !$isProductSalable) {
                return $isProductSalable;
            }

            $product = $this->productRepository->get($sku);

            $resultStatus = false;

            $options = $this->configurableProductType->getConfigurableOptions($product);
            $skus = [[]];
            foreach ($options as $attribute) {
                $skus[] = array_column($attribute, 'sku');
            }
            $skus = array_merge(...$skus);
            $results = $this->areProductsSalable->execute($skus, $stockId);
            foreach ($results as $result) {
                if ($result->isSalable()) {
                    $resultStatus = true;
                    break;
                }
            }

        } catch (NoSuchEntityException $e) {
            $resultStatus = false;
        }

        return $resultStatus;
    }
}
