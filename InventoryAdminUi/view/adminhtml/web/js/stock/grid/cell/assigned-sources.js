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
            bodyTmpl: 'Magento_InventoryAdminUi/stock/grid/cell/assigned-sources-cell.html',
            itemsToDisplay: 5
        },

        /**
         *
         * @param {Array} record
         * @returns {Array}
         */
        getTooltipData: function (record) {
            return record[this.index].map(function (source) {
                return {
                    sourceCode: source.sourceCode,
                    name: source.name
                };
            });
        },

        /**
         * @param {Object} record - Record object
         * @returns {Array} Result array
         */
        getSourcesAssignedToStockOrderedByPriority: function (record) {
            return this.getTooltipData(record).slice(0, this.itemsToDisplay);
        }
    });
});
