/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define(['jquery', 'Magento_Checkout/js/model/resource-url-manager'], function(
    $,
    resourceUrlManager
) {
    'use strict';

    return {
        /**
         * Returns URL for REST API to fetch nearby pickup locations defined for given sales channel.
         * @param {string} salesChannelType Type of the sales channel, e.g. website.
         * @param {string} salesChannelCode Code of the sales channel.
         */
        getUrlForNearbyPickupLocations: function(
            salesChannelType,
            salesChannelCode,
            searchCriteria
        ) {
            var params = {
                    salesChannelType: salesChannelType,
                    salesChannelCode: salesChannelCode,
                },
                urls = {
                    default:
                        '/inventory/in-store-pickup/get-nearby-pickup-locations/:salesChannelType/:salesChannelCode',
                };

            return (
                resourceUrlManager.getUrl(urls, params) +
                '?' +
                $.param(searchCriteria)
            );
        },
        /**
         * Returns URL for REST API to fetch all pickup locations defined for given sales channel.
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
        /**
         * Returns URL for REST API to fetch pickup location with given code defined for given sales channel.
         * @param {string} salesChannelType Type of the sales channel, e.g. website.
         * @param {string} salesChannelCode Code of the sales channel.
         * @param {string} pickupLocationCode Code of the pickup location.
         */
        getUrlForPickupLocation: function(
            salesChannelType,
            salesChannelCode,
            pickupLocationCode
        ) {
            var params = {
                    salesChannelType: salesChannelType,
                    salesChannelCode: salesChannelCode,
                    pickupLocationCode: pickupLocationCode,
                },
                urls = {
                    default:
                        '/inventory/in-store-pickup/pickup-location/:salesChannelType/:salesChannelCode/:pickupLocationCode',
                };

            return resourceUrlManager.getUrl(urls, params);
        },
    };
});
