/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define(['Magento_Checkout/js/model/resource-url-manager'], function(
    resourceUrlManager
) {
    'use strict';

    return {
        /**
         * Returns URL for REST API to fetch pickup locations defined for given sales channel.
         * @param {string} salesChannelType Type of the sales channel, e.g. website.
         * @param {string} salesChannelCode Code of the sales channel.
         */
        getUrlForPickupLocationsAssignedToSalesChannel: function(
            salesChannelType,
            salesChannelCode
        ) {
            var params = {
                    salesChannelType: salesChannelType,
                    salesChannelCode: salesChannelCode,
                },
                urls = {
                    default:
                        '/inventory/in-store-pickup/pickup-locations-assigned-to-sales-channel/:salesChannelType/:salesChannelCode',
                };

            return resourceUrlManager.getUrl(urls, params);
        },
    };
});
