/*
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define(
    [
        'jquery',
        'prototype'
    ],
    function ($) {
        'use strict';

        return function () {
            var STORE_PICKUP_METHOD = 'in_store_pickup',
                SOURCES_FIELD_SELECTOR = '#shipping_form_pickup_location_source';

            /**
             * Disable billing address form;
             * Display sources dropdown field;
             * And vice-versa
             *
             * @param {Boolean} isStorePickup
             */
            function setStorePickupMethod(isStorePickup) {
                var sourcesInput = $(SOURCES_FIELD_SELECTOR),
                    theSameAsBilling = $('#order-shipping_same_as_billing + label');

                if (isStorePickup) {
                    window.order.disableShippingAddress(true);
                    theSameAsBilling.hide();
                    sourcesInput.show();

                    return;
                }
                window.order.disableShippingAddress($('#order-shipping_same_as_billing').prop('checked'));
                theSameAsBilling.show();
                sourcesInput.hide();
            }

            /**
             * Set shipping method override
             *
             * @param {String} method
             */
            window.AdminOrder.prototype.setShippingMethod = function (method) {
                var data = {};

                data['order[shipping_method]'] = method;

                this.loadArea(
                    [
                        'shipping_method',
                        'totals',
                        'billing_method'
                    ],
                    true,
                    data
                ).then(
                    function () {
                        setStorePickupMethod(method === STORE_PICKUP_METHOD);
                    }
                );
            };
        };
    }
);
