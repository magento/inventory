/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_ConfigurableProduct/js/variations/steps/summary',
    'jquery',
    'underscore',
    'mage/translate'
], function (Summary, $, _) {
    'use strict';

    return Summary.extend({
        defaults: {
            attributesName: [
                $.mage.__('Images'),
                $.mage.__('SKU'),
                $.mage.__('Quantity Per Source'),
                $.mage.__('Price')
            ],
            quantityFieldName: 'quantityPerSource'
        },

        /**
         * Prepare product data from grid to have all the current fields values
         *
         * @param {Object} productDataFromGrid
         * @return {Object}
         */
        prepareProductDataFromGrid: function (productDataFromGrid) {
            productDataFromGrid = _.pick(
                productDataFromGrid,
                'sku',
                'name',
                'weight',
                'status',
                'price',
                'quantity_per_source'
            );

            if (productDataFromGrid.hasOwnProperty('quantity_per_source')) {
                productDataFromGrid[this.quantityFieldName] = productDataFromGrid.quantity_per_source;
            }

            delete productDataFromGrid.quantity_per_source;

            return productDataFromGrid;
        }
    });
});
