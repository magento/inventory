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
     * @var AreProductsSalableResultInterfaceFactory
     */
    private $areProductsSalableResultInterfaceFactory;

    /**
     * @param IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQtyInterface
     * @param AreProductsSalableResultInterfaceFactory $areProductsSalableResultInterfaceFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQtyInterface,
        AreProductsSalableResultInterfaceFactory $areProductsSalableResultInterfaceFactory,
        LoggerInterface $logger
    ) {
        $this->isProductSalableForRequestedQtyInterface = $isProductSalableForRequestedQtyInterface;
        $this->logger = $logger;
        $this->areProductsSalableResultInterfaceFactory = $areProductsSalableResultInterfaceFactory;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $skuRequests, int $stockId): AreProductsSalableResultInterface
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

        return $this->areProductsSalableResultInterfaceFactory->create(['results' => $results]);
    }
}
