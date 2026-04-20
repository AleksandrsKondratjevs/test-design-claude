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
    var Object = /*#__PURE__*/ (function () {
        "use strict";

        function Object() {}

        var _proto = Object.prototype;

        /**
         * Convert value to internal format
         *
         * @param value string
         * @returns {string | object}
         */
        _proto.fromDom = function fromDom(value) {
            if (value && value !== "") {
                return JSON.parse(value);
            }

            return [];
        };

        /**
         * Convert value to knockout format
         *
         * @param name string
         * @param data Object
         * @returns {string | object}
         */
        _proto.toDom = function toDom(name, data) {
            var content = (0, _object.get)(data, name);

            if (_underscore.isString(content) && content !== "") {
                content = JSON.parse(content);
                return JSON.stringify(content);
            }

            return JSON.stringify(content);
        };

        return Object;
    })();

    return Object;
});
//# sourceMappingURL=Object.js.map
