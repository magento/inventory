<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model;

use Magento\InventorySalesApi\Api\AreProductsSalableInterface;
use Magento\InventorySalesApi\Api\Data\AreProductsSalableResultInterface;
use Magento\InventorySalesApi\Api\Data\AreProductsSalableResultInterfaceFactory;
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
     * @var AreProductsSalableResultInterfaceFactory
     */
    private $areProductsSalableResultFactory;

    /**
     * @var IsProductSalableResultInterfaceFactory
     */
    private $isProductSalableResultFactory;

    /**
     * @param IsProductSalableInterface $isProductSalable
     * @param IsProductSalableResultInterfaceFactory $isProductSalableResultFactory
     * @param AreProductsSalableResultInterfaceFactory $areProductsSalableResultFactory
     */
    public function __construct(
        IsProductSalableInterface $isProductSalable,
        IsProductSalableResultInterfaceFactory $isProductSalableResultFactory,
        AreProductsSalableResultInterfaceFactory $areProductsSalableResultFactory
    ) {
        $this->isProductSalable = $isProductSalable;
        $this->isProductSalableResultFactory = $isProductSalableResultFactory;
        $this->areProductsSalableResultFactory = $areProductsSalableResultFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $skus, int $stockId): AreProductsSalableResultInterface
    {
        $skus = explode(',', $skus);
        $results = [];
        foreach ($skus as $sku) {
            $isSalable = $this->isProductSalable->execute($sku, $stockId);
            $results[] = $this->isProductSalableResultFactory->create(
                [
                    'sku' => $sku,
                    'isSalable' => $isSalable,
                ]
            );
        }

        return $this->areProductsSalableResultFactory->create(['results' => $results]);
    }
}
