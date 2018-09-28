<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model\IsProductSalableForRequestedQtyCondition;

use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Framework\Math\Division as MathDivision;
use Magento\InventoryConfigurationApi\Api\GetInventoryConfigurationInterface;
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
     * @var GetInventoryConfigurationInterface
     */
    private $getInventoryConfiguration;

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
     * @param GetInventoryConfigurationInterface $getInventoryConfiguration
     * @param StockConfigurationInterface $configuration
     * @param GetReservationsQuantityInterface $getReservationsQuantity
     * @param GetStockItemDataInterface $getStockItemData
     * @param MathDivision $mathDivision
     * @param ProductSalabilityErrorInterfaceFactory $productSalabilityErrorFactory
     * @param ProductSalableResultInterfaceFactory $productSalableResultFactory
     */
    public function __construct(
        GetInventoryConfigurationInterface $getInventoryConfiguration,
        StockConfigurationInterface $configuration,
        GetReservationsQuantityInterface $getReservationsQuantity,
        GetStockItemDataInterface $getStockItemData,
        MathDivision $mathDivision,
        ProductSalabilityErrorInterfaceFactory $productSalabilityErrorFactory,
        ProductSalableResultInterfaceFactory $productSalableResultFactory
    ) {
        $this->getInventoryConfiguration = $getInventoryConfiguration;
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
        $minSaleQty = $this->getInventoryConfiguration->getMinSaleQty($sku, $stockId);
        if ($this->isMinSaleQuantityCheckFailed($minSaleQty, $requestedQty)) {
            return $this->createErrorResult(
                'is_correct_qty-min_sale_qty',
                __(
                    'The fewest you may purchase is %1',
                    $minSaleQty
                )
            );
        }

        $maxSaleQty = $this->getInventoryConfiguration->getMaxSaleQty($sku, $stockId);
        if ($this->isMaxSaleQuantityCheckFailed($maxSaleQty, $requestedQty)) {
            return $this->createErrorResult(
                'is_correct_qty-max_sale_qty',
                __('The requested qty exceeds the maximum qty allowed in shopping cart')
            );
        }

        $isQtyIncrementEnabled = $this->getInventoryConfiguration->isEnableQtyIncrements($sku, $stockId);
        if ($isQtyIncrementEnabled) {
            $qtyIncrements = $this->getInventoryConfiguration->getQtyIncrements($sku, $stockId);
            if ($this->isQuantityIncrementCheckFailed($qtyIncrements, $requestedQty)) {
                return $this->createErrorResult(
                    'is_correct_qty-qty_increment',
                    __(
                        'You can buy this product only in quantities of %1 at a time.',
                        $qtyIncrements
                    )
                );
            }
        }

        $isDecimal = $this->getInventoryConfiguration->isQtyDecimal($sku, $stockId);
        if ($this->isDecimalQtyCheckFailed($isDecimal, $requestedQty)) {
            return $this->createErrorResult(
                'is_correct_qty-is_qty_decimal',
                __('You cannot use decimal quantity for this product.')
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
     * Check if decimal quantity is valid
     *
     * @param bool $isDecimal
     * @param float $requestedQty
     * @return bool
     */
    private function isDecimalQtyCheckFailed(
        bool $isDecimal,
        float $requestedQty
    ): bool {
        return (!$isDecimal && (floor($requestedQty) !== $requestedQty));
    }

    /**
     * Check if min sale condition is satisfied
     *
     * @param float $minSaleQty
     * @param float $requestedQty
     * @return bool
     */
    private function isMinSaleQuantityCheckFailed(
        float $minSaleQty,
        float $requestedQty
    ) : bool {
        // Minimum Qty Allowed in Shopping Cart
        if ($minSaleQty && $requestedQty < $minSaleQty) {
            return true;
        }
        return false;
    }

    /**
     * @param float $maxSaleQty
     * @param float $requestedQty
     * @return bool
     */
    private function isMaxSaleQuantityCheckFailed(
        float $maxSaleQty,
        float $requestedQty
    ) : bool {
        // Maximum Qty Allowed in Shopping Cart
        if ($maxSaleQty && $requestedQty > $maxSaleQty) {
            return true;
        }
        return false;
    }

    /**
     * @param float $qtyIncrements
     * @param float $requestedQty
     * @return bool
     */
    private function isQuantityIncrementCheckFailed(
        float $qtyIncrements,
        float $requestedQty
    ) : bool {
        // Qty Increments
        if ($qtyIncrements !== (float)0 && $this->mathDivision->getExactDivision($requestedQty, $qtyIncrements) !== 0) {
            return true;
        }
        return false;
    }
}
