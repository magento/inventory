<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryIndexer\Plugin\InventorySales;

use Magento\InventoryIndexer\Model\AreMultipleProductsSalable;
use Magento\InventorySales\Model\AreProductsSalable as AreProductsSalableInventorySales;
use Magento\InventorySalesApi\Api\Data\IsProductSalableResultInterfaceFactory;

/**
 * Define if products are salable in a bulk operation.
 */
class AreProductsSalable
{
    /**
     * @var IsProductSalableResultInterfaceFactory
     */
    private IsProductSalableResultInterfaceFactory $isProductSalableResultFactory;

    /**
     * @var AreMultipleProductsSalable
     */
    private AreMultipleProductsSalable $areProductsSalable;

    /**
     * @param IsProductSalableResultInterfaceFactory $isProductSalableResultFactory
     * @param AreMultipleProductsSalable $areProductSalable
     */
    public function __construct(
        IsProductSalableResultInterfaceFactory $isProductSalableResultFactory,
        AreMultipleProductsSalable $areProductSalable
    ) {
        $this->isProductSalableResultFactory = $isProductSalableResultFactory;
        $this->areProductsSalable = $areProductSalable;
    }

    /**
     * Define if products are salable in a bulk operation instead of iterating through each sku.
     *
     * @param AreProductsSalableInventorySales $subject
     * @param callable $proceed
     * @param array|string[] $skus
     * @param int $stockId
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        AreProductsSalableInventorySales $subject,
        callable $proceed,
        array $skus,
        int $stockId
    ): array {
        $results = [];

        $salabilityResults = $this->areProductsSalable->execute($skus, $stockId);

        foreach ($salabilityResults as $sku => $isSalable) {
            $results[] = $this->isProductSalableResultFactory->create(
                [
                    'sku' => $sku,
                    'stockId' => $stockId,
                    'isSalable' => $isSalable,
                ]
            );
        }

        return $results;
    }
}
