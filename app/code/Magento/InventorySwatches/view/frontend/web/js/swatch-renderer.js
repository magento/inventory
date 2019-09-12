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
                this._super($this, widget);
                configurableVariationQty(widget.getProductId());
            }
        });

        return $.mage.SwatchRenderer;
    };
});
