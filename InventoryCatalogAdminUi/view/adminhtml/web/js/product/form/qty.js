/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_CatalogInventory/js/components/qty-validator-changer'
], function (Abstract) {
    'use strict';

    return Abstract.extend({
        defaults: {
            links: {
                value: false
            }
        },

        /** @inheritdoc */
        getInitialValue: function () {
            var values = [this.source.get(this.dataScope), this.default],
                value;

            values.some(function (v) {
                if (v !== null && v !== undefined) {
                    value = v;

                    return true;
                }

                return false;
            });

            return this.normalizeData(value);
        },

        /** @inheritdoc */
        setDifferedFromDefault: function () {
            var initialValue;

            this._super();
            initialValue = this.source.data.product['current_product_id'] !== null ? this.initialValue : 0;

            if (this.value() &&
                parseFloat(initialValue) !== parseFloat(this.value())
            ) {
                this.source.set(this.dataScope, this.value());
            } else {
                this.source.remove(this.dataScope);
            }
        }
    });
});
