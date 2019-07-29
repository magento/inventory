/*
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
        'jquery',
        'Magento_Sales/order/create/form',
    ],
    function ($) {
        'use strict';
        return function () {
        debugger;
            let storePickupCheckbox = $('#s_method_in_store_pickup');
            if (storePickupCheckbox.length && storePickupCheckbox.prop('checked')) {
                order.setShippingMethod(storePickupCheckbox.val());
            }
        };
    });
