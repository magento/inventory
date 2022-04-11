/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'underscore',
    'Magento_Ui/js/dynamic-rows/dynamic-rows-grid'
], function (_, dynamicRowsGrid) {
    'use strict';

    return dynamicRowsGrid.extend({
        defaults: {
            cacheGridDataIndex: []
        },

        /**
         * Performance optimization of setting initial property to records data of only first page
         *
         * @returns {Object} Chainable.
         */
        setInitialProperty: function () {
            var startIndex,
                stopIndex;

            if (_.isArray(this.recordData())) {
                startIndex = (~~this.currentPage() - 1) * this.pageSize;
                stopIndex = startIndex + parseInt(this.pageSize, 10);
                this.recordData.each(function (data, index) {
                    if (index < stopIndex) {
                        this.source.set(this.dataScope + '.' + this.index + '.' + index + '.initialize', true);
                    }
                }, this);
            }

            return this;
        },

        /**
         * Performance optimization of checking changed records
         * skip when checks are not necessary
         *
         * @param {Array} data - array with records data
         * @returns {Array} Changed records
         */
        _checkGridData: function (data) {
            var cacheLength = this.cacheGridData.length,
                curData = data.length,
                changes = [],
                dataIndex = [],
                changesIndex = [];

            if (cacheLength === curData || cacheLength > curData) {
                return [];
            }

            if (!cacheLength) {
                return data;
            }
            data.forEach(function (record, index) {
                dataIndex[index] = record[this.map[this.identificationDRProperty]];
            }, this);
            changesIndex = _.difference(dataIndex, this.cacheGridDataIndex);
            changesIndex.forEach(function (changeIndex) {
                data.forEach(function (record, index) {
                    if (changeIndex === record[this.map[this.identificationDRProperty]]) {
                        changes.push(data[index]);
                    }
                }, this);
            }, this);

            return changes;
        },

        /**
         * Performance optimization of processing insert data
         *
         * @param {Object} data
         */
        processingInsertData: function (data) {
            this._super(data);
            data.forEach(function (record, index) {
                this.cacheGridDataIndex[index] = record[this.map[this.identificationDRProperty]];
            }, this);
        }
    });
});
