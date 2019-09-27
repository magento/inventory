<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\ValidationChecker;

/**
 * Checks whether given value is an empty value
 */
class NotAnEmptyString
{
    /**
     * Checks whether given value is an empty value
     *
     * @param string $fieldName
     * @param mixed $value
     * @return array
     */
    public function execute(string $fieldName, $value): array
    {
        $errors = [];

        if ('' === trim((string)$value)) {
            $errors[] = __('"%field" can not be empty.', ['field' => $fieldName]);
        }

        return $errors;
    }
}
