/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'configurableVariationQty',
    'jquery/ui'
], function ($, configurableVariationQty) {
    'use strict';

    return function (configurable) {
        $.widget('mage.configurable', configurable, {

            /**
             * @inheritDoc
             */
            _configureElement: function (element) {
                this._super(element);
                configurableVariationQty(this.simpleProduct);
            }
        });

        return $.mage.configurable;
    };
});
