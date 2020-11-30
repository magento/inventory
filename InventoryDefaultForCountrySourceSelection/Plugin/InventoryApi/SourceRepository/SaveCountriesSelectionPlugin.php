<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryDefaultForCountrySourceSelection\Plugin\InventoryApi\SourceRepository;

use Magento\Framework\DataObject;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryDefaultForCountrySourceSelection\Model\Source\InitCountriesSelectionExtensionAttributes;

/**
 * Set data to SourceInterface from its extension attributes to save these values to `inventory_source` DB table.
 */
class SaveCountriesSelectionPlugin
{
    /**
     * Persist the default_for_countries attribute on Source save
     *
     * @param SourceRepositoryInterface $subject
     * @param SourceInterface $source
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSave(
        SourceRepositoryInterface $subject,
        SourceInterface $source
    ): array {
        if (!$source instanceof DataObject) {
            return [$source];
        }

        $extensionAttributes = $source->getExtensionAttributes();

        if ($extensionAttributes !== null) {
            $defaultForCountries = $extensionAttributes->getDefaultForCountries();
            if (!empty($defaultForCountries)) {
                $source->setData(
                    InitCountriesSelectionExtensionAttributes::DEFAULT_FOR_COUNTRIES_KEY,
                    implode(',', $defaultForCountries)
                );
            }
        }

        return [$source];
    }
}
