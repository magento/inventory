/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
define([
    'mage/translate',
    'Magento_Ui/js/grid/columns/column'
], function ($t, Column) {
    'use strict'; //eslint-disable-line

    return Column.extend({
        defaults: {
            bodyTmpl: 'Magento_InventoryBundleProductAdminUi/grid/column/quantity-per-source',
            itemsToDisplay: 3,
            showFullListDescription: $t('Show more...')
        },

        /**
         * Get source items from product data.
         *
         * @param {Object} rowData
         * @returns {Array}
         */
        getSourceItemsData: function (rowData) {
            return rowData['quantity_per_source'];
        }
    });
});
