/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'Magento_Ui/js/grid/columns/column'
], function (Column) {
    'use strict';

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
