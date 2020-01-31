<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\IsProductSalableCondition;

use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventorySalesApi\Api\Data\ProductSalabilityErrorInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\ProductSalableResultInterface;
use Magento\InventorySalesApi\Api\Data\ProductSalableResultInterfaceFactory;
use Magento\InventorySalesApi\Api\IsProductSalableForRequestedQtyInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventorySales\Model\GetBackOrderQty;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException;

/**
 * BackOrderNotifyCustomerCondition Class
 *
 * Set backorder message for new orders if necessary.
 */
class BackOrderNotifyCustomerCondition implements IsProductSalableForRequestedQtyInterface
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
     * @var ProductSalabilityErrorInterfaceFactory
     */
    private $productSalabilityErrorFactory;

    /**
     * @var GetBackOrderQty
     */
    private $getBackOrderQty;

    /**
     * @param GetStockItemConfigurationInterface $getStockItemConfiguration
     * @param ProductSalableResultInterfaceFactory $productSalableResultFactory
     * @param ProductSalabilityErrorInterfaceFactory $productSalabilityErrorFactory
     * @param GetBackOrderQty $getBackOrderQty
     */
    public function __construct(
        GetStockItemConfigurationInterface $getStockItemConfiguration,
        ProductSalableResultInterfaceFactory $productSalableResultFactory,
        ProductSalabilityErrorInterfaceFactory $productSalabilityErrorFactory,
        GetBackOrderQty $getBackOrderQty
    ) {
        $this->getStockItemConfiguration = $getStockItemConfiguration;
        $this->productSalableResultFactory = $productSalableResultFactory;
        $this->productSalabilityErrorFactory = $productSalabilityErrorFactory;
        $this->getBackOrderQty = $getBackOrderQty;
    }

    /**
     * BackOrderNotifyCustomerCondition::execute
     *
     * Given a product SKU and a Stock ID, this function checks if an order for the requested quantity
     * would result in a back order situation. If it does then an appropriate message is returned to
     * be displayed to the user.
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
            && $stockItemConfiguration->getBackorders() === StockItemConfigurationInterface::BACKORDERS_YES_NOTIFY
        ) {
            $backOrderQty = $this->getBackOrderQty->execute($sku, $stockId, $requestedQty);
            if ($backOrderQty > 0) {
                $errors = [
                    $this->productSalabilityErrorFactory->create(
                        [
                            'code' => 'back_order-not-enough',
                            'message' => __(
                                'We don\'t have as many quantity as you requested, '
                                . 'but we\'ll back order the remaining %1.',
                                $backOrderQty * 1
                            )
                        ]
                    )
                ];
                return $this->productSalableResultFactory->create(['errors' => $errors]);
            }
        }

        return $this->productSalableResultFactory->create(['errors' => []]);
    }
}
