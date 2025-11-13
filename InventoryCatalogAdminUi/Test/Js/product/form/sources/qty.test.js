/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */
/* eslint-disable max-nested-callbacks */

define([
    'Magento_InventoryCatalogAdminUi/js/product/form/sources/qty'
], function (Qty) {
    'use strict'; //eslint-disable-line

    describe('Magento_InventoryCatalogAdminUi/js/product/form/sources/qty', function () {
        var model,
            isDecimal,
            params = {
                dataScope: 'dataScope'
            };

        beforeEach(function () {
            model = new Qty(params);
        });

        describe('"setDefaultValue" method', function () {
            it('Check decimal value if qty uses decimal', function () {
                isDecimal = 1;
                model.setDefaultValue(isDecimal);
                expect(model.value()).toEqual('0.0');
            });

            it('Check integer value if qty is not uses decimal', function () {
                isDecimal = 0;
                model.setDefaultValue(isDecimal);
                expect(model.value()).toEqual('0');
            });
        });
    });
});
