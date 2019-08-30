<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model;

use Magento\Framework\Model\AbstractExtensibleModel;
use Magento\Inventory\Model\ResourceModel\SourceTypeLink as SourceTypeLinkResourceModel;
use Magento\InventoryApi\Api\Data\SourceTypeLinkExtensionInterface;
use Magento\InventoryApi\Api\Data\SourceTypeLinkInterface;

/**
 * {@inheritdoc}
 *
 * @codeCoverageIgnore
 */
class SourceTypeLink extends AbstractExtensibleModel implements SourceTypeLinkInterface
{
    /**
     * @inheritdoc
     */
    protected function _construct()
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
    public function getPosition(): ?int
    {
        return $this->getData(self::POSITION) === null ?
            null:
            (int)$this->getData(self::POSITION);
    }

    /**
     * @inheritdoc
     */
    public function setPosition(?int $position): void
    {
        $this->setData(self::POSITION, $position);
    }

    /**
     * @inheritdoc
     */
    public function getExtensionAttributes(): ?SourceTypeLinkExtensionInterface
    {
        $extensionAttributes = $this->_getExtensionAttributes();
        if (null === $extensionAttributes) {
            $extensionAttributes = $this->extensionAttributesFactory->create(SourceCarrierLinkInterface::class);
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
