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
            var STORE_PICKUP_METHOD = 'instore_pickup',
                SOURCES_FIELD_SELECTOR = '#shipping_form_pickup_location_source',
                SAME_AS_BILLING_SELECTOR = '#order-shipping_same_as_billing',
                CUSTOMER_SHIPPING_ADDRESS_ID_SELECTOR = '#order-shipping_address_customer_address_id',
                CUSTOMER_ADDRESS_SAVE_IN_ADDRESS_BOOK_SELECTOR = '#order-shipping_address_save_in_address_book',
                IN_STORE_PICKUP_CHECKBOX_SELECTOR = '#s_method_instore_pickup';

            /**
             * Display sources dropdown field;
             * And vice-versa
             *
             * @param {Boolean} isStorePickup
             */
            function setStorePickupMethod(isStorePickup) {
                var sourcesInput = jQuery(SOURCES_FIELD_SELECTOR),
                    shippingAddressSaveInAddressBook = jQuery(CUSTOMER_ADDRESS_SAVE_IN_ADDRESS_BOOK_SELECTOR);

                if (isStorePickup) {
                    shippingAddressSaveInAddressBook.prop('checked', false);
                    sourcesInput.show();

                    return;
                }
                window.order.disableShippingAddress(jQuery(SAME_AS_BILLING_SELECTOR).prop('checked'));
                sourcesInput.hide();
            }

            /**
             * Verify is store pickup delivery method is checked.
             */
            function isStorePickupSelected() {
                var storePickupCheckbox = jQuery(IN_STORE_PICKUP_CHECKBOX_SELECTOR);

                return storePickupCheckbox.length && storePickupCheckbox.prop('checked');
            }

            /**
             * Always disable unwanted shipping address fields in case store pickup is selected.
             */
            window.AdminOrder.prototype.disableShippingAddress =
                window.AdminOrder.prototype.disableShippingAddress.wrap(function (proceed, flag) {
                    var shippingAddressId = jQuery(CUSTOMER_SHIPPING_ADDRESS_ID_SELECTOR),
                        theSameAsBillingCheckBox = jQuery(SAME_AS_BILLING_SELECTOR),
                        shippingAddressSaveInAddressBook = jQuery(CUSTOMER_ADDRESS_SAVE_IN_ADDRESS_BOOK_SELECTOR);

                    proceed(flag);

                    if (isStorePickupSelected()) {
                        shippingAddressId.prop('disabled', true);
                        theSameAsBillingCheckBox.prop('disabled', true);
                        shippingAddressSaveInAddressBook.prop('disabled', true);
                    }
                });

            /**
             * Set shipping method override
             *
             * @param {String} method
             */
            window.AdminOrder.prototype.setShippingMethod = function (method) {
                var data = {},
                    areas = [
                        'shipping_method',
                        'totals',
                        'billing_method',
                        'shipping_address'
                    ];

                data['order[shipping_method]'] = method;

                if (method === STORE_PICKUP_METHOD) {
                    data = this.serializeData(this.shippingAddressContainer).toObject();
                    data['order[shipping_method]'] = method;
                    data['shipping_as_billing'] = 0;
                    data['save_in_address_book'] = 0;
                    this.shippingAsBilling = 0;
                    this.saveInAddressBook = 0;
                }

                this.loadArea(areas, true, data).then(
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
                var storePickupCheckbox = jQuery(IN_STORE_PICKUP_CHECKBOX_SELECTOR);

                if (!this.isOnlyVirtualProduct) {
                    /* eslint-disable no-undef */
                    $(this.getAreaId('shipping_method')).update(this.shippingTemplate);

                    if (isStorePickupSelected()) {
                        window.order.setShippingMethod(storePickupCheckbox.val());
                    }
                }
            };
        };
    }
);
