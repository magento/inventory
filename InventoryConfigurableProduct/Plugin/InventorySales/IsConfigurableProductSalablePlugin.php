<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryConfigurableProduct\Plugin\InventorySales;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventorySales\Model\IsProductSalableCondition\IsProductSalableConditionChain;
use Magento\InventorySalesApi\Api\AreProductsSalableInterface;

/**
 * Check if configurable product is salable.
 * Moved from chain due to framework limitations (usage of 'AreProductsSalableInterface' provides cyclical dependency).
 */
class IsConfigurableProductSalablePlugin
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
     * @param ProductRepositoryInterface $productRepository
     * @param AreProductsSalableInterface $areProductsSalable
     * @param Configurable $configurableProductType
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        AreProductsSalableInterface $areProductsSalable,
        Configurable $configurableProductType
    ) {
        $this->productRepository = $productRepository;
        $this->areProductsSalable = $areProductsSalable;
        $this->configurableProductType = $configurableProductType;
    }

    /**
     * Is configurable product salable.
     *
     * @param IsProductSalableConditionChain $subject
     * @param bool $resultStatus
     * @param string $sku
     * @param int $stockId
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(
        IsProductSalableConditionChain $subject,
        bool $resultStatus,
        string $sku,
        int $stockId
    ): bool {
        if (!$resultStatus) {
            return $resultStatus;
        }

        try {
            $product = $this->productRepository->get($sku);
            if ($product->getTypeId() !== Configurable::TYPE_CODE) {
                return $resultStatus;
            }

            /** @noinspection PhpParamsInspection */
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
