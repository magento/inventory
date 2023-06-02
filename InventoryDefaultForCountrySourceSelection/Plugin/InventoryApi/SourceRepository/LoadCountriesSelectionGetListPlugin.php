<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryDefaultForCountrySourceSelection\Plugin\InventoryApi\SourceRepository;

use Magento\InventoryApi\Api\Data\SourceSearchResultsInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryDefaultForCountrySourceSelection\Model\Source\InitCountriesSelectionExtensionAttributes;

/**
 * Populate countries selection extension attribute when loading a list of sources.
 */
class LoadCountriesSelectionGetListPlugin
{
    /**
     * @var InitCountriesSelectionExtensionAttributes
     */
    private $setExtensionAttributes;

    /**
     * @param InitCountriesSelectionExtensionAttributes $setExtensionAttributes
     */
    public function __construct(
        InitCountriesSelectionExtensionAttributes $setExtensionAttributes
    ) {
        $this->setExtensionAttributes = $setExtensionAttributes;
    }

    /**
     * Add extension attribute object to source items
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
