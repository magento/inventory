<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\IsProductSalableForSalesChannelCondition;

use Magento\InventorySales\Model\IsProductSalableForStockCondition\ManageStockCondition as ManageStockForStockCondition;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\IsProductSalableForSalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;

/**
 * @inheritdoc
 */
class ManageStockCondition implements IsProductSalableForSalesChannelInterface
{
    /**
     * @var ManageStockForStockCondition
     */
    private $manageStockCondition;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @param ManageStockForStockCondition $manageStockCondition
     * @param StockResolverInterface $stockResolver
     */
    public function __construct(
        ManageStockForStockCondition $manageStockCondition,
        StockResolverInterface $stockResolver
    ) {
        $this->manageStockCondition = $manageStockCondition;
        $this->stockResolver = $stockResolver;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, SalesChannelInterface $salesChannel): bool
    {
        $stockId = (int)$this->stockResolver->get($salesChannel->getType(), $salesChannel->getCode())->getStockId();
        return $this->manageStockCondition->execute($sku, $stockId);
    }
}
