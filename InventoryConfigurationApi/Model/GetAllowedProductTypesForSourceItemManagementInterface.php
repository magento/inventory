<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryConfigurationApi\Model;

/**
 * Get allowed product types for source items management
 *
 * @api
 */
interface GetAllowedProductTypesForSourceItemManagementInterface
{
    /**
     * Returns an array of product types that are allowed for source item management.
     *
     * @return array
     */
    public function execute(): array;
}
