<?php

/**
 * @category  Scandiweb
 * @package   Scandiweb_ContentTypes
 * @author    Baron Gobi <info@scandiweb.com>
 * @copyright Copyright (c) 2025 Scandiweb, Inc (https://scandiweb.com)
 * @license   http://opensource.org/licenses/OSL-3.0 The Open Software License 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Scandiweb\ContentTypes\Block\ContentTypes;

use Scandiweb\ContentTypes\Helper\ContentType as ContentTypeHelper;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Widget\Block\BlockInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Serialize\Serializer\Json;

class AbstractContentTypeTemplate extends Template implements BlockInterface {

    /**
     * @param Context $context
     * @param Escaper $escaper
     * @param Json $serializer
     * @param ContentTypeHelper $contentTypeHelper
     * @param array $data
     */
    public function __construct(
        protected Context $context,
        protected Escaper $escaper,
        protected Json $serializer,
        protected ContentTypeHelper $contentTypeHelper,
        array $data = []
    ) {
        $this->escaper = $escaper;
        $this->serializer = $serializer;
        $this->contentTypeHelper = $contentTypeHelper;

        parent::__construct($context, $data);
    }

    public function getSingleLink($link) {
        return $this->contentTypeHelper->getSingleLink($this->decodeSingleLink($link));
    }

    public function decodeSingleLink($link) {
        $linkData = str_replace('&amp;quote;', '"', $link);
        $linkData = $this->decodeWysiwygCharacters($linkData);
        $linkData = $this->serializer->unserialize($linkData);

        return $linkData;
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

    public function decodeSections($sections) {
        if (!$sections) {
            return [];
        }

        $sectionData = str_replace('&amp;quote;', '"', $sections);
        $sectionData = $this->decodeWysiwygCharacters($sectionData);
        $sectionData = $this->serializer->unserialize($sectionData);

        $sectionData = $this->sortSections($sectionData);

        return $sectionData;
    }

    public function sortSections($sections) {
        if (!$sections) {
            return [];
        }

        try {
            usort($sections, function($a, $b) {
                return $a['position'] <=> $b['position'];
            });
        } catch (\Exception $e) {
        }

        return $sections;
    }

    public function getTemplate()
    {
        $appearance = $this->getData('appearance');
        $appearanceType = $this->getData('appearance_type');
        $combinedAppearance = $appearance . '_' . $appearanceType;

        if (!isset($this->appearanceTemplateMap[$combinedAppearance])) {
            return $this->_template;
        }

        return $this->appearanceTemplateMap[$combinedAppearance];
    }

    public function getImage($usp) {
        if (!isset($usp['image']) || count($usp['image']) <= 0) {
            return null;
        }

        return $usp['image'][0]['url'];
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->getTemplate()) {
            return '';
        }

        $isPreview = $this->getData('preview');

        if ($isPreview) {
            $result = $this->fetchView($this->getTemplateFile());
            $swiperSlider = $this->fetchView($this->getTemplateFile('Scandiweb_ContentTypes::swiper-slider.phtml'));
            $alpineJs = $this->fetchView($this->getTemplateFile('Hyva_Theme::page/js/alpinejs.phtml'));
            $iframeId = uniqid();

            $doc = <<<EOT
                <!doctype html>
                <html>
                <head>
                    <link  rel="stylesheet" type="text/css"  media="all" href="{$this->getViewFileUrl('css/styles.css')}"/>
                    {$alpineJs}
                </head>
                <body style="overflow-x: hidden; background: transparent;">
                    <div data-content-type="{$this->getRole()}">{$result}</div>
                    {$swiperSlider}
                    <script>
                        document.querySelectorAll('a').forEach(function(link) {
                            link.addEventListener('click', function(event) {
                                event.preventDefault();
                            });
                        });
                    </script>
                </body>
                </html>
            EOT;

            return <<<EOT
                <iframe id="{$this->escaper->escapeHtmlAttr($iframeId)}"
                        srcdoc="{$this->escaper->escapeHtmlAttr($doc)}"
                        style="width: 100%; border: 0;"></iframe>
                <script>
                (() => {
                // update the iframe height to match the content
                    const iframe = document.getElementById('{$iframeId}');

                    const resizeIframe = () => {
                        setTimeout(() => {
                            const iframe = document.getElementById('{$iframeId}');
                            const doc = iframe.contentWindow.document;
                            const height = Math.max(doc.body.scrollHeight, doc.documentElement.scrollHeight);
                            iframe.style.height = height + 15 + 'px';

                            document.querySelectorAll('a').forEach(function(link) {
                                link.addEventListener('click', function(event) {
                                    event.preventDefault();
                                });
                            });
                        }, 500);
                    }

                    iframe.addEventListener('load', () => {
                        // wait until the iframe contents are done rendering.
                        setTimeout(() => {
                            resizeIframe();
                        }, 50);

                        var iframeDoc = iframe.contentWindow.document;
                        var observer = new MutationObserver(resizeIframe);
                        observer.observe(iframeDoc.body, { childList: true, subtree: true, characterData: true });
                    });
                    })()
                </script>
            EOT;
        }

        return $this->fetchView($this->getTemplateFile());
    }
}