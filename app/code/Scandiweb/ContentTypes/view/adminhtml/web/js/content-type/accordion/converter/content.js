/*eslint-disable */
/* jscs:disable */
define(["underscore", "Magento_PageBuilder/js/utils/object"], function (
    _underscore,
    _object
) {
    /**
     * @category  Scandiweb
     * @package   Scandiweb_ContentTypes
     * @author    Aleksandrs Kondratjevs <info@scandiweb.com>
     * @copyright Copyright (c) 2025 Scandiweb, Inc (https://scandiweb.com)
     * @license   http://opensource.org/licenses/OSL-3.0 The Open Software License 3.0 (OSL-3.0)
     */
    var Content = /*#__PURE__*/ (function () {
        "use strict";

        function Content() {}

        var _proto = Content.prototype;

        /**
         * Convert value to internal format
         *
         * @param value string
         * @returns {string | object}
         */
        _proto.fromDom = function fromDom(value) {
            return this.decodeWysiwygCharacters(value);
        };
        /**
         * Convert value to knockout format
         *
         * @param name string
         * @param data Object
         * @returns {string | object}
         */

        _proto.toDom = function toDom(name, data) {
            var content = this.encodeWysiwygCharacters((0, _object.get)(data, name));

            return content;
        };


        /**
         * @param {string} content
         * @returns {string}
         */
        _proto.encodeWysiwygCharacters = function encodeWysiwygCharacters(content) {
            return content.replace(/\{/g, "^[").replace(/\}/g, "^]").replace(/"/g, "`").replace(/\\/g, "|").replace(/</g, "&lt;").replace(/>/g, "&gt;");
        };

        /**
         * @param {string} content
         * @returns {string}
         */
        _proto.decodeWysiwygCharacters = function decodeWysiwygCharacters(content) {
            return content.replace(/\^\[/g, "{").replace(/\^\]/g, "}").replace(/`/g, "\"").replace(/\|/g, "\\").replace(/&lt;/g, "<").replace(/&gt;/g, ">");
        };

        return Content;
    })();

    return Content;
});
//# sourceMappingURL=Content.js.map
