<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Inventory\Model\Source;

use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\DataObject;
use Magento\Inventory\Model\ResourceModel\GetSourceTypeBySourceCode;
use Magento\InventoryApi\Api\Data\SourceInterface;

/**
 * Set source type related source extension attributes
 */
class InitTypeCodeExtensionAttributes
{
    /**
     * @var ExtensionAttributesFactory
     */
    private $extensionAttributesFactory;

    /**
     * @var GetSourceTypeBySourceCode
     */
    private $getSourceTypeBySourceCode;

    /**
     * @param ExtensionAttributesFactory $extensionAttributesFactory
     * @param GetSourceTypeBySourceCode $getSourceTypeBySourceCode
     */
    public function __construct(
        ExtensionAttributesFactory $extensionAttributesFactory,
        GetSourceTypeBySourceCode $getSourceTypeBySourceCode
    ) {
        $this->extensionAttributesFactory = $extensionAttributesFactory;
        $this->getSourceTypeBySourceCode = $getSourceTypeBySourceCode;
    }

    /**
     * Set source type related source extension attributes.
     *
     * @param SourceInterface $source
     */
    public function execute(SourceInterface $source): void
    {
        if (!$source instanceof DataObject) {
            return;
        }
        $sourceTypeCode = $this->getSourceTypeBySourceCode->execute($source->getSourceCode());

        $extensionAttributes = $source->getExtensionAttributes();

        if ($extensionAttributes === null) {
            $extensionAttributes = $this->extensionAttributesFactory->create(SourceInterface::class);
            /** @noinspection PhpParamsInspection */
            $source->setExtensionAttributes($extensionAttributes);
        }

        $extensionAttributes->setTypeCode($sourceTypeCode);
    }
}
