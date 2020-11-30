<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\InventoryDefaultForCountrySourceSelection\Model\Source;

use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\DataObject;
use Magento\InventoryApi\Api\Data\SourceInterface;

/**
 * Set store-pickup related source extension attributes
 */
class InitCountriesSelectionExtensionAttributes
{
    public const DEFAULT_FOR_COUNTRIES_KEY = 'default_for_countries';
    /**
     * @var ExtensionAttributesFactory
     */
    private $extensionAttributesFactory;

    /**
     * @param ExtensionAttributesFactory $extensionAttributesFactory
     */
    public function __construct(ExtensionAttributesFactory $extensionAttributesFactory)
    {
        $this->extensionAttributesFactory = $extensionAttributesFactory;
    }

    /**
     * Set store-pickup related source extension attributes.
     *
     * @param SourceInterface $source
     */
    public function execute(SourceInterface $source): void
    {
        if (!$source instanceof DataObject) {
            return;
        }
        $defaultForCountries = $source->getData(self::DEFAULT_FOR_COUNTRIES_KEY);
        $defaultForCountries = (empty($defaultForCountries)) ? [] : explode(',', $defaultForCountries);
        $extensionAttributes = $source->getExtensionAttributes();

        if ($extensionAttributes === null) {
            $extensionAttributes = $this->extensionAttributesFactory->create(SourceInterface::class);
            /** @noinspection PhpParamsInspection */
            $source->setExtensionAttributes($extensionAttributes);
        }
        if (!empty($defaultForCountries)) {
            $extensionAttributes->setDefaultForCountries($defaultForCountries);
        }
    }
}
