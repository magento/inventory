<?php
/**
 * Copyright 2026 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model\SourceItem\Validator;

use Magento\Framework\Validation\ValidationResult;
use Magento\Framework\Validation\ValidationResultFactory;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Model\SourceItemValidatorInterface;
use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;

/**
 * Reject source items for product types that don't support source-item management (e.g. configurable,
 * bundle, grouped). Without this check, SourceItemsSave persists an inventory_source_item row for such
 * a SKU unconditionally, an orphan row that has no admin UI surface for editing or deletion.
 */
class ProductTypeManagementAllowedValidator implements SourceItemValidatorInterface
{
    /**
     * @param GetProductTypesBySkusInterface $getProductTypesBySkus
     * @param IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType
     * @param ValidationResultFactory $validationResultFactory
     */
    public function __construct(
        private readonly GetProductTypesBySkusInterface $getProductTypesBySkus,
        private readonly IsSourceItemManagementAllowedForProductTypeInterface
        $isSourceItemManagementAllowedForProductType,
        private readonly ValidationResultFactory $validationResultFactory
    ) {
    }

    /**
     * @inheritdoc
     */
    public function validate(SourceItemInterface $source): ValidationResult
    {
        $sku = (string)$source->getSku();
        $productType = $this->getProductTypesBySkus->execute([$sku])[$sku] ?? null;

        $errors = [];
        if ($productType !== null && !$this->isSourceItemManagementAllowedForProductType->execute($productType)) {
            $errors[] = __(
                'Source items are not supported for product type "%1" (SKU "%2").',
                $productType,
                $sku
            );
        }

        return $this->validationResultFactory->create(['errors' => $errors]);
    }
}
