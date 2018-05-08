<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\Framework\Api;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\ObjectManager\ConfigInterface;
use Magento\Framework\Reflection\NameFinder;
use Magento\Framework\Reflection\TypeProcessor;
use Magento\Framework\Webapi\ServiceTypeToEntityTypeMap;
use Zend\Code\Reflection\ClassReflection;

class ImmutableDataObjectHelper
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var TypeProcessor
     */
    private $typeProcessor;

    /**
     * @var NameFinder
     */
    private $nameFinder;

    /**
     * @var ServiceTypeToEntityTypeMap
     */
    private $serviceTypeToEntityTypeMap;

    /**
     * @var ObjectFactory
     */
    private $objectFactory;

    /**
     * ImmutableDataObjectHelper constructor.
     * @param ObjectFactory $objectFactory
     * @param TypeProcessor $typeProcessor
     * @param ConfigInterface $config
     * @param NameFinder $nameFinder
     * @param ServiceTypeToEntityTypeMap $serviceTypeToEntityTypeMap
     */
    public function __construct(
        ObjectFactory $objectFactory,
        TypeProcessor $typeProcessor,
        ConfigInterface $config,
        NameFinder $nameFinder,
        ServiceTypeToEntityTypeMap $serviceTypeToEntityTypeMap
    ) {
        $this->config = $config;
        $this->typeProcessor = $typeProcessor;
        $this->nameFinder = $nameFinder;
        $this->serviceTypeToEntityTypeMap = $serviceTypeToEntityTypeMap;
        $this->objectFactory = $objectFactory;
    }

    /**
     * Return true if a class is inherited from \Magento\Framework\Model\AbstractModel
     *
     * @param string $className
     * @return bool
     */
    private function isAbstractModel(string $className): bool
    {
        $className = $this->getRealClassName($className);
        return is_subclass_of($className, AbstractModel::class);
    }

    /**
     * Return true if a class is inherited from \Magento\Framework\Model\AbstractModel
     *
     * @param string $className
     * @return bool
     */
    private function isImmutableDto(string $className): bool
    {
        return is_subclass_of($className, ImmutableDtoInterface::class);
    }

    /**
     * Get real class name (if preferenced)
     *
     * @param string $className
     * @return string
     */
    private function getRealClassName(string $className): string
    {
        $preferenceClass = $this->config->getPreference($className);
        return $preferenceClass ?: $className;
    }

    /**
     * @param string $className
     * @param array $data
     * @return array
     * @throws \ReflectionException
     */
    private function getConstructorData(string $className, array $data): array
    {
        $className = $this->getRealClassName($className);
        $class = new ClassReflection($className);

        $constructor = $class->getConstructor();
        if ($constructor === null) {
            return [];
        }

        // Inject into named parameters if a getter method exists
        $res = [];
        $parameters = $constructor->getParameters();
        $parametersByName = [];
        foreach ($parameters as $parameter) {
            $parametersByName[$parameter->getName()] = $parameter;

            $type = $this->getPropertyTypeFromGetterMethod($class, $parameter->getName());
            if (($type !== '') && (isset($data[$parameter->getName()]))) {
                $res[$parameter->getName()] = $data[$parameter->getName()];
            }
        }

        // Inject data field if existing for backward compatibility with
        // \Magento\Framework\Model\AbstractModel
        if ($this->isAbstractModel($class->getName())) {
            $res['data'] = [];
            foreach ($data as $propertyName => $propertyValue) {
                // Find data getter to retrieve its type
                $snakeCaseProperty = SimpleDataObjectConverter::camelCaseToSnakeCase($propertyName);
                $type = $this->getPropertyTypeFromGetterMethod($class, $propertyName);
                if ($type !== '') {
                    $res['data'][$snakeCaseProperty] = $propertyValue;
                }
            }
        }

        return $res;
    }

    /**
     * Return the property type by its getter name
     * @param ClassReflection $classReflection
     * @param string $propertyName
     * @return string
     */
    private function getPropertyTypeFromGetterMethod(ClassReflection $classReflection, string $propertyName): string
    {
        $camelCaseProperty = SimpleDataObjectConverter::snakeCaseToUpperCamelCase($propertyName);
        try {
            $methodName = $this->nameFinder->getGetterMethodName($classReflection, $camelCaseProperty);
        } catch (\Exception $e) {
            return '';
        }

        $methodReflection = $classReflection->getMethod($methodName);
        if ($methodReflection->isPublic()) {
            return (string) $this->typeProcessor->getGetterReturnType($methodReflection)['type'];
        }

        return '';
    }

    /**
     * Populate data object using data in array format.
     *
     * @param array $data
     * @param string $interfaceName
     * @return ImmutableDtoInterface
     * @throws \ReflectionException
     */
    public function createFromArray(array $data, string $interfaceName): ImmutableDtoInterface
    {
        $constructorArgs = $this->getConstructorData($interfaceName, $data);
        $resObject = $this->objectFactory->create($interfaceName, $constructorArgs);

        if ($this->isImmutableDto($interfaceName)) {
            throw new \InvalidArgumentException("$interfaceName must implement ImmutableDtoInterface");
        }

        if ($this->isAbstractModel($interfaceName)) {
            $resObject->setDataChanges(true);
        }

        return $resObject;
    }

    /**
     * Populate data object using data in array format.
     *
     * @param ImmutableDtoInterface $sourceObject
     * @param array $data
     * @param string $interfaceName
     * @return ImmutableDtoInterface
     * @throws \ReflectionException
     */
    public function mapFromArray(
        ImmutableDtoInterface $sourceObject,
        array $data,
        string $interfaceName
    ): ImmutableDtoInterface {
        $data = $this->mergeObjectData($sourceObject, $data);
        return $this->createFromArray($data, $interfaceName);
    }

    /**
     * Merge data into object data
     *
     * @param ImmutableDtoInterface $sourceObject
     * @return array
     */
    public function getObjectData(ImmutableDtoInterface $sourceObject): array
    {
        if ($this->isAbstractModel(get_class($sourceObject))) {
            return $sourceObject->getData();
        }

        // TODO: Handle extension_attributes & custom_attributes
        $sourceObjectMethods = get_class_methods(get_class($sourceObject));

        $res = [];
        foreach ($sourceObjectMethods as $sourceObjectMethod) {
            if (preg_match('/^(is|get)([A-Z]\w*)$/', $sourceObjectMethod, $matches)) {
                $propertyName = SimpleDataObjectConverter::camelCaseToSnakeCase($matches[2]);
                $res[$propertyName] = $sourceObject->$matches[0]();
            }
        }

        return $res;
    }

    /**
     * Merge data into object data
     *
     * @param ImmutableDtoInterface $sourceObject
     * @param array $data
     * @return array
     */
    public function mergeObjectData(ImmutableDtoInterface $sourceObject, array $data): array
    {
        // TODO: Handle extension_attributes & custom_attributes
        $sourceData = $this->getObjectData($sourceObject);
        return array_merge($sourceData, $data);
    }
}
