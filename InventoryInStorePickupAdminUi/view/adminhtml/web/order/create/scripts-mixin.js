/*
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
        'jquery'
    ],
    function ($) {
        'use strict';
        return function () {
            const STORE_PICKUP_METHOD = 'in_store_pickup';

            window.AdminOrder.prototype.setShippingMethod = function (method) {
                let data = {};

                data['order[shipping_method]'] = method;
                this.loadArea([
                    'shipping_method',
                    'totals',
                    'billing_method'
                ], true, data);

                hideShippingAddressForm(method === STORE_PICKUP_METHOD) /* <-- That`s also a line with modifications */
            };

            function hideShippingAddressForm(hide) {
                debugger;
                let theSameAsBilling = $('#order-shipping_same_as_billing + label');
                if (hide) {
                    order.disableShippingAddress(true);
                    theSameAsBilling.hide();
                    return;
                }
                order.disableShippingAddress($('#order-shipping_same_as_billing').prop('checked'));
                theSameAsBilling.show();
            }
        };
    });
