/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */
define([
    'underscore',
    'Magento_Ui/js/form/element/select'
], function (_, select) {
    'use strict'; //eslint-disable-line

    return select.extend({
        /**
         * @inheritdoc
         */
        setOptions: function (data) {
            var result = this._super(data);

            if (data.length === 1) {
                this.value(data[0].value);
            }

            return result;
        }
    });
});
