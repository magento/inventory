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
class NoWhitespaceInString
{
    /**
     * @param string $fieldName
     * @param mixed $value
     * @return array
     */
    public function execute(string $fieldName, $value): array
    {
        $errors = [];

        if (preg_match('/\s/', $value)) {
            $errors[] = __('"%field" can not contain whitespaces.', ['field' => $fieldName]);
        }

        return $errors;
    }
}
