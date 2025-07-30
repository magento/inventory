/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */

define([
    'jquery'
], function ($) {
    'use strict'; //eslint-disable-line

    $('[data-role=toggle-editability]').on('change', function () {
        var useConfigSettings = $(this),
            field = useConfigSettings.parents('.field'),
            someEditable = $('input[type!="checkbox"], select, textarea', field);

        someEditable.prop('disabled', useConfigSettings.prop('checked'));
    });
});
