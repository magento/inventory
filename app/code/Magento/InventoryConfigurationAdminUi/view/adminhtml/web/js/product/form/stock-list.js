/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'Magento_Ui/js/form/element/select',
    'uiRegistry',
    'underscore',
    'Magento_Ui/js/modal/confirm'
], function (Element, registry, _, confirm) {
    'use strict';

    return Element.extend({
            defaults: {
                list: [],
            },

            onAssignSourcesChanged: function (data) {
                var stockIds = [],
                    assignedStocks = [],
                    sourceStockIds;

                _.each(data, function (row) {
                    sourceStockIds = row['stock_ids'].split(',');
                    _.each(sourceStockIds, function (stockId) {
                        stockIds.push(Number(stockId));
                    })
                });

                stockIds = _.unique(stockIds);

                _.each(this.list, function (row) {
                    if (_.contains(stockIds, row.value)) {
                        assignedStocks.push(row);
                    }
                });
                this.setOptions(assignedStocks);
            },

            /**
             * @inheritDoc
             */
            onUpdate: function () {
                this._super();
                var current = this,
                    data = {
                        'sku': this.source.data.product.sku ? this.source.data.product.sku : null,
                        'stockId': this.value()
                    };
                //todo: implment confirmation modal window.
                /* confirm({
                     content:  "Please confirm stock switching. All data that hasn\'t been saved will be lost.",
                     actions: {
                         confirm: function() {
                             registry.get(current.parentName).reload(data);
                         },
                         cancel: function() {
                         }
                     }
                 });*/
                registry.get(current.parentName).reload(data);
            },
        }
    );
});
