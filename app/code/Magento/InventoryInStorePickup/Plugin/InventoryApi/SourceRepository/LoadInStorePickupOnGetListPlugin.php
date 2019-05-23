<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Plugin\InventoryApi\SourceRepository;

use Magento\InventoryApi\Api\Data\SourceSearchResultsInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryInStorePickup\Model\Source\InitPickupLocationExtensionAttributes;

class LoadInStorePickupOnGetListPlugin
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
     * @param SourceSearchResultsInterface $sourceSearchResults
     *
     * @return SourceSearchResultsInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetList(
        SourceRepositoryInterface $subject,
        SourceSearchResultsInterface $sourceSearchResults
    ): SourceSearchResultsInterface {
        $items = $sourceSearchResults->getItems();
        array_walk(
            $items,
            [$this->setExtensionAttributes, 'execute']
        );

        return $sourceSearchResults;
    }
}
