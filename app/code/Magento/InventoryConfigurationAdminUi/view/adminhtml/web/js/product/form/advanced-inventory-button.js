/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Ui/js/form/components/button'
], function (Button) {
    'use strict';

    return Button.extend({

        /**
         * Disable "Advance Inventory" button, if there no sources(stock) assigned to product.
         *
         * @param {Object} assignedSources
         */
        onAssignSourcesChanged: function (assignedSources) {
            if (assignedSources.length === 0) {
                this.disabled(true);
                this.source.set('data.product.stock_data.stock_id', null);
            } else {
                this.disabled(false);
            }
        }
    });
});
