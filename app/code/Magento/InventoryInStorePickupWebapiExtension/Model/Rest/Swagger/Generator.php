<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryInStorePickupWebapiExtension\Model\Rest\Swagger;

use Magento\Webapi\Model\Rest\Swagger\Generator as OriginalGenerator;

/**
 * @inheritdoc
 */
class Generator extends OriginalGenerator
{
    /**
     * Generate the query param name for a primitive type
     *
     * @param string $name
     * @param string $prefix
     * @return string
     */
    private function handlePrimitive($name, $prefix)
    {
        return $prefix
            ? $prefix . '[' . $name . ']'
            : $name;
    }

    /**
     * @inheritdoc
     */
    protected function getQueryParamNames($name, $type, $description, $prefix = '')
    {
        if ($this->typeProcessor->isTypeSimple($type)) {
            // Primitive type or array of primitive types
            return [
                $this->handlePrimitive($name, $prefix) => [
                    'type' => substr($type, -2) === '[]' ? $type : $this->getSimpleType($type),
                    'description' => $description
                ]
            ];
        }
        if ($this->typeProcessor->isArrayType($type)) {
            // Array of complex type
            $arrayType = substr($type, 0, -2);
            return $this->handleComplex($name, $arrayType, $prefix, true);
        } else {
            // Complex type
            return $this->handleComplex($name, $type, $prefix, false);
        }
    }

    /**
     * Recursively generate the query param names for a complex type
     *
     * @param string $name
     * @param string $type
     * @param string $prefix
     * @param bool $isArray
     * @return string[]
     */
    private function handleComplex($name, $type, $prefix, $isArray)
    {
        $typeData = $this->typeProcessor->getTypeData($type);
        $parameters = $typeData['parameters'] ?? [];
        $queryNames = [];
        foreach ($parameters as $subParameterName => $subParameterInfo) {
            $subParameterType = $subParameterInfo['type'];
            $subParameterDescription = isset($subParameterInfo['documentation'])
                ? $subParameterInfo['documentation']
                : null;
            $subPrefix = $prefix
                ? $prefix . '[' . $name . ']'
                : $name;
            if ($isArray) {
                $subPrefix .= self::ARRAY_SIGNIFIER;
            }
            $queryNames[] = $this->getQueryParamNames(
                $subParameterName,
                $subParameterType,
                $subParameterDescription,
                $subPrefix
            );
        }
        return empty($queryNames) ? [] : array_merge(...$queryNames);
    }
}
