<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\IsProductSalableCondition;

use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventorySales\Model\GetBackOrderQty;
use Magento\InventorySalesApi\Api\Data\ProductSalableResultInterface;
use Magento\InventorySalesApi\Api\Data\ProductSalableResultInterfaceFactory;
use Magento\InventorySalesApi\Api\IsProductSalableForRequestedQtyInterface;
use Magento\InventorySalesApi\Api\Data\ProductSalableResultExtensionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\InputException;
use Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException;

/**
 * BackOrderQuantityCondition Class
 *
 * Determine if back orders are required for new order
 */
class BackOrderQuantityCondition implements IsProductSalableForRequestedQtyInterface
{
    /**
     * @var GetStockItemConfigurationInterface
     */
    private $getStockItemConfiguration;

    /**
     * @var ProductSalableResultInterfaceFactory
     */
    private $productSalableResultFactory;

    /**
     * @var GetBackOrderQty
     */
    private $getBackOrderQty;

    /**
     * @param GetStockItemConfigurationInterface $getStockItemConfiguration
     * @param ProductSalableResultInterfaceFactory $productSalableResultFactory
     * @param GetBackOrderQty $getBackOrderQty
     */
    public function __construct(
        GetStockItemConfigurationInterface $getStockItemConfiguration,
        ProductSalableResultInterfaceFactory $productSalableResultFactory,
        GetBackOrderQty $getBackOrderQty
    ) {
        $this->getStockItemConfiguration = $getStockItemConfiguration;
        $this->productSalableResultFactory = $productSalableResultFactory;
        $this->getBackOrderQty = $getBackOrderQty;
    }

    /**
     * BackOrderQuantityCondition::execute
     *
     * Given a product SKU and a Stock ID, this provides the necessary functionality to
     * determine how many items would need to be back ordered, given the requested quantity.
     *
     * @param string $sku
     * @param int $stockId
     * @param float $requestedQty
     * @return ProductSalableResultInterface
     * @throws InputException
     * @throws LocalizedException
     * @throws SkuIsNotAssignedToStockException
     */
    public function execute(string $sku, int $stockId, float $requestedQty): ProductSalableResultInterface
    {
        $stockItemConfiguration = $this->getStockItemConfiguration->execute($sku, $stockId);

        if ($stockItemConfiguration->isManageStock()
        && ($stockItemConfiguration->getBackorders() === StockItemConfigurationInterface::BACKORDERS_YES_NOTIFY
        || $stockItemConfiguration->getBackorders() === StockItemConfigurationInterface::BACKORDERS_YES_NONOTIFY)) {
            $backOrderQty = $this->getBackOrderQty->execute($sku, $stockId, $requestedQty);
            if ($backOrderQty > 0) {
                /**
                 * @var $result ProductSalableResultInterface
                 * @var $extensionAttributes ProductSalableResultExtensionInterface
                 */
                $result = $this->productSalableResultFactory->create(['errors' => []]);
                $extensionAttributes = $result->getExtensionAttributes();
                $extensionAttributes->setBackOrderQty($backOrderQty);
                $result->setExtensionAttributes($extensionAttributes);
                return $result;
            }
        }

        return $this->productSalableResultFactory->create(['errors' => []]);
    }
}
