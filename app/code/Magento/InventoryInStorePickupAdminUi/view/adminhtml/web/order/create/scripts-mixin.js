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
            const SOURCES_FIELD_SELECTOR = '#shipping_form_pickup_location_source';

            window.AdminOrder.prototype.setShippingMethod = function (method) {
                let data = {};

                data['order[shipping_method]'] = method;

                this.loadArea([
                    'shipping_method',
                    'totals',
                    'billing_method'
                ], true, data).then(
                    function () {
                        setStorePickupMethod(method === STORE_PICKUP_METHOD) /* <-- That`s a line with modifications */
                    }
                );
            };

            /**
             * Disable billing address form;
             * Display sources dropdown field;
             * And vice-versa
             *
             * @param {boolean} isStorePickup
             */
            function setStorePickupMethod(isStorePickup) {
                let sourcesInput = $(SOURCES_FIELD_SELECTOR);
                let theSameAsBilling = $('#order-shipping_same_as_billing + label');
                if (isStorePickup) {
                    order.disableShippingAddress(true);
                    theSameAsBilling.hide();
                    sourcesInput.show();
                    return;
                }
                order.disableShippingAddress($('#order-shipping_same_as_billing').prop('checked'));
                theSameAsBilling.show();
                sourcesInput.hide();
            }
        };
    });
