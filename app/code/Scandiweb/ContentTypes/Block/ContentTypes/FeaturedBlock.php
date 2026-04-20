<?php
/**
 * @category  Scandiweb
 * @package   Scandiweb_ContentTypes
 * @copyright Copyright (c) 2025 Scandiweb, Inc (https://scandiweb.com)
 * @license   http://opensource.org/licenses/OSL-3.0 The Open Software License 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Scandiweb\ContentTypes\Block\ContentTypes;

use Scandiweb\ContentTypes\Block\ContentTypes\AbstractContentTypeTemplate;

class FeaturedBlock extends AbstractContentTypeTemplate
{
    protected $_template = 'Scandiweb_ContentTypes::content-type/featured-block.phtml';

    public function getImage($type = 'desktop')
    {
        $dataKey = $type === 'mobile' ? 'visual_content_mobile_img' : 'visual_content_img';
        $img = $this->getData($dataKey);

        if (!$img) {
            return null;
        }

        $img = $this->decodeSections($img);

        if (!isset($img[0])) {
            return null;
        }

        return [
            'url' => $img[0]['url'],
            'alt' => $img[0]['name']
        ];
    }

    public function getImages(): array
    {
        $image = $this->getImage('mobile');
        $desktopImage = null;

        $desktopImageData = $this->getImage('desktop');
        if ($desktopImageData) {
            $desktopImage = $desktopImageData;
        }

        if (!$image && $desktopImage) {
            $image = $desktopImage;
            $desktopImage = null;
        }

        return [
            'image' => $image,
            'desktopImage' => $desktopImage
        ];
    }
}