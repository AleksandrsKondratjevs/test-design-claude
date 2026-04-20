/*eslint-disable */
/* jscs:disable */

function _inheritsLoose(subClass, superClass) { subClass.prototype = Object.create(superClass.prototype); subClass.prototype.constructor = subClass; _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

define(["Scandiweb_ContentTypes/js/mass-converter/block-directive-abstract", "Magento_PageBuilder/js/utils/object"], function (_blockDirectiveAbstract, _object) {
  /**
   * Copyright © Magento, Inc. All rights reserved.
   * See COPYING.txt for license details.
   */

  /**
   * @api
   */
  var BlockDirective = /*#__PURE__*/function (_blockDirectiveAbstr) {
    "use strict";

    _inheritsLoose(BlockDirective, _blockDirectiveAbstr);

    function BlockDirective() {
      return _blockDirectiveAbstr.apply(this, arguments) || this;
    }

    var _proto = BlockDirective.prototype;



    /**
     * Convert value to knockout format
     *
     * @param {object} data
     * @param {object} config
     * @returns {object}
     */
    _proto.toDom = function toDom(data, config) {

      // Preview block is getting parameters and outputs template, where logic of template selection is inside of block it self.
      // Passed attributes to preview.
      var attributes = {
        name: data.name,
        class: "Scandiweb\\ContentTypes\\Block\\ContentTypes\\FeaturedBlock",
            appearance: data.appearance,
            content_description: data.content_description,
            content_title: data.content_title,
            first_cta_text: data.first_cta_text,
            first_cta_type: data.first_cta_type,
            second_cta_text: data.second_cta_text,
            second_cta_type: data.second_cta_type,
            show_first_cta: data.show_first_cta,
            show_second_cta: data.show_second_cta,
      };

      if (data.visual_content_img && data.visual_content_img !== '') {
        attributes.visual_content_img = this.encodeWysiwygCharacters(JSON.stringify(data.visual_content_img))
      }

      if (data.visual_content_mobile_img && data.visual_content_mobile_img !== '') {
        attributes.visual_content_mobile_img = this.encodeWysiwygCharacters(JSON.stringify(data.visual_content_mobile_img))
      }

      if (data.first_cta_link && data.first_cta_link !== '') {
        attributes.first_cta_link = this.encodeWysiwygCharacters(JSON.stringify(data.first_cta_link));
      }

      if (data.second_cta_link && data.second_cta_link !== '') {
        attributes.second_cta_link = this.encodeWysiwygCharacters(JSON.stringify(data.second_cta_link));
      }

      (0, _object.set)(data, config.html_variable, this.buildDirective(attributes));
      return data;
    };

    return BlockDirective;
  }(_blockDirectiveAbstract);

  return BlockDirective;
});
//# sourceMappingURL=carousel-block-directive.js.map