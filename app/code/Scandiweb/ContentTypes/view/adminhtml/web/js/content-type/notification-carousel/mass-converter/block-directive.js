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
        class: "Scandiweb\\ContentTypes\\Block\\ContentTypes\\NotificationCarousel",
        slider_infinitive: data.slider_infinitive,
        slider_show_arrows: data.slider_show_arrows,
        slider_count_mobile: data.slider_count_mobile,
        slider_count_tablet: data.slider_count_tablet,
        slider_count_desktop: data.slider_count_desktop,
        sections: '',
      };

      if (data.sections && data.sections !== '') {
        attributes.sections = this.encodeWysiwygCharacters(JSON.stringify(data.sections))
      }

      (0, _object.set)(data, config.html_variable, this.buildDirective(attributes));
      return data;
    };

    return BlockDirective;
  }(_blockDirectiveAbstract);

  return BlockDirective;
});
//# sourceMappingURL=carousel-block-directive.js.map