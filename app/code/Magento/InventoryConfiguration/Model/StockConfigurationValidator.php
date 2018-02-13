<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\InventoryCatalog\Model\GetProductIdsBySkusInterface;
use Magento\CatalogInventory\Model\Configuration;
use Magento\CatalogInventory\Model\Stock\StockItemRepository as LegacyStockItemRepository;
use Magento\CatalogInventory\Model\Stock\Item as LegacyStockItem;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;

/**
 * @inheritdoc
 */
class StockConfigurationValidator implements StockItemConfigurationInterface
{
    /**
     * @var StockItemConfigurationInterface[]
     */
    private $validators;

    /**
     * @var StockItemCriteriaInterfaceFactory
     */
    private $stockItemCriteriaFactory;

    /**
     * @var LegacyStockItemRepository
     */
    private $legacyStockItemRepository;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var GetProductIdsBySkusInterface
     */
    private $getProductIdsBySkus;

    /**
     * StockConfigurationValidator constructor.
     * @param array $validators
     * @param Configuration $configuration
     * @param LegacyStockItemRepository $legacyStockItemRepository
     * @param StockItemCriteriaInterfaceFactory $stockItemCriteriaFactory
     * @param GetProductIdsBySkusInterface $getProductIdsBySkus
     * @throws LocalizedException
     */
    public function __construct(
        array $validators = [],
        Configuration $configuration,
        LegacyStockItemRepository $legacyStockItemRepository,
        StockItemCriteriaInterfaceFactory $stockItemCriteriaFactory,
        GetProductIdsBySkusInterface $getProductIdsBySkus
    ) {
        $this->configuration = $configuration;
        $this->legacyStockItemRepository = $legacyStockItemRepository;
        $this->getProductIdsBySkus = $getProductIdsBySkus;
        $this->stockItemCriteriaFactory = $stockItemCriteriaFactory;

        foreach ($validators as $validator) {
            if (!$validator instanceof StockItemConfigurationInterface) {
                throw new LocalizedException(
                    __('Validator must implement StockConfigurationInterface.')
                );
            }
        }
        $this->validators = $validators;
    }

    /**
     * Old validation logic
     *
     * @param string $sku
     * @param int $stockId
     * @param float $qtyWithReservation
     * @param bool $isSalable
     * @return bool
     * @throws LocalizedException
     */
    public function execute(string $sku, int $stockId, float $qtyWithReservation, bool $isSalable): bool
    {
        //old validation logic below
        $globalMinQty = $this->configuration->getMinQty();
        $legacyStockItem = $this->getLegacyStockItem($sku)[0];
        if (null === $legacyStockItem) {
            return false;
        }
        if ($this->isManageStock($legacyStockItem)) {
            if (($legacyStockItem->getUseConfigMinQty() == 1 && $qtyWithReservation <= $globalMinQty)
                || ($legacyStockItem->getUseConfigMinQty() == 0 && $qtyWithReservation <= $legacyStockItem->getMinQty())
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param LegacyStockItem|StockItemInterface $legacyStockItem
     *
     * @return bool
     */
    private function isManageStock(LegacyStockItem $legacyStockItem): bool
    {
        $globalManageStock = $this->configuration->getManageStock();
        $manageStock = false;
        if (($legacyStockItem->getUseConfigManageStock() == 1 && $globalManageStock == 1)
            || ($legacyStockItem->getUseConfigManageStock() == 0 && $legacyStockItem->getManageStock() == 1)
        ) {
            $manageStock = true;
        }
        return $manageStock;
    }

    /**
     * @param string $sku
     * @return StockItemInterface[]|null
     * @throws LocalizedException
     */
    private function getLegacyStockItem(string $sku)
    {
        $productIds = $this->getProductIdsBySkus->execute([$sku]);
        $searchCriteria = $this->stockItemCriteriaFactory->create();
        $searchCriteria->addFilter(StockItemInterface::PRODUCT_ID, StockItemInterface::PRODUCT_ID, $productIds[$sku]);
        $legacyStockItem = $this->legacyStockItemRepository->getList($searchCriteria);
        $items = $legacyStockItem->getItems();
        return count($items) ? reset($items) : null;
    }
}
