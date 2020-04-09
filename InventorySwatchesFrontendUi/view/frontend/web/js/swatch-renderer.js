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

    return function (SwatchRenderer) {
        $.widget('mage.SwatchRenderer', SwatchRenderer, {

            /** @inheritdoc */
            _OnClick: function ($this, widget) {
                var salesChannel = this.options.jsonConfig.channel,
                    salesChannelCode = this.options.jsonConfig.salesChannelCode,
                    productVariationsSku = this.options.jsonConfig.sku;

                this._super($this, widget);
                configurableVariationQty(productVariationsSku[widget.getProductId()], salesChannel, salesChannelCode);
            }
        });

        return $.mage.SwatchRenderer;
    };
});
