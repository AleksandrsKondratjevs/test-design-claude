/*eslint-disable */
/* jscs:disable */
define(["underscore", "Magento_PageBuilder/js/utils/object"], function (_underscore, _object) {
  /**
   * Copyright © Magento, Inc. All rights reserved.
   * See COPYING.txt for license details.
   */
  var BlockDirectiveAbstract = /*#__PURE__*/function () {
    "use strict";

    function BlockDirectiveAbstract() { }

    var _proto = BlockDirectiveAbstract.prototype;

    /**
     * Convert value to internal format
     *
     * @param {object} data
     * @param {object} config
     * @returns {object}
     */
    _proto.fromDom = function fromDom(data, config) {
      return data;
    };

    /**
     * Convert value to knockout format
     *
     * @param {ConverterDataInterface} data
     * @param {ConverterConfigInterface} config
     * @returns {object}
     */
    _proto.toDom = function toDom(data, config) {
      (0, _object.set)(data, config.html_variable, this.buildDirective(data));
      return data;
    };

    /**
     * Build the directive string using the supplies attributes
     *
     * @param {object} attributes
     * @returns {string}
     */
    _proto.buildDirective = function buildDirective(attributes) {
      return "{{block " + this.createAttributesString(attributes) + "}}";
    };

    /**
     * @param {string} attributes
     * @return {Object}
     */
    _proto.parseAttributesString = function parseAttributesString(attributes) {
      var result = {};
      attributes.replace(/(\w+)(?:\s*=\s*(?:(?:"((?:\\.|[^"])*)")|(?:'((?:\\.|[^'])*)')|([^>\s]+)))?/g, function (match, key, value) {
        result[key] = value.replace(/&quot;/g, "\"");
        return "";
      });
      return result;
    };

    /**
     * @param {Object} attributes
     * @return {string}
     */
    _proto.createAttributesString = function createAttributesString(attributes) {
      var result = "";

      _underscore.each(attributes, function (value, name) {
        result += name + "=" + String(JSON.stringify(value)).replace(/"/g, "&quot;") + " ";
      });

      return result.substr(0, result.length - 1);
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

    /**
     * Decode html special characters
     *
     * @param {string} content
     * @returns {string}
     */
    _proto.decodeHtmlCharacters = function decodeHtmlCharacters(content) {
      if (content) {
        var htmlDocument = new DOMParser().parseFromString(content, "text/html");
        return htmlDocument.body ? htmlDocument.body.textContent : content;
      }

      return content;
    };

    return BlockDirectiveAbstract;
  }();

  return BlockDirectiveAbstract;
});
//# sourceMappingURL=block-directive-abstract.js.map