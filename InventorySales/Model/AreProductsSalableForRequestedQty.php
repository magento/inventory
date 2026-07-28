<?php
/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model;

use Magento\InventoryApi\Model\CacheInterface;
use Magento\InventorySalesApi\Api\AreProductsSalableForRequestedQtyInterface;
use Magento\InventorySalesApi\Api\Data\IsProductSalableForRequestedQtyResultInterfaceFactory;
use Magento\InventorySalesApi\Api\IsProductSalableForRequestedQtyInterface;

/**
 * @inheritDoc
 */
class AreProductsSalableForRequestedQty implements AreProductsSalableForRequestedQtyInterface
{
    /**
     * @param IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQtyInterface
     * @param IsProductSalableForRequestedQtyResultInterfaceFactory $isProductSalableForRequestedQtyResultFactory
     * @param CacheInterface $cache
     */
    public function __construct(
        private readonly IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQtyInterface,
        private readonly IsProductSalableForRequestedQtyResultInterfaceFactory
        $isProductSalableForRequestedQtyResultFactory,
        private readonly CacheInterface $cache
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(
        array $skuRequests,
        int $stockId
    ): array {
        $results = [];
        $this->cache->warmup(
            array_map(
                static fn ($request) => $request->getSku(),
                $skuRequests
            ),
            $stockId
        );
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
