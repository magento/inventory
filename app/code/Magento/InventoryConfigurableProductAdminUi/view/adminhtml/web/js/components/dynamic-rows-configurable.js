/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'underscore',
    'Magento_ConfigurableProduct/js/components/dynamic-rows-configurable'
], function (_, dynamicRowsConfigurable) {
    'use strict';

    return dynamicRowsConfigurable.extend({
        defaults: {
            quantityFieldName: 'quantity_per_source',
            defaultSourceCode: 'default'
        },

        /** @inheritdoc */
        getProductData: function (row) {
            var defaultSourceCode = this.defaultSourceCode,
                quantityFieldName = this.quantityFieldName,
                defaultSourceQty = 0,
                product = this._super(row);

            product[this.quantityFieldName] = row.quantityPerSource;
            _.each(row.quantityPerSource, function (data) {
                if (data['source_code'] === defaultSourceCode) {
                    defaultSourceQty = data[quantityFieldName];
                }
            });
            product.qty = defaultSourceQty;

            return product;
        }
    });
});
