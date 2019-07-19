/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'ko',
    'underscore',
    'uiComponent',
    'Magento_Checkout/js/action/select-shipping-address',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/shipping-address/form-popup-state',
    'Magento_Checkout/js/checkout-data',
    'Magento_Customer/js/customer-data',
    'Magento_Checkout/js/model/address-converter',
    'Magento_Checkout/js/action/select-shipping-address',
    'Magento_Checkout/js/model/checkout-data-resolver',
], function(
    $,
    ko,
    _,
    Component,
    selectShippingAddressAction,
    quote,
    formPopUpState,
    checkoutData,
    customerData,
    addressConverter,
    selectShippingAddress,
    checkoutDataResolver
) {
    'use strict';

    var countryData = customerData.get('directory-data');

    return Component.extend({
        defaults: {
            template:
                'Magento_InventoryInStorePickupFrontend/pickup-location-address/address-renderer/default',
        },

        /** @inheritdoc */
        initObservable: function() {
            this._super();
            this.isSelected = ko.computed(function() {
                var sourceCode,
                    isSelected = false,
                    shippingAddress = quote.shippingAddress();

                console.log(shippingAddress);

                if (shippingAddress && shippingAddress.customAttributes) {
                    sourceCode = _.findWhere(shippingAddress.customAttributes, {
                        attribute_code: 'sourceCode',
                    });
                    isSelected =
                        sourceCode &&
                        sourceCode.value === this.address().source_code;
                }

                return isSelected;
            }, this);

            return this;
        },

        /**
         * @param {String} countryId
         * @return {String}
         */
        getCountryName: function(countryId) {
            return countryData()[countryId] != undefined
                ? countryData()[countryId].name
                : ''; //eslint-disable-line
        },

        /** Set selected customer shipping address  */
        selectAddress: function() {
            var address = $.extend(
                {},
                addressConverter.formAddressDataToQuoteAddress({
                    firstname: this.address().name,
                    lastname: 'Store',
                    street: [this.address().street],
                    city: this.address().city,
                    postcode: this.address().postcode,
                    countryId: this.address().country_id,
                    telephone: this.address().phone,
                    region: this.address().region,
                    region_id: this.address().region_id,
                    custom_attributes: {
                        sourceCode: this.address().source_code,
                    },
                })
            );
            quote.billingAddress(null);
            selectShippingAddress(address);
            checkoutData.setShippingAddressFromData(address);
            checkoutData.setSelectedShippingAddress(address.getKey());
            checkoutDataResolver.resolveBillingAddress();

            // selectShippingAddressAction(this.address());
            // checkoutData.setSelectedShippingAddress(this.address().getKey());
        },

        /**
         * Show popup.
         */
        showPopup: function() {
            $('[data-open-modal="opc-new-shipping-address"]').trigger('click');
        },
    });
});
