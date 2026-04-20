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

class FeaturedCategories extends AbstractContentTypeTemplate
{
    protected $appearanceTemplateMap = [
        'default_slider' => 'Scandiweb_ContentTypes::content-type/featured-categories/default-slider.phtml',
        'default_grid' => 'Scandiweb_ContentTypes::content-type/featured-categories/default-grid.phtml',
    ];

    /**
     * @return array
     */
    public function getSections(): array
    {
        $data = [];

        $sectionData = $this->decodeSections($this->getData(('sections')));
        $sectionData = $this->contentTypeHelper->populateSectionsLinks($sectionData);

        foreach ($sectionData as $section) {
            $data[] = [
                'title' => $section['title'],
                'image' => $this->getImage($section),
                'url' => $section['url'],
                'open_new_tab' => $section['open_new_tab']
            ];
        }

        return $data;
    }
}
