/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'Magento_Ui/js/form/components/fieldset',
    'mageUtils',
    'jquery',
], function (Fieldset, utils, $) {
    'use strict';

    /**
     * Makes ajax request
     *
     * @param {Object} data
     * @param {String} url
     * @returns {*}
     */
    function makeRequest(data, url) {
        var save = $.Deferred();

        data = utils.serialize(data);
        data['form_key'] = window.FORM_KEY;

        $('body').trigger('processStart');

        $.ajax({
            url: url,
            data: data,
            dataType: 'json',
            type: 'post',

            /**
             * Success callback.
             * @param {Object} resp
             * @returns {Boolean}
             */
            success: function (resp) {
                if (resp.ajaxExpired) {
                    window.location.href = resp.ajaxRedirect;
                }

                if (!resp.error) {
                    save.resolve(resp);

                    return true;
                }
            },

            /**
             * Complete callback.
             */
            complete: function () {
                $('body').trigger('processStop');
            }
        });

        return save.promise();
    }

    return Fieldset.extend(
        {
            defaults: {
                reloadUrl: ''
            },

            /**
             * Updates data from server.
             */
            reload: function (data) {
                var current = this;

                makeRequest(data, this.reloadUrl).then(function (data) {
                    current.source.set("data.product.stock_data", data);
                });
            },
        }
    );
});
