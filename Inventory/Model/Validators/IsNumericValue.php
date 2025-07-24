<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\Validators;

/**
 * Checks whether given string is empty
 */
class IsNumericValue
{
    /**
     * Checks whether given value is numeric.
     *
     * @param string $fieldName
     * @param mixed $value
     * @return array
     */
    public function execute(string $fieldName, $value): array
    {
        $errors = [];

        if (!is_numeric($value)) {
            $errors[] = __('"%field" should be numeric.', ['field' => $fieldName]);
        }

        return $errors;
    }
}
