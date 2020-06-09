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
             * Disable shipping address form elements;
             * Display sources dropdown field;
             * And vice-versa
             *
             * @param {Boolean} isStorePickup
             */
            function setStorePickupMethod(isStorePickup) {
                var sourcesInput = jQuery(SOURCES_FIELD_SELECTOR),
                    theSameAsBillingCheckBox = jQuery(SAME_AS_BILLING_SELECTOR),
                    customerShippingAddressId = jQuery(CUSTOMER_SHIPPING_ADDRESS_ID_SELECTOR),
                    customerShippingAddressSaveInAddressBook = jQuery(CUSTOMER_ADDRESS_SAVE_IN_ADDRESS_BOOK_SELECTOR);

                if (isStorePickup) {
                    theSameAsBillingCheckBox.prop('disabled', true);
                    customerShippingAddressId.prop('disabled', true);
                    customerShippingAddressSaveInAddressBook.prop('disabled', true);
                    sourcesInput.show();

                    return;
                }
                window.order.disableShippingAddress(jQuery(SAME_AS_BILLING_SELECTOR).prop('checked'));
                sourcesInput.hide();
            }

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
                        'billing_method'
                    ];

                data['order[shipping_method]'] = method;

                if (method === STORE_PICKUP_METHOD) {
                    data = this.serializeData(this.shippingAddressContainer).toObject();
                    data['order[shipping_method]'] = method;
                    data['shipping_as_billing'] = 0;
                    data['save_in_address_book'] = 0;
                    areas.push('shipping_address');
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
                    $(this.getAreaId('shipping_method')).update(this.shippingTemplate);

                    if (storePickupCheckbox.length && storePickupCheckbox.prop('checked')) {
                        window.order.setShippingMethod(storePickupCheckbox.val());
                    }
                }
            };
        };
    }
);
