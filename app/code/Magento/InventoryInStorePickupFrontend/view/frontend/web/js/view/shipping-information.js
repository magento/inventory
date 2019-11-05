/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Checkout/js/view/shipping-information',
    'Magento_Checkout/js/model/quote',
], function (Shipping, quote) {
    'use strict';

    return Shipping.extend({
        defaults: {
            template: 'Magento_InventoryInStorePickupFrontend/shipping-information'
        },

        /**
         * @inheritDoc
         */
        getShippingMethodTitle: function () {
            var shippingMethod = quote.shippingMethod(),
                locationName = '',
                title;

            title = shippingMethod['carrier_title'] + ' - ' + shippingMethod['method_title'];
            if (quote.shippingAddress().firstname !== undefined) {
                locationName = quote.shippingAddress().firstname + ' ' + quote.shippingAddress().lastname;
                title += ' "' + locationName + '"';
            }

            return title;
        },
    });
});
