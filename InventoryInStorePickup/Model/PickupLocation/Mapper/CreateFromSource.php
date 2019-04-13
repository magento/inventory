<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickup\Model\PickupLocation\Mapper;

use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Api\SimpleDataObjectConverter;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryInStorePickup\Model\PickupLocationFactory;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;

class CreateFromSource
{
    /**
     * @var \Magento\InventoryInStorePickup\Model\PickupLocationFactory
     */
    private $pickupLocationFactory;

    /**
     * @var \Magento\Framework\Api\ExtensionAttributesFactory
     */
    private $extensionAttributesFactory;

    /**
     * CreateFromSource constructor.
     *
     * @param \Magento\InventoryInStorePickup\Model\PickupLocationFactory $pickupLocationFactory
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionAttributesFactory
     */
    public function __construct(
        PickupLocationFactory $pickupLocationFactory,
        ExtensionAttributesFactory $extensionAttributesFactory
    ) {
        $this->pickupLocationFactory = $pickupLocationFactory;
        $this->extensionAttributesFactory = $extensionAttributesFactory;
    }

    /**
     * @param \Magento\InventoryApi\Api\Data\SourceInterface $source
     * @param array $map
     *
     * @return \Magento\InventoryInStorePickup\Model\PickupLocation
     */
    public function execute(SourceInterface $source, array $map)
    {
        $data = [];
        $pickupLocationExtension = $this->extensionAttributesFactory->create(PickupLocationInterface::class);

        foreach ($map as $sourceField => $pickupLocationField) {
            if ($this->isExtensionAttributeField($sourceField)) {
                $fieldValue = $source->getExtensionAttributes()->{$this->getGetterMethodName(
                    $this->getExtensionAttributeFieldName($sourceField)
                )}();
            } else {
                $fieldValue = $source->{$this->getGetterMethodName($sourceField)}();
            }

            if ($this->isExtensionAttributeField($pickupLocationField)) {
                $pickupLocationExtension->{$this->getSetterMethodName(
                        $this->getExtensionAttributeFieldName($pickupLocationField)
                    )}($fieldValue);
            } else {
                $data[SimpleDataObjectConverter::snakeCaseToCamelCase($pickupLocationField)] = $fieldValue;
            }
        }

        $data['extensionAttributes'] = $pickupLocationExtension;

        return $this->pickupLocationFactory->create($data);
    }

    /**
     * @param $fieldName
     *
     * @return string
     */
    private function getExtensionAttributeFieldName($fieldName): string
    {
        $field = explode('.', $fieldName);

        return end($field);
    }

    /**
     * Check if field should be get from extension attributes.
     *
     * @param $fieldName
     *
     * @return bool
     */
    private function isExtensionAttributeField($fieldName): bool
    {
        return strpos($fieldName, 'extension_attributes.') === 0;
    }

    /**
     * Get getter name based on field name.
     *
     * @param string $fieldName
     *
     * @return string
     */
    private function getGetterMethodName(string $fieldName): string
    {
        return 'get' . SimpleDataObjectConverter::snakeCaseToUpperCamelCase($fieldName);
    }

    /**
     * Get setter name for Extension Attribute based on field name.
     *
     * @param string $fieldName
     *
     * @return string
     */
    private function getSetterMethodName(string $fieldName): string
    {
        return 'set' . SimpleDataObjectConverter::snakeCaseToUpperCamelCase($fieldName);
    }
}
