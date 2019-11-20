<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\InventoryInStorePickupSales\Model;

use Magento\InventoryInStorePickupSalesApi\Model\ResultInterface;
use Magento\InventoryInStorePickupSalesApi\Model\ResultExtensionInterface;

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
    private $failed;

    /**
     * @param array $failed
     */
    public function __construct(array $failed = [])
    {
        $this->failed = $failed;
    }

    /**
     * @inheritdoc
     */
    public function isSuccessful() : bool
    {
        return empty($this->failed);
    }

    /**
     * @inheritdoc
     */
    public function getFailed() : array
    {
        return $this->failed;
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
