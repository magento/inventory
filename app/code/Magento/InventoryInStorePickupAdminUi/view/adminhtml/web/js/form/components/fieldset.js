/*
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'Magento_Ui/js/form/components/fieldset',
    'ko'
], function (Fieldset, ko) {
    'use strict';

    /**
     * TODO Remove when issue is resolved in core.
     * @see Please check issue in core for more details: https://github.com/magento/magento2/issues/22067.
     */
    return Fieldset.extend(ko).extend(
        {
            /**
             * Convert `visible` value from string ('1', '0') to bool (true, false)
             */
            initialize: function () {
                this._super();

                // eslint-disable-next-line vars-on-top
                var visible = ko.observable(this.visible());

                this.visible = ko.computed({
                    /**
                     * @returns {Boolean}
                     */
                    read: function () {
                        return visible();
                    },

                    /**
                     * @param {String} value
                     */
                    write: function (value) {
                        visible(Boolean(parseInt(value, 0)));
                    },
                    owner: this
                });
                this.visible(visible());
            }
        }
    );
});
