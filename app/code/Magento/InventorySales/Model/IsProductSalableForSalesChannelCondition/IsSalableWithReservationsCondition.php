<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\IsProductSalableForSalesChannelCondition;

use Magento\InventorySales\Model\IsProductSalableForStockCondition\IsSalableWithReservationsCondition
    as IsSalableWithReservationsForStockCondition;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\IsProductSalableForSalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;

/**
 * @inheritdoc
 */
class IsSalableWithReservationsCondition implements IsProductSalableForSalesChannelInterface
{
    /**
     * @var IsSalableWithReservationsForStockCondition
     */
    private $isSalableWithReservationsCondition;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @param IsSalableWithReservationsForStockCondition $isSalableWithReservationsCondition
     * @param StockResolverInterface $stockResolver
     */
    public function __construct(
        IsSalableWithReservationsForStockCondition $isSalableWithReservationsCondition,
        StockResolverInterface $stockResolver
    ) {
        $this->isSalableWithReservationsCondition = $isSalableWithReservationsCondition;
        $this->stockResolver = $stockResolver;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, SalesChannelInterface $salesChannel): bool
    {
        $stockId = (int)$this->stockResolver->get($salesChannel->getType(), $salesChannel->getCode())->getStockId();
        return $this->isSalableWithReservationsCondition->execute($sku, $stockId);
    }
}
