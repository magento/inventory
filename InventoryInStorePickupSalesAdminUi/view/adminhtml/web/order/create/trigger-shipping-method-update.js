/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */

define(
    [
        'jquery',
        'Magento_Sales/order/create/form'
    ],
    function ($) {
        'use strict'; //eslint-disable-line

        return function () {
            var storePickupCheckbox = $('#s_method_instore_pickup');

            if (storePickupCheckbox.length && storePickupCheckbox.prop('checked')) {
                window.order.setShippingMethod(storePickupCheckbox.val());
            }
        };
    }
);
