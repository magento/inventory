<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\InventoryConfiguration\Model;

use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\InventoryConfigurationApi\Model\GetAllowedProductTypesForSourceItemManagementInterface;

/**
 * @inheritdoc
 */
class GetAllowedProductTypesForSourceItemManagement implements GetAllowedProductTypesForSourceItemManagementInterface
{
    /**
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * @param StockConfigurationInterface $stockConfiguration
     */
    public function __construct(StockConfigurationInterface $stockConfiguration)
    {
        $this->stockConfiguration = $stockConfiguration;
    }

    /**
     * @inheritdoc
     */
    public function execute(): array
    {
        return array_keys(array_filter($this->stockConfiguration->getIsQtyTypeIds()));
    }
}
