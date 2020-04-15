<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Plugin\Inventory\Model\SourceRepository;

use Magento\Inventory\Model\Source\InitTypeCodeExtensionAttributes;
use Magento\InventoryApi\Api\SourceRepositoryInterface;
use Magento\InventoryApi\Api\Data\SourceSearchResultsInterface;

/**
 * Populate source type extension attribute when loading single order.
 */
class LoadSourceTypeOnGetListPlugin
{
    /**
     * @var InitTypeCodeExtensionAttributes
     */
    private $setExtensionAttributes;

    /**
     * @param InitTypeCodeExtensionAttributes $setExtensionAttributes
     */
    public function __construct(
        InitTypeCodeExtensionAttributes $setExtensionAttributes
    ) {
        $this->setExtensionAttributes = $setExtensionAttributes;
    }

    /**
     * Enrich the given Source Objects with the source type attribute
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
