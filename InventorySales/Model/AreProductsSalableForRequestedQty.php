<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\InventorySalesApi\Api\AreProductsSalableForRequestedQtyInterface;
use Magento\InventorySalesApi\Api\Data\AreProductsSalableResultInterface;
use Magento\InventorySalesApi\Api\Data\AreProductsSalableResultInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\IsProductSalableResultInterfaceFactory;
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
     * @var AreProductsSalableResultInterfaceFactory
     */
    private $areProdcutsSalableResultFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQtyInterface
     * @param IsProductSalableResultInterfaceFactory $isProductSalableResultFactory
     * @param AreProductsSalableResultInterfaceFactory $areProductsSalableResultFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQtyInterface,
        IsProductSalableResultInterfaceFactory $isProductSalableResultFactory,
        AreProductsSalableResultInterfaceFactory $areProductsSalableResultFactory,
        LoggerInterface $logger
    ) {
        $this->isProductSalableForRequestedQtyInterface = $isProductSalableForRequestedQtyInterface;
        $this->areProdcutsSalableResultFactory = $areProductsSalableResultFactory;
        $this->isProductSalableResultFactory = $isProductSalableResultFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function execute(
        array $skuRequests,
        int $stockId
    ): AreProductsSalableResultInterface {
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

        return $this->areProdcutsSalableResultFactory->create(['results' => $results]);
    }
}
