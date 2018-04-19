<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Plugin\StockState;

use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\Framework\DataObject;
use Magento\Framework\DataObject\Factory as ObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\FormatInterface;
use Magento\InventoryCatalog\Model\GetSkusByProductIdsInterface;
use Magento\InventorySales\Model\IsProductSalableForRequestedQtyCondition\ProductSalabilityError;
use Magento\InventorySales\Model\SalesChannelByWebsiteCodeProvider;
use Magento\InventorySalesApi\Api\IsProductSalableForRequestedQtyInterface;
use Magento\Store\Model\StoreManagerInterface;

class CheckQuoteItemQtyPlugin
{
    /**
     * @var ObjectFactory
     */
    private $objectFactory;

    /**
     * @var FormatInterface
     */
    private $format;

    /**
     * @var IsProductSalableForRequestedQtyInterface
     */
    private $isProductSalableForRequestedQty;

    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var SalesChannelByWebsiteCodeProvider
     */
    private $salesChannelByWebsiteCodeProvider;

    /**
     * @param ObjectFactory $objectFactory
     * @param FormatInterface $format
     * @param IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQty
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param StoreManagerInterface $storeManager
     * @param SalesChannelByWebsiteCodeProvider $salesChannelByWebsiteCodeProvider
     */
    public function __construct(
        ObjectFactory $objectFactory,
        FormatInterface $format,
        IsProductSalableForRequestedQtyInterface $isProductSalableForRequestedQty,
        GetSkusByProductIdsInterface $getSkusByProductIds,
        StoreManagerInterface $storeManager,
        SalesChannelByWebsiteCodeProvider $salesChannelByWebsiteCodeProvider
    ) {
        $this->objectFactory = $objectFactory;
        $this->format = $format;
        $this->isProductSalableForRequestedQty = $isProductSalableForRequestedQty;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->storeManager = $storeManager;
        $this->salesChannelByWebsiteCodeProvider = $salesChannelByWebsiteCodeProvider;
    }

    /**
     * @param StockStateInterface $subject
     * @param \Closure $proceed
     * @param int $productId
     * @param float $itemQty
     * @param float $qtyToCheck
     * @param float $origQty
     * @param int|null $scopeId
     *
     * @return DataObject
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundCheckQuoteItemQty(
        StockStateInterface $subject,
        \Closure $proceed,
        $productId,
        $itemQty,
        $qtyToCheck,
        $origQty,
        $scopeId
    ) {
        $result = $this->objectFactory->create();
        $result->setHasError(false);

        $qty = $this->getNumber($itemQty);

        $skus = $this->getSkusByProductIds->execute([$productId]);
        $productSku = $skus[$productId];

        $websiteCode = $this->storeManager->getWebsite()->getCode();
        $salesChannel = $this->salesChannelByWebsiteCodeProvider->execute($websiteCode);
        $isSalableResult = $this->isProductSalableForRequestedQty->execute($productSku, $salesChannel, $qty);

        if ($isSalableResult->isSalable() === false) {
            /** @var ProductSalabilityError $error */
            foreach ($isSalableResult->getErrors() as $error) {
                $result->setHasError(true)->setMessage($error->getMessage())->setQuoteMessage($error->getMessage())
                       ->setQuoteMessageIndex('qty');
            }
        }

        return $result;
    }

    /**
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
}
