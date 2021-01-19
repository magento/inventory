/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'configurableVariationQty',
    'jquery-ui-modules/widget'
], function ($, configurableVariationQty) {
    'use strict';

    return function (configurable) {
        $.widget('mage.configurable', configurable, {

            /** @inheritdoc */
            _configureElement: function (element) {
                var salesChannel = this.options.spConfig.channel,
                    salesChannelCode = this.options.spConfig.salesChannelCode,
                    productVariationsSku = this.options.spConfig.sku;

                this._super(element);
                configurableVariationQty(productVariationsSku[this.simpleProduct], salesChannel, salesChannelCode);
            }
        });

        return $.mage.configurable;
    };
});
