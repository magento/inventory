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
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterfaceFactory;

/**
 * Create Pickup Location based on Source.
 * Transport data from Source to Pickup Location according to provided mapping.
 */
class CreateFromSource
{
    /**
     * @var PickupLocationInterfaceFactory
     */
    private $pickupLocationFactory;

    /**
     * @var ExtensionAttributesFactory
     */
    private $extensionAttributesFactory;

    /**
     * CreateFromSource constructor.
     *
     * @param PickupLocationInterfaceFactory $pickupLocationFactory
     * @param ExtensionAttributesFactory $extensionAttributesFactory
     */
    public function __construct(
        PickupLocationInterfaceFactory $pickupLocationFactory,
        ExtensionAttributesFactory $extensionAttributesFactory
    ) {
        $this->pickupLocationFactory = $pickupLocationFactory;
        $this->extensionAttributesFactory = $extensionAttributesFactory;
    }

    /**
     * @param SourceInterface $source
     * @param array $map  May contains references to fields in extension attributes.
     * Please use format 'extension_attributes.field_name' to do so. E.g.
     * [
     *      "extension_attributes.source_field" => "pickup_location_field"
     *      "extension_attributes.source_field" => "extension_attributes.pickup_location_extension_field",
     * ]
     * @throws \InvalidArgumentException
     * @return PickupLocationInterface
     */
    public function execute(SourceInterface $source, array $map): PickupLocationInterface
    {
        $mappedData = $this->extractDataFromSource($source, $map);
        $data = $this->preparePickupLocationFields($mappedData);

        return $this->pickupLocationFactory->create($data);
    }

    /**
     * @param array $mappedData
     *
     * @return array
     */
    private function preparePickupLocationFields(array $mappedData): array
    {
        $pickupLocationExtension = $this->extensionAttributesFactory->create(PickupLocationInterface::class);
        $pickupLocationMethods = get_class_methods(PickupLocationInterface::class);
        $data = [
            'extensionAttributes' => $pickupLocationExtension
        ];

        foreach ($mappedData as $pickupLocationField => $value) {
            if ($this->isExtensionAttributeField($pickupLocationField)) {
                $methodName = $this->getSetterMethodName($this->getExtensionAttributeFieldName($pickupLocationField));

                if (!method_exists($pickupLocationExtension, $methodName)) {
                    $this->throwException(PickupLocationInterface::class, $pickupLocationField);
                }
                $pickupLocationExtension->{$methodName}($value);
            } else {
                $methodName = $this->getGetterMethodName($pickupLocationField);
                if (!in_array($methodName, $pickupLocationMethods)) {
                    $this->throwException(PickupLocationInterface::class, $pickupLocationField);
                }
                $data[SimpleDataObjectConverter::snakeCaseToCamelCase($pickupLocationField)] = $value;
            }
        }

        return $data;
    }

    /**
     * Extract values from Source according to the provided map.
     *
     * @param \Magento\InventoryApi\Api\Data\SourceInterface $source
     * @param string[] $map
     *
     * @return array
     */
    private function extractDataFromSource(SourceInterface $source, array $map): array
    {
        $mappedData = [];
        foreach ($map as $sourceField => $pickupLocationField) {
            if ($this->isExtensionAttributeField($sourceField)) {
                $methodName = $this->getGetterMethodName($this->getExtensionAttributeFieldName($sourceField));
                $entity = $source->getExtensionAttributes();
            } else {
                $methodName = $this->getGetterMethodName($sourceField);
                $entity = $source;
            }

            if (!method_exists($entity, $methodName)) {
                $this->throwException(SourceInterface::class, $sourceField);
            }

            $mappedData[$pickupLocationField] = $entity->{$methodName}();
        }

        return $mappedData;
    }

    /**
     * Wrapper for throwing Invalid Argument Exception.
     *
     * @param string $className
     * @param string $fieldName
     *
     * @return void
     */
    private function throwException(string $className, string $fieldName): void
    {
        $message = "Wrong mapping provided for %s. Field '%s' is not found.";

        throw new \InvalidArgumentException(sprintf($message, $className, $fieldName));
    }

    /**
     * @param $fieldName
     *
     * @return string
     */
    private function getExtensionAttributeFieldName(string $fieldName): string
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
    private function isExtensionAttributeField(string $fieldName): bool
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
