define(["Scandiweb_ContentTypes/js/content-type/block-directive"], function (
    BlockDirectiveBase
) {
    "use strict";
    const $super = BlockDirectiveBase.prototype;

    function BlockDirective(parent, config, stageId) {
        BlockDirectiveBase.call(this, parent, config, stageId);
    }

    BlockDirective.prototype = Object.create($super);

    var _proto = BlockDirective.prototype;

    _proto.getAdditionalBlockAttributes = function getAdditionalBlockAttributes(
        data
    ) {
        const { delivery_text, cta_text, cta_link, delivery_image } =
            data || {};

        var attributes = {
            delivery_text,
            cta_text,
            cta_link,
            delivery_image: delivery_image
                ? JSON.stringify(delivery_image)
                : null,
        };

        return attributes;
    };

    return BlockDirective;
});
