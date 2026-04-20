/**
 * @category  Scandiweb
 * @package   Scandiweb_ContentTypes
 * @copyright Copyright (c) 2025 Scandiweb, Inc (https://scandiweb.com)
 * @license   http://opensource.org/licenses/OSL-3.0 The Open Software License 3.0 (OSL-3.0)
 */

define(["Magento_Ui/js/form/components/insert-form"], function (
    Insert,
    conditionsDataProcessor
) {
    "use strict";

    return Insert.extend({
        /**
         * Clear form content and reinsert data in argument
         * @param {Object} data
         */
        edit: function (data) {
            this.destroyInserted();
            this.render();
            this.setData(data);
        },

        /**
         * Set data for new form
         *
         * @param {Object} data
         */
        setData: function (data) {
            var formQuery = "index=" + this.ns,
                dataProviderQuery = "index=" + this.ns + "_data_source";

            this.providerData = data;
            this.externalForm = this.requestModule(formQuery);
            this.externalSource = this.requestModule(dataProviderQuery);
            this.setLinks(
                {
                    providerData: dataProviderQuery + ":data",
                },
                "exports"
            );
        },
    });
});