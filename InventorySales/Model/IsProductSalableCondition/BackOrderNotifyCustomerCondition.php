<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\IsProductSalableCondition;

use Magento\Framework\App\ObjectManager;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventorySalesApi\Api\Data\ProductSalabilityErrorInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\ProductSalableResultInterface;
use Magento\InventorySalesApi\Api\Data\ProductSalableResultInterfaceFactory;
use Magento\InventorySalesApi\Api\IsProductSalableForRequestedQtyInterface;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventorySales\Model\GetBackorderQty;

/**
 * Get back order notify for customer condition
 *
 * @inheritdoc
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
     * @var GetBackorderQty
     */
    private $getBackorderQty;

    /**
     * @param GetStockItemConfigurationInterface $getStockItemConfiguration
     * @param GetStockItemDataInterface $getStockItemData @deprecated
     * @param ProductSalableResultInterfaceFactory $productSalableResultFactory
     * @param ProductSalabilityErrorInterfaceFactory $productSalabilityErrorFactory
     * @param GetProductSalableQtyInterface|null $getProductSalableQty @deprecated
     * @param GetBackorderQty|null $getBackorderQty
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        GetStockItemConfigurationInterface $getStockItemConfiguration,
        GetStockItemDataInterface $getStockItemData,
        ProductSalableResultInterfaceFactory $productSalableResultFactory,
        ProductSalabilityErrorInterfaceFactory $productSalabilityErrorFactory,
        ?GetProductSalableQtyInterface $getProductSalableQty = null,
        ?GetBackorderQty $getBackorderQty = null
    ) {
        $this->getStockItemConfiguration = $getStockItemConfiguration;
        $this->productSalableResultFactory = $productSalableResultFactory;
        $this->productSalabilityErrorFactory = $productSalabilityErrorFactory;
        $this->getBackorderQty = $getBackorderQty
            ?? ObjectManager::getInstance()->get(GetBackorderQty::class);
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId, float $requestedQty): ProductSalableResultInterface
    {
        $stockItemConfiguration = $this->getStockItemConfiguration->execute($sku, $stockId);

        if ($stockItemConfiguration->isManageStock()
            && $stockItemConfiguration->getBackorders() === StockItemConfigurationInterface::BACKORDERS_YES_NOTIFY
        ) {
            $backorderQty = $this->getBackorderQty->execute($sku, $stockId, $requestedQty);

            if ($backorderQty > 0) {
                $errors = [
                    $this->productSalabilityErrorFactory->create([
                        'code' => 'back_order-not-enough',
                        'message' => __(
                            'We don\'t have as many quantity as you requested, '
                            . 'but we\'ll back order the remaining %1.',
                            $backorderQty * 1
                        )])
                ];
                return $this->productSalableResultFactory->create(['errors' => $errors]);
            }
        }

        return $this->productSalableResultFactory->create(['errors' => []]);
    }
}
