<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\InventorySalesApi\Api\AreProductsSalableForRequestedQtyInterface;
use Magento\InventorySalesApi\Api\Data\IsProductSalableResultInterfaceFactory;
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
     * @var IsProductSalableResultInterfaceFactory
     */
    private $isProductSalableResultFactory;

    /**
     * @var ProductsSalableResultInterfaceFactory
     */
    private $productsSalableResultFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQtyInterface
     * @param IsProductSalableResultInterfaceFactory $isProductSalableResultFactory
     * @param ProductsSalableResultInterfaceFactory $productsSalableResultFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQtyInterface,
        IsProductSalableResultInterfaceFactory $isProductSalableResultFactory,
        ProductsSalableResultInterfaceFactory $productsSalableResultFactory,
        LoggerInterface $logger
    ) {
        $this->isProductSalableForRequestedQtyInterface = $isProductSalableForRequestedQtyInterface;
        $this->productsSalableResultFactory = $productsSalableResultFactory;
        $this->isProductSalableResultFactory = $isProductSalableResultFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function execute(
        array $skuRequests,
        int $stockId
    ): ProductsSalableResultInterface {
        $results = [];
        foreach ($skuRequests as $request) {
            try {
                $result = $this->isProductSalableForRequestedQtyInterface->execute(
                    $request->getSku(),
                    $stockId,
                    $request->getQty()
                );
                $result = $this->isProductSalableResultFactory->create(
                    [
                        'sku' => $request->getSku(),
                        'isSalable' => $result->isSalable(),
                        'errors' => $result->getErrors(),
                    ]
                );
                $results[] = $result;
            } catch (LocalizedException $e) {
                $this->logger->error($e->getLogMessage());
            }
        }

        return $this->productsSalableResultFactory->create(['results' => $results]);
    }
}
