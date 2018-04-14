/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'Magento_Ui/js/dynamic-rows/dynamic-rows-grid'
], function (dynamicRowsGrid) {
    'use strict';

    return dynamicRowsGrid.extend({

        /**
         * Mutes parent method
         */
        updateInsertData: function () {
            return false;
        }
    });
});
