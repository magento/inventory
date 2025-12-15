/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */

/**
 * Configurable variation left qty.
 */
define([
    'jquery',
    'underscore',
    'mage/url'
], function ($, _, urlBuilder) {
    'use strict'; //eslint-disable-line

    return function (productSku, salesChannel, salesChannelCode) {
        var selectorInfoStockSkuQty = '.availability.only',
            selectorInfoStockSkuQtyValue = '.availability.only > strong',
            productQtyInfoBlock = $(selectorInfoStockSkuQty),
            productQtyInfo = $(selectorInfoStockSkuQtyValue);

        if (!_.isUndefined(productSku) && productSku !== null) {
            $.ajax({
                url: urlBuilder.build('inventory_catalog/product/getQty/'),
                dataType: 'json',
                data: {
                    'sku': productSku,
                    'channel': salesChannel,
                    'salesChannelCode': salesChannelCode
                }
            }).done(function (response) {
                if (response.qty !== null && response.qty > 0) {
                    productQtyInfo.text(response.qty);
                    productQtyInfoBlock.show();
                } else {
                    productQtyInfoBlock.hide();
                }
            }).fail(function () {
                productQtyInfoBlock.hide();
            });
        } else {
            productQtyInfoBlock.hide();
        }
    };
});
