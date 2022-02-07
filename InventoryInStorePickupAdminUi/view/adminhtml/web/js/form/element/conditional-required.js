/*
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'Magento_Ui/js/form/element/abstract',
    'ko',
    'underscore',
    'mageUtils'
], function (uiElement, ko, _, utils) {
    'use strict';

    /**
     * Provide possibility to make field required by dependency on other field value.
     */
    return uiElement.extend(
        {
            /**
             * Convert `required` value from string ('1', '0') to bool (true, false)
             */
            initialize: function () {
                this._super();

                // eslint-disable-next-line vars-on-top
                var required = this.required;

                this.required = ko.computed({
                    /**
                     * @returns {Boolean}
                     */
                    read: function () {
                        return required();
                    },

                    /**
                     * @param {String|Boolean} value
                     */
                    write: function (value) {
                        value = Boolean(value) === value ? value : Boolean(parseInt(value, 10));

                        if (required() !== value) {
                            required(value);
                            this.setValidation('required-entry', required());
                        }
                    },
                    owner: this
                });
                this.required(required());
            },

            /**
             * @param {(String|Object)} rule
             * @param {(Object|Boolean)} [options]
             * @returns {Abstract} Chainable.
             */
            setValidation: function (rule, options) {
                var rules = utils.copy(this.validation),
                    changed;

                if (_.isObject(rule)) {
                    _.extend(this.validation, rule);
                } else {
                    this.validation[rule] = options;
                }

                changed = !utils.compare(rules, this.validation).equal;

                if (changed) {
                    this.required(!!this.validation['required-entry']);
                    this.validate();
                }

                return this;
            }
        }
    );
});
