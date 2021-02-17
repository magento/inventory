/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define(['jquery', 'Magento_Checkout/js/view/form/element/email'], function (
    $,
    Component
) {
    'use strict';

    return Component.extend({
        defaults: {
            template:
                'Magento_InventoryInStorePickupFrontend/form/element/email',
            links: {
                email:
                    'checkout.steps.shipping-step.shippingAddress.customer-email:email'
            },

            /**
             * Checking is form exist.
             */
            emailHasChanged: function () {
                var loginFormSelector = 'form[data-role=email-with-possible-login]',
                    $loginForm = $(loginFormSelector);

                if ($loginForm.length > 0) {
                    this._super();
                }
            }
        }
    });
});
