/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */

define([
    'Magento_Ui/js/form/element/single-checkbox'
], function (checkbox) {
    'use strict'; //eslint-disable-line

    return checkbox.extend({
        defaults: {
            valueFromConfig: '',
            linkedValue: ''
        },

        /**
         * @inheritdoc
         */
        initObservable: function () {
            return this
                ._super()
                .observe(['valueFromConfig', 'linkedValue']);
        },

        /**
         * @inheritdoc
         */
        'onCheckedChanged': function (newChecked) {
            if (newChecked) {
                this.linkedValue(this.valueFromConfig());
            }

            this._super(newChecked);
        },

        /**
         * @returns {String}
         */
        getInitialValue: function () {
            var values = [this.value(), this.default],
                value;

            values.some(function (v) {
                value = v || !!v;

                return value;
            });

            return this.normalizeData(value);
        }
    });
});
