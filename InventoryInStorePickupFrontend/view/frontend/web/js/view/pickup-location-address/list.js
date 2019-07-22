/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'underscore',
    'ko',
    'mageUtils',
    'uiComponent',
    'uiLayout',
    'Magento_InventoryInStorePickupFrontend/js/model/pickup-locations',
], function(_, ko, utils, Component, layout, pickupLocations) {
    'use strict';

    var defaultRendererTemplate = {
        parent: '${ $.$data.parentName }',
        name: '${ $.$data.name }',
        component:
            'Magento_InventoryInStorePickupFrontend/js/view/pickup-location-address/address-renderer/default',
    };

    return Component.extend({
        defaults: {
            template:
                'Magento_InventoryInStorePickupFrontend/pickup-location-address/list',
            visible: true,
            rendererTemplates: [],
        },

        /** @inheritdoc */
        initialize: function() {
            this._super().initChildren();

            pickupLocations.subscribe(function(locations) {
                _.each(locations, this.createRendererComponent, this);
            }, this);

            return this;
        },

        /** @inheritdoc */
        initConfig: function() {
            this._super();
            // the list of child components that are responsible for address rendering
            this.rendererComponents = [];

            return this;
        },

        /** @inheritdoc */
        initChildren: function() {
            _.each(pickupLocations(), this.createRendererComponent, this);

            return this;
        },

        /**
         * Create new component that will render given address in the address list
         *
         * @param {Object} address
         * @param {*} index
         */
        createRendererComponent: function(address, index) {
            var rendererTemplate, templateData, rendererComponent;

            if (index in this.rendererComponents) {
                this.rendererComponents[index].address(address);
            } else {
                // rendererTemplates are provided via layout
                rendererTemplate = defaultRendererTemplate;
                templateData = {
                    parentName: this.name,
                    name: index,
                };
                rendererComponent = utils.template(
                    rendererTemplate,
                    templateData
                );
                utils.extend(rendererComponent, {
                    address: ko.observable(address),
                });
                layout([rendererComponent]);
                this.rendererComponents[index] = rendererComponent;
            }
        },
    });
});
