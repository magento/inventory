/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Configurable variation left qty.
 */
define([
    'jquery',
    'underscore',
    'mage/url',
    'Magento_Ui/js/model/messageList'
], function ($, _, urlBuilder, globalMessageList) {
    'use strict';

    return function (productId) {
        const selectorInfoStockSkuQty = '.availability.only',
            selectorInfoStockSkuQtyValue = '.availability.only > strong';
        let productQtyInfoBlock = $(selectorInfoStockSkuQty),
            productQtyInfo = $(selectorInfoStockSkuQtyValue);

        if (!_.isUndefined(productId) && productId !== null) {
            $.ajax({
                url: urlBuilder.build('catalog/product/getQty/'),
                dataType: 'json',
                data: {
                    'id': productId
                }
            }).done(function (response) {
                if (!_.isUndefined(response.qty)) {
                    productQtyInfo.text(response.qty);
                    productQtyInfoBlock.show();
                } else {
                    productQtyInfoBlock.hide();
                }
            }).fail(function (response) {
                let error = JSON.parse(response.responseText);

                productQtyInfoBlock.hide();
                globalMessageList.addErrorMessage({
                    message: error.message
                });
            });
        } else {
            productQtyInfoBlock.hide();
        }
    };
});
