/**
 * @category  Scandiweb
 * @package   Scandiweb_ContentTypes
 * @author    Baron Gobi <info@scandiweb.com>
 * @copyright Copyright (c) 2025 Scandiweb, Inc (https://scandiweb.com)
 * @license   http://opensource.org/licenses/OSL-3.0 The Open Software License 3.0 (OSL-3.0)
 */
define([
    "Scandiweb_ContentTypes/js/content-type/block-directive",
    "Magento_PageBuilder/js/utils/directives",
    "Magento_PageBuilder/js/utils/editor",
    "Magento_PageBuilder/js/utils/object",
], function (BlockDirectiveBase, _directives, _editor, _object) {
    "use strict";
    const $super = BlockDirectiveBase.prototype;

    function BlockDirective(parent, config, stageId) {
        BlockDirectiveBase.call(this, parent, config, stageId);
    }

    BlockDirective.prototype = Object.create($super);

    var _proto = BlockDirective.prototype;

    /**
     * Convert value to internal format
     *
     * @param {object} data
     * @returns {object}
     */
    _proto.fromDom = function fromDom(data) {
        return data;
    };


    /**
     * Convert value to knockout format
     *
     * @param {object} data
     * @param {object} config
     * @returns {object}
     */
    _proto.toDom = function toDom(data, config) {
        var attributes = {
            class: config.block_class,
            ...this.getAdditionalBlockAttributes(data),
        };

        (0, _object.set)(
            data,
            config.html_variable,
            this.buildDirective(attributes)
        );


        return data;
    };

    _proto.getAdditionalBlockAttributes = function getAdditionalBlockAttributes(
        data
    ) {
        const {
            name,
            block_title,
            title_color,
            content,
            appearance,
            is_opened,
            show_button,
            desktop_disabled,
        } = data || {};

        const parsedHtml = this.encodeWysiwygCharacters(content).replace(/"/g, "&quot;");

        var attributes = {
            name,
            block_title,
            title_color,
            content: parsedHtml,
            appearance,
            is_opened,
            show_button,
            desktop_disabled,
        };

        return attributes;
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
     * @param {string} content
     * @returns {string}
     */

    return BlockDirective;
});
