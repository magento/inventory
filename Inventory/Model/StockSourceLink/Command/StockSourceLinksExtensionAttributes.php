<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\StockSourceLink\Command;

use Magento\Inventory\Model\ResourceModel\StockSourceLink\Collection as StockSourceLinkCollection;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;

/**
 * Joint extension attributes to stock source links
 */
class StockSourceLinksExtensionAttributes
{
    /**
     * @var JoinProcessorInterface
     */
    private $extensionAttributesJoinProcessor;

    /**
     * @var bool
     */
    private $joinExtensionAttributes = false;

    /**
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param bool $joinExtensionAttributes
     */
    public function __construct(
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        bool $joinExtensionAttributes = false
    ) {
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->joinExtensionAttributes = $joinExtensionAttributes;
    }

    /**
     * Process of joining extension attributes
     *
     * @param StockSourceLinkCollection $collection
     */
    public function process(StockSourceLinkCollection $collection)
    {
        if ($this->joinExtensionAttributes) {
            $this->extensionAttributesJoinProcessor->process($collection);
        }
        $this->joinExtensionAttributes = false;
    }

    /**
     * Join extension attributes
     *
     * @param bool $join
     */
    public function joinExtensionAttributes(bool $join)
    {
        $this->joinExtensionAttributes = $join;
    }
}
