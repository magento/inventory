<?php
/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\Validators;

/**
 * Checks whether given value is an empty string
 */
class NotAnEmptyString
{
    /**
     * Checks whether given value is an empty string.
     *
     * @param string $fieldName
     * @param string $value
     * @return array
     */
    public function execute(string $fieldName, string $value): array
    {
        $errors = [];

        if ('' === trim($value)) {
            $errors[] = __('"%field" can not be empty.', ['field' => $fieldName]);
        }

        return $errors;
    }
}
