<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Plugin\Inventory\Model\SourceRepository;

use Magento\Inventory\Model\Source\InitTypeCodeExtensionAttributes;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;

/**
 * Populate source type extension attribute when loading a list of orders.
 */
class LoadSourceTypeOnGetPlugin
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
