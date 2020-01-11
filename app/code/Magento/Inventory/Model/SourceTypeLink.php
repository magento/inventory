<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model;

use Magento\InventoryApi\Api\Data\SourceTypeLinkInterface;
use Magento\Framework\Model\AbstractExtensibleModel;
use Magento\Inventory\Model\ResourceModel\SourceTypeLink as SourceTypeLinkResourceModel;
use Magento\InventoryApi\Api\Data\SourceTypeLinkExtensionInterface;

/**
 * @inheritdoc
 *
 * @codeCoverageIgnore
 */
class SourceTypeLink extends AbstractExtensibleModel implements SourceTypeLinkInterface
{

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(SourceTypeLinkResourceModel::class);
    }

    /**
     * @inheritdoc
     */
    public function getSourceCode(): ?string
    {
        return $this->getData(self::SOURCE_CODE);
    }

    /**
     * @inheritdoc
     */
    public function setSourceCode(?string $sourceCode): void
    {
        $this->setData(self::SOURCE_CODE, $sourceCode);
    }

    /**
     * @inheritdoc
     */
    public function getTypeCode(): ?string
    {
        return $this->getData(self::TYPE_CODE);
    }

    /**
     * @inheritdoc
     */
    public function setTypeCode(?string $typeCode): void
    {
        $this->setData(self::TYPE_CODE, $typeCode);
    }

    /**
     * @inheritdoc
     */
    public function getExtensionAttributes(): ?SourceTypeLinkExtensionInterface
    {
        $extensionAttributes = $this->_getExtensionAttributes();
        if (null === $extensionAttributes) {
            $extensionAttributes = $this->extensionAttributesFactory->create(SourceTypeLinkInterface::class);
            $this->setExtensionAttributes($extensionAttributes);
        }
        return $extensionAttributes;
    }

    /**
     * @inheritdoc
     */
    public function setExtensionAttributes(SourceTypeLinkExtensionInterface $extensionAttributes): void
    {
        $this->_setExtensionAttributes($extensionAttributes);
    }
}
