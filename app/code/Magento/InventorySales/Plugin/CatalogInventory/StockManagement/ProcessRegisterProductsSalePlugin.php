<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Plugin\CatalogInventory\StockManagement;

use Magento\CatalogInventory\Api\RegisterProductSaleInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryCatalog\Model\GetSkusByProductIdsInterface;
use Magento\InventorySales\Model\SalesChannelByWebsiteIdProvider;
use Magento\InventorySales\Model\CheckItemsQuantity;

/**
 * Class provides around Plugin on RegisterProductSaleInterface::registerProductsSale
 */
class ProcessRegisterProductsSalePlugin
{
    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @var CheckItemsQuantity
     */
    private $checkItemsQuantity;

    /**
     * @var SalesChannelByWebsiteIdProvider
     */
    private $salesChannelByWebsiteIdProvider;

    /**
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param CheckItemsQuantity $checkItemsQuantity
     * @param SalesChannelByWebsiteIdProvider $salesChannelByWebsiteIdProvider
     */
    public function __construct(
        GetSkusByProductIdsInterface $getSkusByProductIds,
        CheckItemsQuantity $checkItemsQuantity,
        SalesChannelByWebsiteIdProvider $salesChannelByWebsiteIdProvider
    ) {
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->checkItemsQuantity = $checkItemsQuantity;
        $this->salesChannelByWebsiteIdProvider = $salesChannelByWebsiteIdProvider;
    }

    /**
     * @param RegisterProductSaleInterface $subject
     * @param callable $proceed
     * @param float[] $items
     * @param int|null $websiteId
     *
     * @return array
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundRegisterProductsSale(
        RegisterProductSaleInterface $subject,
        callable $proceed,
        $items,
        $websiteId = null
    ) {
        if (empty($items)) {
            return [];
        }
        if (null === $websiteId) {
            throw new LocalizedException(__('$websiteId parameter is required'));
        }
        $productSkus = $this->getSkusByProductIds->execute(array_keys($items));
        $itemsBySku = [];
        foreach ($productSkus as $productId => $sku) {
            $itemsBySku[$sku] = $items[$productId];
        }
        $salesChannel = $this->salesChannelByWebsiteIdProvider->execute((int)$websiteId);
        $this->checkItemsQuantity->execute($itemsBySku, $salesChannel);
        return [];
    }
}
