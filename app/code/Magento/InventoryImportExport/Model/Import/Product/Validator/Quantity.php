<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventoryImportExport\Model\Import\Product\Validator;

use Magento\CatalogImportExport\Model\Import\Product\RowValidatorInterface;
use Magento\CatalogImportExport\Model\Import\Product\Validator\AbstractImportValidator;

class Quantity extends AbstractImportValidator implements RowValidatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function isValid($value)
    {
        $this->_clearMessages();
        if (!empty($value['qty']) && !$this->_hasValidValue($value['qty'])) {
            $this->_addMessages(
                [
                    sprintf(
                        $this->context->retrieveMessageTemplate(self::ERROR_INVALID_ATTRIBUTE_TYPE),
                        'qty',
                        'decimal'
                    ),
                ]
            );
            return false;
        }
        return true;
    }

    private function _hasValidValue($value)
    {
        if (!is_numeric($value)) {
            // If the value isn't numeric there has to be an '=' somewhere
            if (strpos($value, '=') === false) {
                return false;
            }
            // If we get here, we have an '=' somewhere in the value,
            // but if there is multiple sources in value we have to check each one is either numeric or has an '='
            if (strpos($value, '|') !== false) {
                $parts = explode('|', $value);
                foreach ($parts as $part) {
                    if (!is_numeric($part)) {
                        if (strpos($part, '=') === false) {
                            return false;
                        }
                    }
                }
            }
        }
        return true;
    }

}
