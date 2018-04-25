<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\IsProductSalableForSalesChannelCondition;

use Magento\InventorySales\Model\IsProductSalableForStockCondition\BackOrderCondition as BackOrderForStockCondition;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\IsProductSalableForSalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;

/**
 * @inheritdoc
 */
class BackOrderCondition implements IsProductSalableForSalesChannelInterface
{
    /**
     * @var BackOrderForStockCondition
     */
    private $backOrderCondition;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @param BackOrderForStockCondition $backOrderCondition
     * @param StockResolverInterface $stockResolver
     */
    public function __construct(
        BackOrderForStockCondition $backOrderCondition,
        StockResolverInterface $stockResolver
    ) {
        $this->backOrderCondition = $backOrderCondition;
        $this->stockResolver = $stockResolver;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, SalesChannelInterface $salesChannel): bool
    {
        $stockId = (int)$this->stockResolver->get($salesChannel->getType(), $salesChannel->getCode())->getStockId();
        return $this->backOrderCondition->execute($sku, $stockId);
    }
}
