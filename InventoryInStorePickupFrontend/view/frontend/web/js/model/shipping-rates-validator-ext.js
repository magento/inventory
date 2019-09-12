/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define(['underscore'], function(_) {
    'use strict';

    return function(shippingRatesValidator) {
        return _.extend(shippingRatesValidator, {
            /**
             * This method has to suppressed, otherwise store pickup address
             * gets overwritten when user adds new billing address.
             */
            validateFields: function() {},
        });
    };
});
