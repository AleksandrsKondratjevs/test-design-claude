/*eslint-disable */
/* jscs:disable */

define([
    'Magento_PageBuilder/js/content-type/tabs/preview',
    "jquery",
    "knockout",
    "mage/translate",
    "Magento_PageBuilder/js/events",
    "underscore",
    "Magento_PageBuilder/js/config",
    "Magento_PageBuilder/js/content-type-factory",
    "Magento_PageBuilder/js/utils/delay-until",
    "Magento_PageBuilder/js/content-type/preview-collection"
], function (
    PreviewBase,
    _jquery,
    _knockout,
    _translate,
    _events,
    _underscore,
    _config,
    _contentTypeFactory,
    _delayUntil,
    _previewCollection
) {
    'use strict';

    function Preview(contentType, config, observableUpdater) {
        const base = PreviewBase.call(this, contentType, config, observableUpdater);

        var _this = this;

        _events.on("scandiweb_tabs_with_image:mountAfter", function (args) {
            if (args.contentType.id === _this.contentType.id && args.expectChildren !== undefined) {
                base.mountAfterDeferred.resolve(args.expectChildren);
            }
        });

        _events.on("scandiweb_tab_with_image_item:mountAfter", function (args) {
            if (_this.element && args.contentType.parentContentType.id === _this.contentType.id) {
                base.refreshTabs();
            }
        });

        _events.on("scandiweb_tab_with_image_item:renderAfter", function (args) {
            if (_this.element && args.contentType.parentContentType.id === _this.contentType.id) {
                _underscore.defer(function () {
                    base.refreshTabs();
                });
            }
        });

        _events.on("scandiweb_tab_with_image_item:removeAfter", function (args) {
            if (args.parentContentType && args.parentContentType.id === _this.contentType.id) {
                base.refreshTabs();
                _underscore.defer(function () {
                    var newPosition = args.index > 0 ? args.index - 1 : 0;
                    base.setFocusedTab(newPosition, true);
                });
            }
        });

        return _this;
    }

    Preview.prototype = Object.create(PreviewBase.prototype);
    Preview.prototype.constructor = Preview;

    Object.assign(Preview.prototype, {
        addTab: function () {
            var _this4 = this;
            (0, _contentTypeFactory)(_config.getContentTypeConfig("scandiweb_tab_with_image_item"), this.contentType, this.contentType.stageId).then(function (tab) {
                _events.on("scandiweb_tab_with_image_item:mountAfter", function (args) {
                    if (args.id === tab.id) {
                        _this4.setFocusedTab(_this4.contentType.children().length - 1);
                        _events.off("scandiweb_tab_with_image_item:" + tab.id + ":mountAfter");
                    }
                }, "scandiweb_tab_with_image_item:" + tab.id + ":mountAfter");

                _this4.contentType.addChild(tab, _this4.contentType.children().length);
                tab.dataStore.set("tab_name", (0, _translate)("Tab") + " " + (_this4.contentType.children.indexOf(tab) + 1));
            });
        },

        getTabHeaderStyles: function (index = 0) {
            var headerStyles = this.data.headers.style();
            var backgroundStyles = this.data.background.style();
            var styles = _extends({}, headerStyles, {
                marginBottom: "-" + headerStyles.borderWidth,
                marginLeft: "-" + headerStyles.borderWidth
            });

            if (index === this.activeTab()) {
                return _extends({}, styles, {
                    backgroundColor: backgroundStyles.backgroundColor
                });
            }

            return styles;
        }
    });

    return Preview;
});
