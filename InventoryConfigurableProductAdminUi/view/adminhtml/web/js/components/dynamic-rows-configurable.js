/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */

define([
    'Magento_ConfigurableProduct/js/components/dynamic-rows-configurable'
], function (dynamicRowsConfigurable) {
    'use strict'; //eslint-disable-line

    return dynamicRowsConfigurable.extend({
        defaults: {
            quantityFieldName: 'quantity_per_source'
        },

        /** @inheritdoc */
        getProductData: function (row) {
            var product = this._super(row);

            product[this.quantityFieldName] = row.quantityPerSource;

            return product;
        }
    });
});
