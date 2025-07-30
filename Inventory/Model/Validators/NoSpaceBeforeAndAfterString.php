<?php
/**
 * Copyright 2023 Adobe
 * All Rights Reserved.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\Validators;

/**
 * Checks whether given value contains spaces before and after
 */
class NoSpaceBeforeAndAfterString
{
    /**
     * Checks whether given value contains spaces before and after
     *
     * @param string $fieldName
     * @param string $value
     * @return array
     */
    public function execute(string $fieldName, string $value): array
    {
        $errors = [];

        $trimValue = trim($value);
        if ($trimValue !== $value) {
            $errors[] = __('"%field" can not contain leading or trailing spaces.', ['field' => $fieldName]);
        }

        return $errors;
    }
}
