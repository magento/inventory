<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model\SourceItem\Validator;

use Magento\Catalog\Model\ResourceModel\Product as ProductResourceModel;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Model\Stock\Item as LegacyStockItem;
use Magento\CatalogInventory\Model\Stock\StockItemRepository as LegacyStockItemRepository;
use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\Inventory\Model\SourceItem\Validator\SourceItemValidatorInterface;
use Magento\Inventory\Model\SourceItem\Validator\StatusConsistencyValidator;
use Magento\InventoryApi\Api\Data\SourceItemInterface;

/**
 * Check for manage stock configuration.
 */
class ManageStockValidator implements SourceItemValidatorInterface
{
    /**
     * @var ValidationResultFactory
     */
    private $validationResultFactory;

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
     * @var StatusConsistencyValidator
     */
    private $statusConsistencyValidator;

    /**
     * @param ValidationResultFactory $validationResultFactory
     * @param StockConfigurationInterface $stockConfiguration
     * @param LegacyStockItemRepository $legacyStockItemRepository
     * @param ProductResourceModel $productResource
     * @param StockItemCriteriaInterfaceFactory $stockItemCriteriaFactory
     * @param StatusConsistencyValidator $statusConsistencyValidator
     */
    public function __construct(
        ValidationResultFactory $validationResultFactory,
        StockConfigurationInterface $stockConfiguration,
        LegacyStockItemRepository $legacyStockItemRepository,
        ProductResourceModel $productResource,
        StockItemCriteriaInterfaceFactory $stockItemCriteriaFactory,
        StatusConsistencyValidator $statusConsistencyValidator
    ) {
        $this->validationResultFactory = $validationResultFactory;
        $this->stockConfiguration = $stockConfiguration;
        $this->legacyStockItemRepository = $legacyStockItemRepository;
        $this->productResource = $productResource;
        $this->stockItemCriteriaFactory = $stockItemCriteriaFactory;
        $this->statusConsistencyValidator = $statusConsistencyValidator;
    }

    /**
     * @inheritdoc
     */
    public function validate(SourceItemInterface $sourceItem): ValidationResult
    {
        $errors = [];

        $legacyStockItem = $this->getLegacyStockItem($sourceItem->getSku());
        if (null === $legacyStockItem) {
            $isManageStock = true;
        } else {
            $isManageStock = $this->isManageStock($legacyStockItem);
        }

        if ($isManageStock) {
            $validationResult = $this->statusConsistencyValidator->validate($sourceItem);
        } else {
            $validationResult = $this->validationResultFactory->create(['errors' => $errors]);
        }

        return $validationResult;
    }

    /**
     * @param string $sku
     *
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
     *
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
