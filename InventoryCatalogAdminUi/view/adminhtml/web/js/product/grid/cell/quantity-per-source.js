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
            bodyTmpl: 'Magento_InventoryCatalogAdminUi/product/grid/cell/source-items.html',
            itemsToDisplay: 5
        },

        /**
         * Get source items data (source name and qty)
         *
         * @param {Object} record - Record object
         * @returns {Array} Result array
         */
        getSourceItemsData: function (record) {
            return record[this.index] ? record[this.index] : [];
        },

        /**
         * @param {Object} record - Record object
         * @returns {Array} Result array
         */
        getSourceItemsDataCut: function (record) {
            return this.getSourceItemsData(record).slice(0, this.itemsToDisplay);
        }
    });
});
