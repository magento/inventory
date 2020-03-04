<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySourceDeductionApi\Model;

use Magento\InventoryConfigurationApi\Model\GetAllowedProductTypesForSourceItemManagementInterface;

/**
 * Is product could be deducted service.
 */
class IsItemCouldBeDeductedByTypes
{
    /**
     * @var GetAllowedProductTypesForSourceItemManagementInterface
     */
    private $allowedProductTypesForSourceItemManagement;

    /**
     * @param GetAllowedProductTypesForSourceItemManagementInterface $allowedProductTypesForSourceItemManagement
     */
    public function __construct(
        GetAllowedProductTypesForSourceItemManagementInterface $allowedProductTypesForSourceItemManagement
    ) {
        $this->allowedProductTypesForSourceItemManagement = $allowedProductTypesForSourceItemManagement;
    }

    /**
     * Verify, if product could be deducted in case product has changed type.
     *
     * @param string $productTypeToDeduct
     * @param string $actualProductType
     * @return bool
     */
    public function execute(string $productTypeToDeduct, string $actualProductType): bool
    {
        return $productTypeToDeduct === $actualProductType || $productTypeToDeduct !== $actualProductType
            && in_array($actualProductType, $this->allowedProductTypesForSourceItemManagement->execute());
    }
}
