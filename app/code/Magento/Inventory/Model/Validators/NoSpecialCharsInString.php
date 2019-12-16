<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Inventory\Model\Validators;

/**
 * Checks whether given string contains special chars
 */
class NoSpecialCharsInString
{
    /**
     * Checks whether given string contains special chars
     *
     * @param string $value
     * @return array
     */
    public function execute(string $value): array
    {
        $errors = [];

        if (preg_match('/\$[:]*{(.)*}/', $value)) {
            $errors[] = __('Validation Failed');
        }

        return $errors;
    }
}
