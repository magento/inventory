<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\InventorySalesApi\Api\AreProductsSalableForRequestedQtyInterface;
use Magento\InventorySalesApi\Api\Data\ProductsSalableResultInterface;
use Magento\InventorySalesApi\Api\Data\ProductsSalableResultInterfaceFactory;
use Magento\InventorySalesApi\Api\IsProductSalableForRequestedQtyInterface;
use Psr\Log\LoggerInterface;

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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ProductsSalableResultInterfaceFactory
     */
    private $productsSalableResultFactory;

    /**
     * @param IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQtyInterface
     * @param ProductsSalableResultInterfaceFactory $productsSalableResultFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQtyInterface,
        ProductsSalableResultInterfaceFactory $productsSalableResultFactory,
        LoggerInterface $logger
    ) {
        $this->isProductSalableForRequestedQtyInterface = $isProductSalableForRequestedQtyInterface;
        $this->logger = $logger;
        $this->productsSalableResultFactory = $productsSalableResultFactory;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $skuRequests, int $stockId): ProductsSalableResultInterface
    {
        $results = [];
        foreach ($skuRequests as $sku => $quantity) {
            try {
                $results[] = $this->isProductSalableForRequestedQtyInterface->execute(
                    (string)$sku,
                    $stockId,
                    (float)$quantity
                );
            } catch (LocalizedException $e) {
                $this->logger->error($e->getLogMessage());
            }
        }

        return $this->productsSalableResultFactory->create(['results' => $results]);
    }
}
