/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */
define([
    'Magento_Ui/js/grid/columns/column'
], function (Column) {
    'use strict'; //eslint-disable-line

    return Column.extend({
        defaults: {
            bodyTmpl: 'Magento_InventoryShippingAdminUi/order/grid/cell/allocated-sources-cell.html',
            itemsToDisplay: 5
        },

        /**
         *
         * @param {Array} record
         * @returns {Array}
         */
        getTooltipData: function (record) {
            return record[this.index];
        },

        /**
         * @param {Object} record - Record object
         * @returns {Array} Result array
         */
        getAllocatedSources: function (record) {
            return this.getTooltipData(record).slice(0, this.itemsToDisplay);
        }
    });
});
