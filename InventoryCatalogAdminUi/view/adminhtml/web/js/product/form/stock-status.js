/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */

define([
    'Magento_Ui/js/form/element/select'
], function (Select) {
    'use strict'; //eslint-disable-line

    return Select.extend({
        defaults: {
            links: {
                linkedValue: false
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
            this._super();

            if (parseFloat(this.initialValue) !== parseFloat(this.value())) {
                this.source.set(this.dataScope, this.value());
            } else {
                this.source.remove(this.dataScope);
            }
        }
    });
});
