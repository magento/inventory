<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryDefaultForCountrySourceSelection\Plugin\InventoryApi\SourceRepository;

use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryDefaultForCountrySourceSelection\Model\Source\InitCountriesSelectionExtensionAttributes;

/**
 * Populate store pickup extension attributes when loading single order.
 */
class LoadCountriesSelectionOnGetPlugin
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
     * Add extension attribute object to source
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
