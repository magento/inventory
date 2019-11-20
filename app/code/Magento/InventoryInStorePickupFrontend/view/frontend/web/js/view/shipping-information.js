/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Checkout/js/view/shipping-information',
    'Magento_Checkout/js/model/quote'
], function (Shipping, quote) {
    'use strict';

    return Shipping.extend({

        /**
         * Change template considering delivery method.
         *
         * @returns {string}
         */
        getTemplate: function () {
            this.template = this.isStorePickup()
                ? 'Magento_InventoryInStorePickupFrontend/shipping-information'
                : 'Magento_Checkout/shipping-information';

            return this.template;
        },

        /** @inheritdoc */
        getShippingMethodTitle: function () {
            var shippingMethod = quote.shippingMethod(),
                locationName = '',
                title;

            if (!this.isStorePickup()) {

                return this._super();
            }

            title = shippingMethod['carrier_title'] + ' - ' + shippingMethod['method_title'];

            if (quote.shippingAddress().firstname !== undefined) {
                locationName = quote.shippingAddress().firstname + ' ' + quote.shippingAddress().lastname;
                title += ' "' + locationName + '"';
            }

            return title;
        },

        /**
         * Get is store pickup delivery method selected.
         *
         * @returns {boolean}
         */
        isStorePickup: function () {
            var shippingMethod = quote.shippingMethod(),
                isStorePickup = false;

            if (shippingMethod !== null) {
                isStorePickup = shippingMethod['carrier_code'] === 'in_store'
                    && shippingMethod['method_code'] === 'pickup';
            }

            return isStorePickup;
        }
    });
});
