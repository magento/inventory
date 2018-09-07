<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\IsProductSalableForRequestedQtyCondition;

use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Framework\Math\Division as MathDivision;
use Magento\InventoryConfigurationApi\Api\GetStockConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryReservationsApi\Model\GetReservationsQuantityInterface;
use Magento\InventorySalesApi\Model\GetStockItemDataInterface;
use Magento\InventorySalesApi\Api\IsProductSalableForRequestedQtyInterface;
use Magento\InventorySalesApi\Api\Data\ProductSalableResultInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\ProductSalableResultInterface;
use Magento\InventorySalesApi\Api\Data\ProductSalabilityErrorInterfaceFactory;
use Magento\Framework\Phrase;

/**
 * @inheritdoc
 */
class IsCorrectQtyCondition implements IsProductSalableForRequestedQtyInterface
{
    /**
     * @var GetStockConfigurationInterface
     */
    private $getStockItemConfiguration;

    /**
     * @var GetReservationsQuantityInterface
     */
    private $getReservationsQuantity;

    /**
     * @var GetStockItemDataInterface
     */
    private $getStockItemData;

    /**
     * @var StockConfigurationInterface
     */
    private $configuration;

    /**
     * @var MathDivision
     */
    private $mathDivision;

    /**
     * @var ProductSalabilityErrorInterfaceFactory
     */
    private $productSalabilityErrorFactory;

    /**
     * @var ProductSalableResultInterfaceFactory
     */
    private $productSalableResultFactory;

    /**
     * @param GetStockConfigurationInterface $getStockItemConfiguration
     * @param StockConfigurationInterface $configuration
     * @param GetReservationsQuantityInterface $getReservationsQuantity
     * @param GetStockItemDataInterface $getStockItemData
     * @param MathDivision $mathDivision
     * @param ProductSalabilityErrorInterfaceFactory $productSalabilityErrorFactory
     * @param ProductSalableResultInterfaceFactory $productSalableResultFactory
     */
    public function __construct(
        GetStockConfigurationInterface $getStockItemConfiguration,
        StockConfigurationInterface $configuration,
        GetReservationsQuantityInterface $getReservationsQuantity,
        GetStockItemDataInterface $getStockItemData,
        MathDivision $mathDivision,
        ProductSalabilityErrorInterfaceFactory $productSalabilityErrorFactory,
        ProductSalableResultInterfaceFactory $productSalableResultFactory
    ) {
        $this->getStockItemConfiguration = $getStockItemConfiguration;
        $this->configuration = $configuration;
        $this->getStockItemData = $getStockItemData;
        $this->getReservationsQuantity = $getReservationsQuantity;
        $this->mathDivision = $mathDivision;
        $this->productSalabilityErrorFactory = $productSalabilityErrorFactory;
        $this->productSalableResultFactory = $productSalableResultFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute(string $sku, int $stockId, float $requestedQty): ProductSalableResultInterface
    {
        $stockItemConfiguration = $this->getStockItemConfiguration->forStockItem($sku, $stockId);
        $stockConfiguration = $this->getStockItemConfiguration->forStock($stockId);
        $globalConfiguration = $this->getStockItemConfiguration->forGlobal();

        $isMinSaleQuantityCheckFailed = $this->isMinSaleQuantityCheckFailed(
            $stockItemConfiguration,
            $stockConfiguration,
            $globalConfiguration,
            $requestedQty
        );
        if ($isMinSaleQuantityCheckFailed) {
            return $this->createErrorResult(
                'is_correct_qty-min_sale_qty',
                __(
                    'The fewest you may purchase is %1',
                    $stockItemConfiguration->getMinSaleQty()
                )
            );
        }

        $isMaxSaleQuantityCheckFailed = $this->isMaxSaleQuantityCheckFailed(
            $stockItemConfiguration,
            $stockConfiguration,
            $globalConfiguration,
            $requestedQty
        );
        if ($isMaxSaleQuantityCheckFailed) {
            return $this->createErrorResult(
                'is_correct_qty-max_sale_qty',
                __('The requested qty exceeds the maximum qty allowed in shopping cart')
            );
        }

        $isQuantityIncrementCheckFailed = $this->isQuantityIncrementCheckFailed(
            $stockItemConfiguration,
            $stockConfiguration,
            $globalConfiguration,
            $requestedQty
        );
        if ($isQuantityIncrementCheckFailed) {
            return $this->createErrorResult(
                'is_correct_qty-qty_increment',
                __(
                    'You can buy this product only in quantities of %1 at a time.',
                    $stockItemConfiguration->getQtyIncrements()
                )
            );
        }

        return $this->productSalableResultFactory->create(['errors' => []]);
    }

    /**
     * Create Error Result Object
     *
     * @param string $code
     * @param Phrase $message
     * @return ProductSalableResultInterface
     */
    private function createErrorResult(string $code, Phrase $message) : ProductSalableResultInterface
    {
        $errors = [
            $this->productSalabilityErrorFactory->create([
                'code' => $code,
                'message' => $message
            ])
        ];
        return $this->productSalableResultFactory->create(['errors' => $errors]);
    }

    /**
     * @param StockItemConfigurationInterface $stockItemConfiguration
     * @param StockItemConfigurationInterface $stockConfiguration
     * @param StockItemConfigurationInterface $globalConfiguration
     * @param float $requestedQty
     * @return bool
     */
    private function isMinSaleQuantityCheckFailed(
        StockItemConfigurationInterface $stockItemConfiguration,
        StockItemConfigurationInterface $stockConfiguration,
        StockItemConfigurationInterface $globalConfiguration,
        float $requestedQty
    ) : bool {
        // Minimum Qty Allowed in Shopping Cart
        $defaultValue = $stockConfiguration->getMinSaleQty() !== null ?: $globalConfiguration->getMinSaleQty();
        $minSaleQty = $stockItemConfiguration->getMinSaleQty() !== null ?: $defaultValue;
        if ($minSaleQty && $requestedQty < $minSaleQty) {
            return true;
        }
        return false;
    }

    /**
     * @param StockItemConfigurationInterface $stockItemConfiguration
     * @param StockItemConfigurationInterface $stockConfiguration
     * @param StockItemConfigurationInterface $globalConfiguration
     * @param float $requestedQty
     * @return bool
     */
    private function isMaxSaleQuantityCheckFailed(
        StockItemConfigurationInterface $stockItemConfiguration,
        StockItemConfigurationInterface $stockConfiguration,
        StockItemConfigurationInterface $globalConfiguration,
        float $requestedQty
    ) : bool {
        // Maximum Qty Allowed in Shopping Cart
        $defaultValue = $stockConfiguration->getMaxSaleQty() !== null ?: $globalConfiguration->getMaxSaleQty();
        $maxSaleQty = $stockItemConfiguration->getMaxSaleQty() !== null ?: $defaultValue;
        if ($maxSaleQty && $requestedQty > $maxSaleQty) {
            return true;
        }
        return false;
    }

    /**
     * @param StockItemConfigurationInterface $stockItemConfiguration
     * @param StockItemConfigurationInterface $stockConfiguration
     * @param StockItemConfigurationInterface $globalConfiguration
     * @param float $requestedQty
     * @return bool
     */
    private function isQuantityIncrementCheckFailed(
        StockItemConfigurationInterface $stockItemConfiguration,
        StockItemConfigurationInterface $stockConfiguration,
        StockItemConfigurationInterface $globalConfiguration,
        float $requestedQty
    ) : bool {
        // Qty Increments
        $defaultValue = $stockConfiguration->getQtyIncrements() !== null ?: $globalConfiguration->getQtyIncrements();
        $qtyIncrements = $stockItemConfiguration->getQtyIncrements() !== null ?: $defaultValue;
        if ($qtyIncrements !== (float)0 && $this->mathDivision->getExactDivision($requestedQty, $qtyIncrements) !== 0) {
            return true;
        }
        return false;
    }
}
