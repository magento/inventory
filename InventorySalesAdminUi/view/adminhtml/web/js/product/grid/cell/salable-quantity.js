/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */
define([
    'Magento_Ui/js/grid/columns/column'
], function (Column) {
    'use strict'; //eslint-disable-line

    return Column.extend({
        defaults: {
            bodyTmpl: 'Magento_InventorySalesAdminUi/product/grid/cell/salable-quantity.html'
        },

        /**
         * Get salable quantity data (stock name and salable qty)
         *
         * @param {Object} record - Record object
         * @returns {Array} Result array
         */
        getSalableQuantityData: function (record) {
            return record[this.index] ? record[this.index] : [];
        }
    });
});
