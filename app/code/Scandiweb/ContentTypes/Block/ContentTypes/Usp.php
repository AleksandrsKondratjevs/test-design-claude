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

class Usp extends AbstractContentTypeTemplate
{
    protected $appearanceTemplateMap = [
        'default_slider' => 'Scandiweb_ContentTypes::content-type/usp/default-slider.phtml',
        'default_grid' => 'Scandiweb_ContentTypes::content-type/usp/default-grid.phtml',
        'expanded_slider' => 'Scandiweb_ContentTypes::content-type/usp/expanded-slider.phtml',
        'expanded_grid' => 'Scandiweb_ContentTypes::content-type/usp/expanded-grid.phtml',
        'minimalistic_grid' => 'Scandiweb_ContentTypes::content-type/usp/minimalistic-grid.phtml',
        'minimalistic_slider' => 'Scandiweb_ContentTypes::content-type/usp/minimalistic-slider.phtml',
    ];

    /**
     * @return array
     */
    public function getSections(): array
    {
        $data = [];
        $sectionData = $this->decodeSections($this->getData('sections'));
        $sectionData = $this->contentTypeHelper->populateSectionsLinks($sectionData);

        foreach ($sectionData as $section) {
            $data[] = [
                'title' => $section['title'],
                'description' => $section['description'],
                'image' => $this->getImage($section),
                'cta_text' => $section['cta_text'],
                'url' => $section['url'],
                'open_new_tab' => $section['open_new_tab']
            ];
        }

        return $data;
    }
}