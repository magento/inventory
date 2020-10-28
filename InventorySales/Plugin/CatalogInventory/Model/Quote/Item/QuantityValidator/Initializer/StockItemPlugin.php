<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Plugin\CatalogInventory\Model\Quote\Item\QuantityValidator\Initializer;

use Magento\CatalogInventory\Model\Quote\Item\QuantityValidator\Initializer\StockItem;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\FormatInterface;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\StoreManagerInterface;
use Magento\InventorySalesApi\Api\AreProductsSalableForRequestedQtyInterface;
use Magento\InventorySalesApi\Api\Data\IsProductSalableForRequestedQtyRequestInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;

/**
 * Plugin for initializes quantity stock item validator
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class StockItemPlugin
{
    /**
     * @var FormatInterface
     */
    private $format;

    /**
     * @var AreProductsSalableForRequestedQtyInterface
     */
    private $areProductsSalableForRequestedQty;

    /**
     * @var IsProductSalableForRequestedQtyRequestInterfaceFactory
     */
    private $isProductSalableForRequestedQtyRequestInterfaceFactory;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var GetStockItemConfigurationInterface
     */
    private $getStockItemConfiguration;

    /**
     * @var GetProductSalableQtyInterface
     */
    private $getProductSalableQty;

    /**
     * @param FormatInterface $format
     * @param AreProductsSalableForRequestedQtyInterface $areProductsSalableForRequestedQty
     * @param IsProductSalableForRequestedQtyRequestInterfaceFactory $isProductSalableForRequestedQtyRequestFactory
     * @param StockResolverInterface $stockResolver
     * @param StoreManagerInterface $storeManager
     * @param GetStockItemConfigurationInterface $getStockItemConfiguration
     * @param GetProductSalableQtyInterface|null $getProductSalableQty
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function __construct(
        FormatInterface $format,
        AreProductsSalableForRequestedQtyInterface $areProductsSalableForRequestedQty,
        IsProductSalableForRequestedQtyRequestInterfaceFactory $isProductSalableForRequestedQtyRequestFactory,
        StockResolverInterface $stockResolver,
        StoreManagerInterface $storeManager,
        GetStockItemConfigurationInterface $getStockItemConfiguration,
        GetProductSalableQtyInterface $getProductSalableQty
    ) {
        $this->format = $format;
        $this->areProductsSalableForRequestedQty = $areProductsSalableForRequestedQty;
        $this->isProductSalableForRequestedQtyRequestInterfaceFactory = $isProductSalableForRequestedQtyRequestFactory;
        $this->stockResolver = $stockResolver;
        $this->storeManager = $storeManager;
        $this->getStockItemConfiguration = $getStockItemConfiguration;
        $this->getProductSalableQty = $getProductSalableQty;
    }

    /**
     * Set backorder qty to Quote Item
     *
     * @param StockItem $subject
     * @param DataObject $result
     * @param StockItemInterface $stockItem
     * @param Item $quoteItem
     * @param int $qty
     *
     * @return DataObject
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterInitialize(
        StockItem $subject,
        DataObject $result,
        StockItemInterface $stockItem,
        Item $quoteItem,
        int $qty
    ): DataObject {
        $product = $quoteItem->getProduct();
        $productSku = $product->getSku();
        $websiteCode = $this->storeManager->getWebsite($product->getStore()->getWebsiteId())->getCode();
        $stock = $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $websiteCode);
        $stockId = $stock->getStockId();
        if ($this->isBackorderEnabled($productSku, $stockId)) {
            $qty = $this->getNumber($qty);
            $request = $this->isProductSalableForRequestedQtyRequestInterfaceFactory->create(
                [
                    'sku' => $productSku,
                    'qty' => $qty,
                ]
            );
            $productsSalableResult = $this->areProductsSalableForRequestedQty->execute([$request], (int)$stockId);
            $productsSalableResult = current($productsSalableResult);
            if ($productsSalableResult->isSalable()) {
                $backOrdersQty = $this->getBackOrdersQty(
                    $productSku,
                    (int)$stockId,
                    $qty
                );
                if ($backOrdersQty) {
                    $result->setItemBackorders($backOrdersQty);
                    $quoteItem->setBackorders($backOrdersQty);
                }
            }
        }

        return $result;
    }

    /**
     * Convert quantity to a valid float
     *
     * @param string|float|int|null $qty
     *
     * @return float|null
     */
    private function getNumber($qty)
    {
        if (!is_numeric($qty)) {
            return $this->format->getNumber($qty);
        }

        return $qty;
    }

    /**
     * Check if backorder enabled
     *
     * @param string $sku
     * @param int $stockId
     *
     * @return bool
     */
    private function isBackorderEnabled(string $sku, int $stockId): bool
    {
        $result = false;
        $stockItemConfiguration = $this->getStockItemConfiguration->execute($sku, $stockId);
        if ($stockItemConfiguration->isManageStock()
            && $stockItemConfiguration->getMinQty() >= 0
            && in_array(
                $stockItemConfiguration->getBackorders(),
                [
                    StockItemConfigurationInterface::BACKORDERS_YES_NOTIFY,
                    StockItemConfigurationInterface::BACKORDERS_YES_NONOTIFY,
                ],
            )
        ) {
            $result =  true;
        }

        return $result;
    }

    /**
     * Get back orders qty
     *
     * @param string $sku
     * @param int $stockId
     * @param float $requestedQty
     *
     * @return float
     */
    private function getBackOrdersQty(string $sku, int $stockId, float $requestedQty): float
    {
        $backOrderQty = 0;
        $salableQty = $this->getProductSalableQty->execute($sku, $stockId);
        if (($requestedQty - $salableQty) > 0 && $salableQty > 0) {
            $backOrderQty = $requestedQty - $salableQty;
        } elseif ($requestedQty > $salableQty) {
            $backOrderQty = $requestedQty;
        }

        return $backOrderQty;
    }
}
