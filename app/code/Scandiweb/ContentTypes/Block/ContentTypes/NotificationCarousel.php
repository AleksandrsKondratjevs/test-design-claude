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

class NotificationCarousel extends AbstractContentTypeTemplate
{
    protected $_template = 'Scandiweb_ContentTypes::content-type/notification-carousel/default-slider.phtml';

    /**
     * @return array
     */
    public function getSections(): array
    {
        $data = [];
        $sectionData = $this->decodeSections($this->getData('sections'));

        foreach ($sectionData as $section) {
            $data[] = [
                'text' => $section['text'],
            ];
        }

        return $data;
    }
}