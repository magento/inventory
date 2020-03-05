/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery'
], function ($) {
    'use strict';

    $('[data-role=toggle-editability]').change(function () {
        var useConfigSettings = $(this),
            field = useConfigSettings.parents('.field'),
            someEditable = $('input[type!="checkbox"], select, textarea', field);

        someEditable.prop('disabled', useConfigSettings.prop('checked'));
    });
});
