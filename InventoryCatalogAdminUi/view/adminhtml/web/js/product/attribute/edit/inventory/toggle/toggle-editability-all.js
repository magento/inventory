/**
 * Copyright 2020 Adobe
 * All Rights Reserved.
 */

define([
    'jquery'
], function ($) {
    'use strict'; //eslint-disable-line

    $('[data-role=toggle-editability-all]').on('change', function () {
        var toggler = $(this),
            field = toggler.parents('.field'),
            someEditable = $('input[type!="checkbox"], select, textarea', field),
            someEditableCheckboxes = $('input[type="checkbox"]', field).not(toggler);

        if (someEditableCheckboxes.length) {
            someEditable.prop('disabled', !toggler.prop('checked') || someEditableCheckboxes.prop('checked'));
            someEditableCheckboxes.prop('disabled', !toggler.prop('checked'));
        } else {
            someEditable.prop('disabled', !toggler.prop('checked'));
        }
    });
});
