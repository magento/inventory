<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\InventoryInStorePickupSales\Model;

use Magento\InventoryInStorePickupSalesApi\Api\Data\ResultInterface;
use Magento\InventoryInStorePickupSalesApi\Api\Data\ResultExtensionInterface;

/**
 * Operation result object that contains statuses for each operation.
 */
class Result implements ResultInterface
{
    /**
     * @var ResultExtensionInterface
     */
    private $extensionAttributes;

    /**
     * @var array
     */
    private $errors;

    /**
     * @param array $errors
     */
    public function __construct(array $errors = [])
    {
        $this->errors = $errors;
    }

    /**
     * @inheritdoc
     */
    public function isSuccessful() : bool
    {
        return empty($this->errors);
    }

    /**
     * @inheritdoc
     */
    public function getErrors() : array
    {
        return $this->errors;
    }

    /**
     * @inheritdoc
     */
    public function setExtensionAttributes(?ResultExtensionInterface $extensionAttributes): void
    {
        $this->extensionAttributes = $extensionAttributes;
    }

    /**
     * @inheritdoc
     */
    public function getExtensionAttributes(): ?ResultExtensionInterface
    {
        return $this->extensionAttributes;
    }
}
