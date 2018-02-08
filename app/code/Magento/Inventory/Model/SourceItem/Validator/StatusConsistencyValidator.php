<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\SourceItem\Validator;

use Magento\Catalog\Model\ResourceModel\Product as ProductResourceModel;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Model\Stock\Item as LegacyStockItem;
use Magento\CatalogInventory\Model\Stock\StockItemRepository as LegacyStockItemRepository;
use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\Inventory\Model\OptionSource\SourceItemStatus;
use Magento\InventoryApi\Api\Data\SourceItemInterface;

/**
 * Check that if product quantity <= 0 status is not "In Stock".
 */
class StatusConsistencyValidator implements SourceItemValidatorInterface
{
    /**
     * @var ValidationResultFactory
     */
    private $validationResultFactory;

    /**
     * @var SourceItemStatus
     */
    private $sourceItemStatus;

    /**
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * @var LegacyStockItemRepository
     */
    private $legacyStockItemRepository;

    /**
     * @var ProductResourceModel
     */
    private $productResource;

    /**
     * @var StockItemCriteriaInterfaceFactory
     */
    private $stockItemCriteriaFactory;

    /**
     * @param ValidationResultFactory $validationResultFactory
     * @param SourceItemStatus $sourceItemStatus
     * @param StockConfigurationInterface $stockConfiguration
     * @param LegacyStockItemRepository $legacyStockItemRepository
     * @param ProductResourceModel $productResource
     * @param StockItemCriteriaInterfaceFactory $stockItemCriteriaFactory
     */
    public function __construct(
        ValidationResultFactory $validationResultFactory,
        SourceItemStatus $sourceItemStatus,
        StockConfigurationInterface $stockConfiguration,
        LegacyStockItemRepository $legacyStockItemRepository,
        ProductResourceModel $productResource,
        StockItemCriteriaInterfaceFactory $stockItemCriteriaFactory
    ) {
        $this->validationResultFactory = $validationResultFactory;
        $this->sourceItemStatus = $sourceItemStatus;
        $this->stockConfiguration = $stockConfiguration;
        $this->legacyStockItemRepository = $legacyStockItemRepository;
        $this->productResource = $productResource;
        $this->stockItemCriteriaFactory = $stockItemCriteriaFactory;
    }

    /**
     * @inheritdoc
     */
    public function validate(SourceItemInterface $sourceItem): ValidationResult
    {
        $quantity = $sourceItem->getQuantity();
        $status = $sourceItem->getStatus();
        $errors = [];

        $legacyStockItem = $this->getLegacyStockItem($sourceItem->getSku());
        if (null === $legacyStockItem) {
            $isManageStock = true;
        } else {
            $isManageStock = $this->isManageStock($legacyStockItem);
        }

        if ($isManageStock
            && is_numeric($quantity)
            && (float)$quantity <= 0
            && (int)$status === SourceItemInterface::STATUS_IN_STOCK
        ) {
            $statusOptions = $this->sourceItemStatus->toOptionArray();
            $labels = array_column($statusOptions, 'label', 'value');
            $errors[] = __(
                'Product cannot have "%status" "%in_stock" while product "%quantity" equals or below zero',
                [
                    'status' => SourceItemInterface::STATUS,
                    'in_stock' => $labels[SourceItemInterface::STATUS_IN_STOCK],
                    'quantity' => SourceItemInterface::QUANTITY,

                ]
            );
        }

        return $this->validationResultFactory->create(['errors' => $errors]);
    }

    /**
     * @param string $sku
     * @return LegacyStockItem|null
     */
    private function getLegacyStockItem(string $sku)
    {
        $productIds = $this->productResource->getProductsIdsBySkus([$sku]);
        $searchCriteria = $this->stockItemCriteriaFactory->create();
        $searchCriteria->addFilter(StockItemInterface::PRODUCT_ID, StockItemInterface::PRODUCT_ID, $productIds[$sku]);

        $legacyStockItem = $this->legacyStockItemRepository->getList($searchCriteria);
        $items = $legacyStockItem->getItems();

        return count($items) ? reset($items) : null;
    }

    /**
     * @param LegacyStockItem $legacyStockItem
     * @return bool
     */
    private function isManageStock(LegacyStockItem $legacyStockItem): bool
    {
        $globalManageStock = $this->stockConfiguration->getManageStock();
        $manageStock = false;
        if (($legacyStockItem->getUseConfigManageStock() == 1 && $globalManageStock == 1)
            || ($legacyStockItem->getUseConfigManageStock() == 0 && $legacyStockItem->getManageStock() == 1)
        ) {
            $manageStock = true;
        }

        return $manageStock;
    }
}
