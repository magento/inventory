<?php
/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model;

use Magento\InventoryApi\Model\GetSourceCodesBySkusInterface;

/**
 * @inheritdoc
 */
class GetSourceCodesBySkus implements GetSourceCodesBySkusInterface
{
    /**
     * @var ResourceModel\GetSourceCodesBySkus
     */
    private $getSourceCodesBySkus;

    /**
     * @param ResourceModel\GetSourceCodesBySkus $getSourceCodesBySkus
     */
    public function __construct(ResourceModel\GetSourceCodesBySkus $getSourceCodesBySkus)
    {
        $this->getSourceCodesBySkus = $getSourceCodesBySkus;
    }

    /**
     * Executes a resource model to retrieve source codes for the provided SKUs and returns the result as an array.
     *
     * @param array $skus
     * @return array
     */
    public function execute(array $skus): array
    {
        return $this->getSourceCodesBySkus->execute($skus);
    }
}
