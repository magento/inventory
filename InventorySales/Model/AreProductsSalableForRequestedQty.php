<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model;

use Magento\InventorySalesApi\Api\AreProductsSalableForRequestedQtyInterface;
use Magento\InventorySalesApi\Api\Data\IsProductSalableForRequestedQtyResultInterfaceFactory;
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
     * @var IsProductSalableForRequestedQtyResultInterfaceFactory
     */
    private $isProductSalableForRequestedQtyResultFactory;

    /**
     * @param IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQtyInterface
     * @param IsProductSalableForRequestedQtyResultInterfaceFactory $isProductSalableForRequestedQtyResultFactory
     */
    public function __construct(
        IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQtyInterface,
        IsProductSalableForRequestedQtyResultInterfaceFactory $isProductSalableForRequestedQtyResultFactory
    ) {
        $this->isProductSalableForRequestedQtyInterface = $isProductSalableForRequestedQtyInterface;
        $this->isProductSalableForRequestedQtyResultFactory = $isProductSalableForRequestedQtyResultFactory;
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
            $results[] = $this->isProductSalableForRequestedQtyResultFactory->create(
                [
                    'sku' => $request->getSku(),
                    'stockId' => $stockId,
                    'isSalable' => $result->isSalable(),
                    'errors' => $result->getErrors(),
                ]
            );
        }

        return $results;
    }
}
