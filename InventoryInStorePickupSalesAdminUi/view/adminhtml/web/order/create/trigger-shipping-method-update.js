/*
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define(
    [
        'jquery',
        'Magento_Sales/order/create/form'
    ],
    function ($) {
        'use strict';

        return function () {
            var storePickupCheckbox = $('#s_method_instore_pickup');

            if (storePickupCheckbox.length && storePickupCheckbox.prop('checked')) {
                window.order.setShippingMethod(storePickupCheckbox.val());
            }
        };
    }
);
