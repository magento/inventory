<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model;

use Magento\Framework\Model\AbstractExtensibleModel;
use Magento\Inventory\Model\ResourceModel\SourceType as SourceTypeResourceModel;
use Magento\InventoryApi\Api\Data\SourceTypeExtensionInterface;
use Magento\InventoryApi\Api\Data\SourceTypeInterface;
use Magento\InventoryApi\Api\Data\SourceTypeLinkInterface;

/**
 * @inheritDoc
 */
class SourceType extends AbstractExtensibleModel implements SourceTypeInterface
{

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(SourceTypeResourceModel::class);
    }

    /**
     * @inheritDoc
     */
    public function getTypeCode(): ?string
    {
        return $this->getData(self::TYPE_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setTypeCode(?string $typeCode): void
    {
        $this->setData(self::TYPE_CODE, $typeCode);
    }

    /**
     * @inheritDoc
     */
    public function getName(): ?string
    {
        return $this->getData(self::NAME);
    }

    /**
     * @inheritDoc
     */
    public function setName(?string $name): void
    {
        $this->setData(self::NAME, $name);
    }

    /**
     * @inheritdoc
     */
    public function getExtensionAttributes(): ?SourceTypeExtensionInterface
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
    public function setExtensionAttributes(SourceTypeExtensionInterface $extensionAttributes): void
    {
        $this->_setExtensionAttributes($extensionAttributes);
    }
}
