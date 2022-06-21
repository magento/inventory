<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryElasticsearch\Plugin\Elasticsearch\Model\Adapter\FieldMapper\Product\FieldProvider;

use Magento\Elasticsearch\Model\Adapter\FieldMapper\Product\AttributeProvider;
use Magento\Elasticsearch\Model\Adapter\FieldMapper\Product\FieldProvider\DynamicField;
use Magento\Elasticsearch\Model\Adapter\FieldMapper\Product\FieldProvider\FieldName\ResolverInterface as FieldNameResolver;
use Magento\Elasticsearch\Model\Adapter\FieldMapper\Product\FieldProvider\FieldType\ConverterInterface
    as FieldTypeConverterInterface;

class DynamicFieldPlugin
{
    /**
     * @var FieldTypeConverterInterface
     */
    private $fieldTypeConverter;

    /**
     * @var AttributeProvider
     */
    private $attributeAdapterProvider;

    /**
     * @var FieldNameResolver
     */
    private $fieldNameResolver;

    /**
     * @param FieldTypeConverterInterface $fieldTypeConverter
     * @param AttributeProvider $attributeAdapterProvider
     * @param FieldNameResolver $fieldNameResolver
     */
    public function __construct(
        FieldTypeConverterInterface $fieldTypeConverter,
        AttributeProvider $attributeAdapterProvider,
        FieldNameResolver $fieldNameResolver
    ) {
        $this->fieldTypeConverter = $fieldTypeConverter;
        $this->attributeAdapterProvider = $attributeAdapterProvider;
        $this->fieldNameResolver = $fieldNameResolver;
    }

    /**
     * @param DynamicField $subject
     * @param array $allAttributes
     * @return array
     */
    public function afterGetFields(DynamicField $subject, array $allAttributes): array
    {
        $isOutOfStockAttribute = $this->attributeAdapterProvider->getByAttributeCode('is_out_of_stock');
        $groupStockItemKey = $this->fieldNameResolver->getFieldName(
            $isOutOfStockAttribute,
            []
        );
        $allAttributes[$groupStockItemKey] = [
            'type' => $this->fieldTypeConverter->convert(FieldTypeConverterInterface::INTERNAL_DATA_TYPE_INT),
            'store' => true
        ];

        return $allAttributes;
    }
}
