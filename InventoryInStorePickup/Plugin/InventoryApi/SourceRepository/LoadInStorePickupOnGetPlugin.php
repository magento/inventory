<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Plugin\InventoryApi\SourceRepository;

use Magento\Framework\DataObject;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryInStorePickup\Model\Source\InitPickupLocationExtensionAttributes;

class LoadInStorePickupOnGetPlugin
{
    /**
     * @var InitPickupLocationExtensionAttributes
     */
    private $setExtensionAttributes;

    /**
     * @param InitPickupLocationExtensionAttributes $setExtensionAttributes
     */
    public function __construct(
        InitPickupLocationExtensionAttributes $setExtensionAttributes
    ) {
        $this->setExtensionAttributes = $setExtensionAttributes;
    }

    /**
     * Enrich the given Source Objects with the In-Store pickup attribute
     *
     * @param SourceRepositoryInterface $subject
     * @param SourceInterface $source
     *
     * @return SourceInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(
        SourceRepositoryInterface $subject,
        SourceInterface $source
    ): SourceInterface {
        $this->setExtensionAttributes->execute($source);

        return $source;
    }
}
