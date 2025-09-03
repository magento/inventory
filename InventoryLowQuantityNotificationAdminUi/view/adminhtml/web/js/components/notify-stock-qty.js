/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */

define([
    'Magento_Ui/js/form/element/abstract'
], function (AbstractField) {
    'use strict'; //eslint-disable-line

    return AbstractField.extend({
        defaults: {
            notifyStockQtyUseDefault: '',
            manageStock: '',
            listens: {
                notifyStockQtyUseDefault: 'onChange',
                manageStock: 'onChange'
            }
        },

        /**
         * @inheritdoc
         */
        initObservable: function () {
            return this
                ._super()
                .observe(['notifyStockQtyUseDefault', 'manageStock']);
        },

        /**
         * Disable input when Manage Stock switched off or Notify Quantity Use Default
         */
        onChange: function () {
            this.disabled(
                this.notifyStockQtyUseDefault() ||
                this.manageStock()
            );
        }
    });
});
