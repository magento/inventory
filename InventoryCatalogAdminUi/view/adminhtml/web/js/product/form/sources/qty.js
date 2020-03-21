/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_CatalogInventory/js/components/qty-validator-changer'
], function (Validator) {
    'use strict';

    return Validator.extend({

        /**
         * Set default value for source quantity, depends on 'Use Decimal" value.
         *
         * @param {Integer} isDecimal
         * @returns void
         */
        setDefaultValue: function (isDecimal) {
            if (!this.value()) {
                isDecimal ? this.value('0.0') : this.value('0');
            }
        }
    });
});
