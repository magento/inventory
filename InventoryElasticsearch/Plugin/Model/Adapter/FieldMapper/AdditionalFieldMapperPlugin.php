<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryElasticsearch\Plugin\Model\Adapter\FieldMapper;

use Magento\Elasticsearch\ElasticAdapter\Model\Adapter\FieldMapper\ProductFieldMapper;

/**
 * Class AdditionalFieldMapperPlugin for es attributes mapping
 */
class AdditionalFieldMapperPlugin
{
    /**
     * @var array
     */
    private $allowedFields = [
        'is_out_of_stock' => 'integer'
    ];

    /**
     * Missing mapped attribute code
     *
     * @param ProductFieldMapper $subject
     * @param array $result
     * @param array $context
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetAllAttributesTypes(ProductFieldMapper $subject, array $result, array $context): array
    {
        foreach ($this->allowedFields as $fieldName => $fieldType) {
            $result[$fieldName] = ['type' => $fieldType];
        }

        return $result;
    }
}
