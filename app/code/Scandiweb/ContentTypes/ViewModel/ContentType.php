<?php
/**
 * @category  Scandiweb
 * @package   Scandiweb_ContentTypes
 * @copyright Copyright (c) 2025 Scandiweb, Inc (https://scandiweb.com)
 * @license   http://opensource.org/licenses/OSL-3.0 The Open Software License 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Scandiweb\ContentTypes\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollection;
use Magento\Catalog\Helper\Category as CategoryHelper;
use Magento\Catalog\Helper\Product as ProductHelper;
use Magento\Cms\Helper\Page as PageHelper;
use Magento\Framework\Serialize\Serializer\Json;

// Helper for content types logic
class ContentType implements ArgumentInterface {
    /**
     * @param Context $context
     * @param CategoryCollection $categoryCollection
     * @param CategoryListInterface $categoryList
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param array $data
     */
    public function __construct(
        protected CategoryCollection $categoryCollection,
        protected ProductCollection $productCollection,
        protected CategoryHelper $categoryHelper,
        protected ProductHelper $productHelper,
        protected PageHelper $pageHelper,
        protected Json $serializer,
    ) {
        $this->categoryCollection = $categoryCollection;
        $this->productCollection = $productCollection;
        $this->categoryHelper = $categoryHelper;
        $this->productHelper = $productHelper;
        $this->pageHelper = $pageHelper;
         $this->serializer = $serializer;
    }

    public function decodeSections($sections) {
        if (!$sections) {
            return [];
        }

        $sectionData = str_replace('&amp;quote;', '"', $sections);
        $sectionData = $this->decodeWysiwygCharacters($sectionData);
        $sectionData = $this->serializer->unserialize($sectionData);

        return $sectionData;
    }

    public function decodeWysiwygCharacters($content) {
        $content = str_replace("^[", "{", $content);
        $content = str_replace("^]", "}", $content);
        $content = str_replace("`", "\"", $content);
        $content = str_replace("|", "\\", $content);
        $content = str_replace("&lt;", "<", $content);
        $content = str_replace("&gt;", ">", $content);

        return $content;
    }

    public function getSingleLink($link) {
        $link = $this->decodeSections($link);

        $resultLink = [
            'url' => '',
            'open_new_tab' => false
        ];

        switch ($link['type']) {
            case 'category':
                if (!empty($link['category'])) {
                    $collection = $this->categoryCollection->create()->addFieldToSelect('url_path')->addFieldToFilter('entity_id', ['in' => $link['category']]);
                    $category = $collection->getFirstItem();
                    $resultLink['url'] = $this->categoryHelper->getCategoryUrl($category);
                }

                break;
            case 'product':
                if (!empty($link['product'])) {
                    $resultLink['url'] = $this->productHelper->getProductUrl($link['product']);
                }

                break;
            case 'page':
                if (!empty($link['page'])) {
                    $resultLink['url'] = $this->pageHelper->getPageUrl($link['page']);
                }

                break;
            case 'default':
                $resultLink['url'] = $link['default'];
                break;
        }

        $resultLink['open_new_tab'] = $link['setting'];

        return $resultLink;
    }

    public function populateSectionsLinks($sections) {
        $categoryIDs = [];
        $productIDs = [];
        $categoryUrls = [];
        $productUrls = [];
        $data = [];

        foreach ($sections as $section) {
            switch ($section['link_url']['type']) {
                case 'category':
                    $categoryIDs[] = (int) $section['link_url']['category'];
                    break;
                case 'product':
                    $productIDs[] = (int) $section['link_url']['product'];
                    break;
            }
        }

        if (count($categoryIDs)) {
            $collection = $this->categoryCollection->create()->addFieldToSelect('url_path')->addFieldToFilter('entity_id', ['in' => $categoryIDs]);
            $categoryUrls = $collection->getItems();
        }

        if (count($productIDs)) {
            $collection = $this->productCollection->create()->addFieldToSelect('url_path')->addFieldToFilter('entity_id', ['in' => $productIDs]);
            $productUrls = $collection->getItems();
        }

        foreach ($sections as $section) {
            switch ($section['link_url']['type']) {
                case 'category':
                    if (!empty($section['link_url']['category'])) {
                        $section['url'] = $this->categoryHelper->getCategoryUrl($categoryUrls[$section['link_url']['category']]);
                    }

                    break;
                case 'product':
                    if (!empty($section['link_url']['product'])) {
                        $section['url'] = $this->productHelper->getProductUrl($productUrls[$section['link_url']['product']]);
                    }

                    break;
                case 'page':
                    if (!empty($section['link_url']['page'])) {
                        $section['url'] = $this->pageHelper->getPageUrl($section['link_url']['page']);
                    }

                    break;
                case 'default':
                    $section['url'] = $section['link_url']['default'];
                    break;
            }

            $section['open_new_tab'] = $section['link_url']['setting'];
            $data[] = $section;
        }

        return $data;
    }
}