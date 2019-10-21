/*
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define(
    [
        'jquery',
        'prototype'
    ],
    function (jQuery) {
        'use strict';

        return function () {
            var STORE_PICKUP_METHOD = 'in_store_pickup',
                SOURCES_FIELD_SELECTOR = '#shipping_form_pickup_location_source',
                SAME_AS_BILLING_SELECTOR = '#order-shipping_same_as_billing',
                IN_STORE_PICKUP_CHECKBOX_SELECTOR = '#s_method_in_store_pickup';

            /**
             * Disable billing address form;
             * Display sources dropdown field;
             * And vice-versa
             *
             * @param {Boolean} isStorePickup
             */
            function setStorePickupMethod(isStorePickup) {
                var sourcesInput = jQuery(SOURCES_FIELD_SELECTOR),
                    theSameAsBilling = jQuery(SAME_AS_BILLING_SELECTOR + ' + label');

                if (isStorePickup) {
                    window.order.disableShippingAddress(true);
                    theSameAsBilling.hide();
                    sourcesInput.show();

                    return;
                }
                window.order.disableShippingAddress(jQuery(SAME_AS_BILLING_SELECTOR).prop('checked'));
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

            /**
             * Replace shipping method area.
             * Restore store pickup shipping method if it was already selected.
             */
            window.AdminOrder.prototype.resetShippingMethod = function () {
                if (!this.isOnlyVirtualProduct) {
                    var storePickupCheckbox = jQuery(IN_STORE_PICKUP_CHECKBOX_SELECTOR);

                    $(this.getAreaId('shipping_method')).update(this.shippingTemplate);

                    if (storePickupCheckbox.length && storePickupCheckbox.prop('checked')) {
                        window.order.setShippingMethod(storePickupCheckbox.val());
                    }
                }
            };
        };
    }
);
