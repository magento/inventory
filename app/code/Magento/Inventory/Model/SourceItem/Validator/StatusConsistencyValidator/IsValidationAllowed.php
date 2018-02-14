<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\SourceItem\Validator\StatusConsistencyValidator;

use Magento\Inventory\Model\IsSourceItemsManagementAllowedForProductTypeInterface;
use Magento\Inventory\Model\ResourceModel\GetProductTypeBySku;
use Magento\InventoryApi\Api\Data\SourceItemInterface;

/**
 * Check if we need Status Consistency Validator for particular product type.
 */
class IsValidationAllowed
{
    /**
     * @var GetProductTypeBySku
     */
    private $getProductTypeBySku;

    /**
     * @var IsSourceItemsManagementAllowedForProductTypeInterface
     */
    private $isSourceItemsManagementAllowedForProductType;

    /**
     * @param GetProductTypeBySku $getProductTypeBySku
     * @param IsSourceItemsManagementAllowedForProductTypeInterface $isSourceItemsManagementAllowedForProductType
     */
    public function __construct(
        GetProductTypeBySku $getProductTypeBySku,
        IsSourceItemsManagementAllowedForProductTypeInterface $isSourceItemsManagementAllowedForProductType
    ) {
        $this->getProductTypeBySku = $getProductTypeBySku;
        $this->isSourceItemsManagementAllowedForProductType = $isSourceItemsManagementAllowedForProductType;
    }

    /**
     * @param SourceItemInterface $sourceItem
     *
     * @return bool
     */
    public function execute(SourceItemInterface $sourceItem): bool
    {
        $product = $this->getProductTypeBySku->execute($sourceItem->getSku());

        return $this->isSourceItemsManagementAllowedForProductType->execute(reset($product));
    }
}
