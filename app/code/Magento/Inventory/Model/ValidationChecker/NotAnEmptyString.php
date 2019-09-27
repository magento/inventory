<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\ValidationChecker;

/**
 * Checks whether given string is empty
 */
class NotAnEmptyString
{
    /**
     * @param string $fieldName
     * @param mixed $value
     * @return array
     */
    public function execute(string $fieldName, $value): array
    {
        $errors = [];

        if ('' === trim($value)) {
            $errors[] = __('"%field" can not be empty.', ['field' => $fieldName]);
        }

        return $errors;
    }
}
