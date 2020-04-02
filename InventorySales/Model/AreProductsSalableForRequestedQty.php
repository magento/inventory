<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model;

use Magento\InventorySalesApi\Api\AreProductsSalableForRequestedQtyInterface;
use Magento\InventorySalesApi\Api\Data\IsProductSalableResultInterfaceFactory;
use Magento\InventorySalesApi\Api\IsProductSalableForRequestedQtyInterface;

/**
 * @inheritDoc
 */
class AreProductsSalableForRequestedQty implements AreProductsSalableForRequestedQtyInterface
{
    /**
     * @var IsProductSalableForRequestedQtyInterface
     */
    private $isProductSalableForRequestedQtyInterface;

    /**
     * @var IsProductSalableResultInterfaceFactory
     */
    private $isProductSalableResultFactory;

    /**
     * @param IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQtyInterface
     * @param IsProductSalableResultInterfaceFactory $isProductSalableResultFactory
     */
    public function __construct(
        IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQtyInterface,
        IsProductSalableResultInterfaceFactory $isProductSalableResultFactory
    ) {
        $this->isProductSalableForRequestedQtyInterface = $isProductSalableForRequestedQtyInterface;
        $this->isProductSalableResultFactory = $isProductSalableResultFactory;
    }

    /**
     * @inheritDoc
     */
    public function execute(
        array $skuRequests,
        int $stockId
    ): array {
        $results = [];
        foreach ($skuRequests as $request) {
            $result = $this->isProductSalableForRequestedQtyInterface->execute(
                $request->getSku(),
                $stockId,
                $request->getQty()
            );
            $results[] = $this->isProductSalableResultFactory->create(
                [
                    'sku' => $request->getSku(),
                    'isSalable' => $result->isSalable(),
                    'errors' => $result->getErrors(),
                ]
            );
        }

        return $results;
    }
}
