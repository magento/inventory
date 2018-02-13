<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\SourceItem\Validator\StatusConsistencyValidator;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Inventory\Model\IsSourceItemsManagementAllowedForProductTypeInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;

/**
 * Check if we need Status Consistency Validator for particular product type.
 */
class IsValidationAllowed
{
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var IsSourceItemsManagementAllowedForProductTypeInterface
     */
    private $isSourceItemsManagementAllowedForProductType;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param IsSourceItemsManagementAllowedForProductTypeInterface $isSourceItemsManagementAllowedForProductType
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        IsSourceItemsManagementAllowedForProductTypeInterface $isSourceItemsManagementAllowedForProductType
    ) {
        $this->productRepository = $productRepository;
        $this->isSourceItemsManagementAllowedForProductType = $isSourceItemsManagementAllowedForProductType;
    }

    /**
     * @param SourceItemInterface $sourceItem
     *
     * @return bool
     */
    public function execute(SourceItemInterface $sourceItem): bool
    {
        $product = $this->productRepository->get($sourceItem->getSku());

        return $this->isSourceItemsManagementAllowedForProductType->execute($product->getTypeId());
    }
}
