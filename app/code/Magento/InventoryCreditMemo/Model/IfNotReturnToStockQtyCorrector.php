<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryCreditMemo\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryCatalog\Model\GetProductTypesBySkusInterface;
use Magento\InventoryConfiguration\Model\IsSourceItemsAllowedForProductTypeInterface;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventoryReservationsApi\Api\AppendReservationsInterface;
use Magento\InventoryReservationsApi\Api\ReservationBuilderInterface;
use Magento\InventorySales\Model\StockByWebsiteIdResolver;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Correct qty during credit memo if not return to stock.
 * Create compensational reservation and decrease stock.
 */
class IfNotReturnToStockQtyCorrector
{
    /**
     * @var ReservationBuilderInterface
     */
    private $reservationBuilder;

    /**
     * @var StockByWebsiteIdResolver
     */
    private $stockByWebsiteIdResolver;

    /**
     * @var AppendReservationsInterface
     */
    private $appendReservations;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var IsSourceItemsAllowedForProductTypeInterface
     */
    private $isSourceItemsAllowedForProductType;

    /**
     * @var GetProductTypesBySkusInterface
     */
    private $getProductTypesBySkus;

    /**
     * @var SourceItemsSaveInterface
     */
    private $sourceItemsSave;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var GetStockItemConfigurationInterface
     */
    private $getStockItemConfiguration;

    /**
     * @var GetSourceItem
     */
    private $getSourceItem;

    /**
     * @param ReservationBuilderInterface $reservationBuilder
     * @param StockByWebsiteIdResolver $stockByWebsiteIdResolver
     * @param AppendReservationsInterface $appendReservations
     * @param StoreManagerInterface $storeManager
     * @param IsSourceItemsAllowedForProductTypeInterface $isSourceItemsAllowedForProductType
     * @param GetProductTypesBySkusInterface $getProductTypesBySkus
     * @param SourceItemsSaveInterface $sourceItemsSave
     * @param ProductRepositoryInterface $productRepository
     * @param GetStockItemConfigurationInterface $getStockItemConfiguration
     * @param GetSourceItem $getSourceItem
     */
    public function __construct(
        ReservationBuilderInterface $reservationBuilder,
        StockByWebsiteIdResolver $stockByWebsiteIdResolver,
        AppendReservationsInterface $appendReservations,
        StoreManagerInterface $storeManager,
        IsSourceItemsAllowedForProductTypeInterface $isSourceItemsAllowedForProductType,
        GetProductTypesBySkusInterface $getProductTypesBySkus,
        SourceItemsSaveInterface $sourceItemsSave,
        ProductRepositoryInterface $productRepository,
        GetStockItemConfigurationInterface $getStockItemConfiguration,
        GetSourceItem $getSourceItem
    ) {
        $this->reservationBuilder = $reservationBuilder;
        $this->stockByWebsiteIdResolver = $stockByWebsiteIdResolver;
        $this->appendReservations = $appendReservations;
        $this->storeManager = $storeManager;
        $this->isSourceItemsAllowedForProductType = $isSourceItemsAllowedForProductType;
        $this->getProductTypesBySkus = $getProductTypesBySkus;
        $this->sourceItemsSave = $sourceItemsSave;
        $this->productRepository = $productRepository;
        $this->getStockItemConfiguration = $getStockItemConfiguration;
        $this->getSourceItem = $getSourceItem;
    }

    /**
     * @param array $items
     * @param string $sourceCode
     * @param int $stockId
     * @return void
     * @throws LocalizedException
     */
    public function execute(array $items, string $sourceCode, int $stockId): void
    {
        $reservations = [];
        $sourceItemsToSave = [];

        foreach ($items as $item) {
            if (!$item->getBackToStock()) {
                $qty = $item->getQty();
                $sku = $item->getSku() ?: $this->productRepository->getById($item->getProductId())->getSku();
                $productType = $this->getProductTypesBySkus->execute([$sku])[$sku];

                $stockItemConfiguration = $this->getStockItemConfiguration->execute(
                    $sku,
                    $stockId
                );

                if (!$stockItemConfiguration->isManageStock()
                    || false === $this->isSourceItemsAllowedForProductType->execute($productType)
                ) {
                    continue;
                }

                $sourceItem = $this->getSourceItem->execute($sku, $sourceCode);

                if (($sourceItem->getQuantity() - $qty) >= 0) {
                    $sourceItem->setQuantity($sourceItem->getQuantity() - $qty);
                    $sourceItemsToSave[] = $sourceItem;

                    $reservation = $this->reservationBuilder
                        ->setSku($sku)
                        ->setQuantity((float)$qty)
                        ->setStockId($stockId)
                        ->build();
                    $reservations[] = $reservation;
                } else {
                    throw new LocalizedException(__('Negative quantity is not allowed.'));
                }
            }
        }

        $this->appendReservations->execute($reservations);
        if ($sourceItemsToSave) {
            $this->sourceItemsSave->execute($sourceItemsToSave);
        }
    }
}
