/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define(['ko'], function (ko) {

    let loc = ko.observable();

    return {
        /**
         * Set selected  pickup location.
         *
         * @param {Object} pickupLocation
         */
        setSelectedLocation: function (pickupLocation) {
            loc(pickupLocation);
        },

        /**
         * Get Selected Location observable.
         *
         * @returns {observable}
         */
        getSelectedLocation: function () {
            return loc;
        }
    };
});
