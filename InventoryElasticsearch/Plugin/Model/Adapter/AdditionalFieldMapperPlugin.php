<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryElasticsearch\Plugin\Model\Adapter;

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
     * @param mixed $subject
     * @param array $result
     * @return array
     */
    public function afterGetAllAttributesTypes($subject, array $result): array
    {
        foreach ($this->allowedFields as $fieldName => $fieldType) {
            $result[$fieldName] = ['type' => $fieldType];
        }

        return $result;
    }
}
