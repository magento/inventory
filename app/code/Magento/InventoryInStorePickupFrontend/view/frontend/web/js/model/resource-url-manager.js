/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define(['jquery', 'Magento_Checkout/js/model/resource-url-manager'], function (
    $,
    resourceUrlManager
) {
    'use strict';

    return {
        /**
         * Returns URL for REST API to fetch nearby pickup locations defined for given sales channel.
         *
         * @param {String} salesChannelCode - Code of the sales channel.
         * @param {Object} searchCriteria
         */
        getUrlForNearbyPickupLocations: function (
            salesChannelCode,
            searchCriteria
        ) {
            var urls = {
                    default: '/inventory/in-store-pickup/pickup-locations/'
                },
                criteria = {
                    searchRequest: {
                        scopeCode: salesChannelCode
                    }
                };

            searchCriteria = {
                searchRequest: searchCriteria
            };

            return (
                resourceUrlManager.getUrl(urls, {}) +
                '?' +
                $.param($.extend(true, criteria, searchCriteria))
            );
        },

        /**
         * Returns URL for REST API to fetch all pickup locations defined for given sales channel.
         *
         * @param {String} salesChannelType - Type of the sales channel, e.g. website.
         * @param {String} salesChannelCode - Code of the sales channel.
         */
        getUrlForPickupLocationsAssignedToSalesChannel: function (
            salesChannelType,
            salesChannelCode
        ) {
            var params = {
                    salesChannelType: salesChannelType,
                    salesChannelCode: salesChannelCode
                },
                urls = {
                    default: '/inventory/in-store-pickup/pickup-locations-assigned-to-sales-channel/' +
                        ':salesChannelType/:salesChannelCode'
                };

            return resourceUrlManager.getUrl(urls, params);
        },

        /**
         * Returns URL for REST API to fetch pickup location with given code defined for given sales channel.
         *
         * @param {String} salesChannelCode - Code of the sales channel.
         * @param {String} pickupLocationCode - Code of the pickup location.
         */
        getUrlForPickupLocation: function (
            salesChannelCode,
            pickupLocationCode
        ) {
            var urls = {
                    default: '/inventory/in-store-pickup/pickup-locations/'
                },
                searchRequest = {
                    searchRequest: {
                        filterSet: {
                            pickupLocationCodeFilter: {
                                value: pickupLocationCode
                            }
                        },
                        scopeCode: salesChannelCode
                    }
                };

            return resourceUrlManager.getUrl(urls, {}) +
                '?' +
                $.param(searchRequest);
        }
    };
});
