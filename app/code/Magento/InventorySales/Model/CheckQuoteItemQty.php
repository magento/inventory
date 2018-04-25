<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Model;

use Magento\Framework\DataObject;
use Magento\Framework\DataObject\Factory as ObjectFactory;
use Magento\InventoryConfigurationApi\Api\Data\StockItemConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventorySales\Model\IsProductSalableForRequestedQtyCondition\ProductSalabilityError;
use Magento\InventorySalesApi\Api\Data\ProductSalabilityErrorInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\IsProductSalableForRequestedQtyInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

class CheckQuoteItemQty
{
    /**
     * @var GetProductBackorderWarningsInterface
     */
    private $getProductBackorderWarnings;

    /**
     * @var GetStockItemConfigurationInterface
     */
    private $getStockItemConfiguration;

    /**
     * @var ObjectFactory
     */
    private $objectFactory;

    /**
     * @var IsProductSalableForRequestedQtyInterface
     */
    private $isProductSalableForRequestedQty;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param ObjectFactory $objectFactory
     * @param IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQty
     * @param StockResolverInterface $stockResolver
     * @param StoreManagerInterface $storeManager
     * @param GetStockItemConfigurationInterface $getStockItemConfiguration
     * @param GetProductBackorderWarningsInterface $getProductBackorderWarnings
     */
    public function __construct(
        ObjectFactory $objectFactory,
        IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQty,
        StockResolverInterface $stockResolver,
        StoreManagerInterface $storeManager,
        GetStockItemConfigurationInterface $getStockItemConfiguration,
        GetProductBackorderWarningsInterface $getProductBackorderWarnings
    ) {
        $this->objectFactory = $objectFactory;
        $this->isProductSalableForRequestedQty = $isProductSalableForRequestedQty;
        $this->stockResolver = $stockResolver;
        $this->storeManager = $storeManager;
        $this->getStockItemConfiguration = $getStockItemConfiguration;
        $this->getProductBackorderWarnings = $getProductBackorderWarnings;
    }

    /**
     * @param string $productSku
     * @param float $qty
     *
     * @return DataObject
     */
    public function execute(string $productSku, float $qty): DataObject
    {
        $result = $this->objectFactory->create();
        $result->setHasError(false);

        $websiteCode = $this->storeManager->getWebsite()->getCode();
        $stock = $this->stockResolver->get(SalesChannelInterface::TYPE_WEBSITE, $websiteCode);
        $stockId = $stock->getStockId();

        $isSalableResult = $this->isProductSalableForRequestedQty->execute($productSku, (int)$stockId, $qty);

        if ($isSalableResult->isSalable() === false) {
            /** @var ProductSalabilityError $error */
            foreach ($isSalableResult->getErrors() as $error) {
                $result->setHasError(true)->setMessage($error->getMessage())->setQuoteMessage($error->getMessage())
                    ->setQuoteMessageIndex('qty');
            }
        } else {
            $stockItemConfiguration = $this->getStockItemConfiguration->execute($productSku, (int)$stockId);
            if ($stockItemConfiguration->getBackorders() === StockItemConfigurationInterface::BACKORDERS_YES_NOTIFY) {
                $warnings = $this->getProductBackorderWarnings->execute($productSku, (int)$stockId, $qty);
                if (count($warnings) > 0) {
                    /** @var ProductSalabilityErrorInterface $warning */
                    foreach ($warnings as $warning) {
                        $result->setMessage($warning->getMessage());
                    }
                }
            }
        }

        return $result;
    }
}
