<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model;

use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Api\Data\IsProductSalableResultInterfaceFactory;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;

/**
 * @inheritdoc
 */
class AreProductsSalable implements AreProductsSalableInterface
{
    /**
     * @var IsProductSalableInterface
     */
    private $isProductSalable;

    /**
     * @var IsProductSalableResultInterfaceFactory
     */
    private $isProductSalableResultFactory;

    /**
     * @param IsProductSalableInterface $isProductSalable
     * @param IsProductSalableResultInterfaceFactory $isProductSalableResultFactory
     */
    public function __construct(
        IsProductSalableInterface $isProductSalable,
        IsProductSalableResultInterfaceFactory $isProductSalableResultFactory
    ) {
        $this->isProductSalable = $isProductSalable;
        $this->isProductSalableResultFactory = $isProductSalableResultFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute(array $skus, int $stockId): array
    {
        $results = [];
        foreach ($skus as $sku) {
            $isSalable = $this->isProductSalable->execute($sku, $stockId);
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
