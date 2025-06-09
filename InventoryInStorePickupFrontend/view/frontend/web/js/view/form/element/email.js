/**
 * Copyright 2019 Adobe
 * All Rights Reserved.
 */

define(['jquery', 'Magento_Checkout/js/view/form/element/email'], function (
    $,
    Component
) {
    'use strict'; //eslint-disable-line

    return Component.extend({
        defaults: {
            template:
                'Magento_InventoryInStorePickupFrontend/form/element/email',
            links: {
                email:
                    'checkout.steps.shipping-step.shippingAddress.customer-email:email'
            }
        }
    });
});
