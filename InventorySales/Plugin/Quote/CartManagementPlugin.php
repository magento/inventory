<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Plugin\Quote;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface;
use Magento\InventorySales\Model\ReservationExecutionInterface;
use Magento\InventorySales\Model\ResourceModel\AcquireInventoryLock;
use Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Acquire inventory locks during cart place order to prevent overselling race conditions
 */
class CartManagementPlugin
{
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var GetSkusByProductIdsInterface
     */
    private $getSkusByProductIds;

    /**
     * @var AcquireInventoryLock
     */
    private $acquireInventoryLock;

    /**
     * @var StockByWebsiteIdResolverInterface
     */
    private $stockByWebsiteIdResolver;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ReservationExecutionInterface
     */
    private $reservationExecution;

    /**
     * @param CartRepositoryInterface $cartRepository
     * @param GetSkusByProductIdsInterface $getSkusByProductIds
     * @param AcquireInventoryLock $acquireInventoryLock
     * @param StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver
     * @param StoreManagerInterface $storeManager
     * @param ReservationExecutionInterface $reservationExecution
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        GetSkusByProductIdsInterface $getSkusByProductIds,
        AcquireInventoryLock $acquireInventoryLock,
        StockByWebsiteIdResolverInterface $stockByWebsiteIdResolver,
        StoreManagerInterface $storeManager,
        ReservationExecutionInterface $reservationExecution
    ) {
        $this->cartRepository = $cartRepository;
        $this->getSkusByProductIds = $getSkusByProductIds;
        $this->acquireInventoryLock = $acquireInventoryLock;
        $this->stockByWebsiteIdResolver = $stockByWebsiteIdResolver;
        $this->storeManager = $storeManager;
        $this->reservationExecution = $reservationExecution;
    }

    /**
     * Acquire locks around place order for both guest and customer checkout
     *
     * @param CartManagementInterface $subject
     * @param callable $proceed
     * @param int $cartId
     * @param PaymentInterface|null $paymentMethod
     * @return int Order ID
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundPlaceOrder(
        CartManagementInterface $subject,
        callable $proceed,
        $cartId,
        ?PaymentInterface $paymentMethod = null
    ) {
        if (!$this->reservationExecution->isDeferred()) {
            return $proceed($cartId, $paymentMethod);
        }

        try {
            $quote = $this->cartRepository->getActive($cartId);
        } catch (NoSuchEntityException $exception) {
            // Async order processing can work with an inactive quote after checkout message submission.
            $quote = $this->cartRepository->get($cartId);
        }
        $websiteId = (int)$this->storeManager->getStore($quote->getStoreId())->getWebsiteId();
        $stockId = (int)$this->stockByWebsiteIdResolver->execute($websiteId)->getStockId();

        $productIds = [];
        foreach ($quote->getAllVisibleItems() as $item) {
            $productIds[] = $item->getProductId();
        }

        if (empty($productIds)) {
            return $proceed($cartId, $paymentMethod);
        }

        $skus = $this->getSkusByProductIds->execute($productIds);
        $locksAcquired = [];

        try {
            foreach ($skus as $sku) {
                if (!$this->acquireInventoryLock->execute((string) $sku, $stockId)) {
                    throw new LocalizedException(
                        __('Could not acquire inventory lock for SKU: %1. Please try again.', $sku)
                    );
                }
                $locksAcquired[$sku] = true;
            }

            return $proceed($cartId, $paymentMethod);
        } finally {
            foreach (array_keys($locksAcquired) as $sku) {
                $this->acquireInventoryLock->release((string) $sku, $stockId);
            }
        }
    }
}
