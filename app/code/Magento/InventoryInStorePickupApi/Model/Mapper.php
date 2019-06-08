<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupApi\Model;

use InvalidArgumentException;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Api\SimpleDataObjectConverter;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterface;
use Magento\InventoryInStorePickupApi\Api\Data\PickupLocationInterfaceFactory;
use Magento\InventoryInStorePickupApi\Api\MapperInterface;
use Magento\InventoryInStorePickupApi\Model\Mapper\PreProcessorInterface;

/**
 * Create projection of sources on In-Store Pickup context.
 * Data transfer from source to projection will be done according to provided fields mapping.
 *
 * @api
 */
class Mapper implements MapperInterface
{
    private const MAPPING_ERROR_MESSAGE = "Wrong mapping provided for %s. Field '%s' is not found.";

    /**
     * Attributes map for projection.
     *
     * @var array
     */
    private $map;

    /**
     * @var PreProcessorInterface[]
     */
    private $preProcessors;

    /**
     * @var PickupLocationInterfaceFactory
     */
    private $pickupLocationFactory;

    /**
     * @var ExtensionAttributesFactory
     */
    private $extensionAttributesFactory;

    /**
     * @param PickupLocationInterfaceFactory $pickupLocationFactory
     * @param ExtensionAttributesFactory $extensionAttributesFactory
     * @param string[] $map
     * Please use format 'extension_attributes.field_name' to do so. E.g.
     * [
     *      "extension_attributes.source_field" => "pickup_location_field"
     *      "extension_attributes.source_field" => "extension_attributes.pickup_location_extension_field",
     * ]
     * @param PreProcessorInterface[] $preProcessors Map for Source Fields pre-processing. E.g.
     * [
     *      "source_field" => PreProcessorInterface,
     *      "extension_attributes.source_field" => PreProcessorInterface
     * ]
     */
    public function __construct(
        PickupLocationInterfaceFactory $pickupLocationFactory,
        ExtensionAttributesFactory $extensionAttributesFactory,
        array $map = [],
        array $preProcessors = []
    ) {
        $this->map = $map;

        foreach ($preProcessors as $preProcessor) {
            if (!$preProcessor instanceof PreProcessorInterface) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Source Data PreProcessor must implement %s.',
                        PreProcessorInterface::class
                    )
                );
            }
        }

        $this->preProcessors = $preProcessors;
        $this->pickupLocationFactory = $pickupLocationFactory;
        $this->extensionAttributesFactory = $extensionAttributesFactory;
    }

    /**
     * @param SourceInterface $source
     *
     * @return PickupLocationInterface
     */
    public function map(SourceInterface $source): PickupLocationInterface
    {
        $data = $this->extractDataFromSource($source);
        $data = $this->preProcessData($source, $data);
        $data = $this->preparePickupLocationFields($data);

        return $this->pickupLocationFactory->create($data);
    }

    /**
     * @param SourceInterface $source
     * @param array $data
     *
     * @return array
     */
    private function preProcessData(SourceInterface $source, array $data)
    {
        foreach ($this->preProcessors as $field => $processor) {
            if (array_key_exists($field, $data)) {
                $data[$field] = $processor->process($source, $data[$field]);
            }
        }

        return $data;
    }

    /**
     * @param array $sourceData
     *
     * @return array
     */
    private function preparePickupLocationFields(array $sourceData): array
    {
        $pickupLocationExtension = $this->extensionAttributesFactory->create(PickupLocationInterface::class);
        $pickupLocationMethods = get_class_methods(PickupLocationInterface::class);
        $data = [
            'extensionAttributes' => $pickupLocationExtension
        ];

        foreach ($sourceData as $sourceField => $value) {
            $pickupLocationField = $this->map[$sourceField];
            if ($this->isExtensionAttributeField($pickupLocationField)) {
                $methodName = $this->getSetterMethodName($this->getExtensionAttributeFieldName($pickupLocationField));

                if (!method_exists($pickupLocationExtension, $methodName)) {
                    throw new InvalidArgumentException(
                        sprintf(
                            self::MAPPING_ERROR_MESSAGE,
                            PickupLocationInterface::class,
                            $pickupLocationField
                        )
                    );
                }
                $pickupLocationExtension->{$methodName}($value);
            } else {
                $methodName = $this->getGetterMethodName($pickupLocationField);
                if (!in_array($methodName, $pickupLocationMethods)) {
                    throw new InvalidArgumentException(
                        sprintf(
                            self::MAPPING_ERROR_MESSAGE,
                            PickupLocationInterface::class,
                            $pickupLocationField
                        )
                    );
                }
                $data[SimpleDataObjectConverter::snakeCaseToCamelCase($pickupLocationField)] = $value;
            }
        }

        return $data;
    }

    /**
     * Extract values from Source according to the provided map.
     *
     * @param SourceInterface $source
     *
     * @return array
     */
    private function extractDataFromSource(SourceInterface $source): array
    {
        $sourceData = [];
        foreach (array_keys($this->map) as $sourceField) {
            if ($this->isExtensionAttributeField($sourceField)) {
                $methodName = $this->getGetterMethodName($this->getExtensionAttributeFieldName($sourceField));
                $entity = $source->getExtensionAttributes();
            } else {
                $methodName = $this->getGetterMethodName($sourceField);
                $entity = $source;
            }

            if (!method_exists($entity, $methodName)) {
                throw new InvalidArgumentException(
                    sprintf(
                        self::MAPPING_ERROR_MESSAGE,
                        SourceInterface::class,
                        $sourceField
                    )
                );
            }

            $sourceData[$sourceField] = $entity->{$methodName}();
        }

        return $sourceData;
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
