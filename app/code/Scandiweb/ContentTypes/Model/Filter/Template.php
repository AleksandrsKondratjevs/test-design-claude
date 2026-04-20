<?php

/**
 * @category  Scandiweb
 * @package   Scandiweb_ContentTypes
 * @copyright Copyright (c) 2025 Scandiweb, Inc (https://scandiweb.com)
 * @license   http://opensource.org/licenses/OSL-3.0 The Open Software License 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Scandiweb\ContentTypes\Model\Filter;

use DOMDocument;
use DOMElement;
use DOMException;
use DOMXPath;
use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Math\Random;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\ConfigInterface;
use Magento\PageBuilder\Plugin\Filter\TemplatePlugin;
use Psr\Log\LoggerInterface;
use Magento\PageBuilder\Model\Filter\Template as TemplateParent;

/**
 * Specific template filters for Page Builder content
 * Override this class to add custom functionality to image background
 * Had to override the whole class due to private functions everywhere
 */
class Template extends TemplateParent
{
    /**
     * @var ConfigInterface
     */
    private $viewConfig;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DOMDocument
     */
    private $domDocument;

    /**
     * @var Random
     */
    private $mathRandom;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var array
     */
    private $scripts;


    /**
     * @param LoggerInterface $logger
     * @param ConfigInterface $viewConfig
     * @param Random $mathRandom
     * @param Json $json
     */
    public function __construct(
        LoggerInterface $logger,
        ConfigInterface $viewConfig,
        Random $mathRandom,
        Json $json,
    ) {
        $this->logger = $logger;
        $this->viewConfig = $viewConfig;
        $this->mathRandom = $mathRandom;
        $this->json = $json;
    }

    /**
     * After filter of template data apply transformations
     *
     * @param string $result
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function filter(string $result): string
    {
        $this->domDocument = false;
        $this->scripts = [];

        // Validate if the filtered result requires background image processing
        if (preg_match(TemplatePlugin::BACKGROUND_IMAGE_PATTERN, $result)) {
            $document = $this->getDomDocument($result);
            $this->generateBackgroundImageStyles($document);
        }

        // render video element for data-background-video pagebuilder elements
        if (preg_match('/data-background-type="video"/si', $result)) {
            $document = $this->getDomDocument($result);
            $this->renderVideoElements($document);
        }

        // Process any HTML content types, they need to be decoded on the front-end
        if (preg_match(TemplatePlugin::HTML_CONTENT_TYPE_PATTERN, $result)) {
            $document = $this->getDomDocument($result);
            $uniqueNodeNameToDecodedOuterHtmlMap = $this->generateDecodedHtmlPlaceholderMappingInDocument($document);
        }

        if (preg_match('/data-element="overlay"/si', $result)) {
            $document = $this->getDomDocument($result);
            $this->formatOverlayElement($document);
        }

        // If a document was retrieved we've modified the output so need to retrieve it from within the document
        if (isset($document)) {
            // Match the contents of the body from our generated document
            preg_match(
                '/<body>(.+)<\/body><\/html>$/si',
                $document->saveHTML(),
                $matches
            );

            if (!empty($matches)) {
                $docHtml = $matches[1];

                // restore any encoded directives
                $docHtml = preg_replace_callback(
                    '/=\"(%7B%7B[^"]*%7D%7D)\"/m',
                    function ($matches) {
                        return urldecode($matches[0]);
                    },
                    $docHtml
                );

                if (isset($uniqueNodeNameToDecodedOuterHtmlMap)) {
                    foreach ($uniqueNodeNameToDecodedOuterHtmlMap as $uniqueNodeName => $decodedOuterHtml) {
                        $docHtml = str_replace(
                            '<' . $uniqueNodeName . '>' . '</' . $uniqueNodeName . '>',
                            $decodedOuterHtml,
                            $docHtml
                        );
                    }
                }

                $result = $docHtml;
            }

            $result = $this->unmaskScriptTags($result);
        }

        return $result;
    }

    private function formatOverlayElement(DOMDocument $document): void
    {
        $xpath = new DOMXPath($document);
        $nodes = $xpath->query('//*[@data-element="overlay"]');

        foreach ($nodes as $node) {
            // Create a new wrapper div element
            $wrapper = $document->createElement('div');
            $wrapper->setAttribute('class', 'pagebuilder-overlay-wrapper');

            // Clone the node and append it to the wrapper
            $clonedNode = $node->cloneNode(true);
            $wrapper->appendChild($clonedNode);

            // Replace the original node with the wrapped node
            if ($node->parentNode) {
                $node->parentNode->replaceChild($wrapper, $node);
            }
        }
    }

    /**
     * Generate <iframe> elements for data-background-type="video" pagebuilder elements
     * Supports Vimeo, YouTube, and direct video URLs
     *
     * @param DOMDocument $document
     */
    private function renderVideoElements(DOMDocument $document): void
    {
        $xpath = new DOMXPath($document);
        $nodes = $xpath->query('//*[@data-background-type="video"]');

        foreach ($nodes as $index => $node) {
            if ($node->getAttribute('data-video-ready') === 'true') {
                continue;
            }

            $nodeClasses = explode(' ', $node->getAttribute('class')) ?? [];
            $isSliderVideo = in_array('pagebuilder-slide-wrapper', $nodeClasses);

            /* @var DOMElement $node */
            $videoSrc = $node->attributes->getNamedItem('data-video-src');
            $mobileVideoSrc = $node->attributes->getNamedItem('data-video-src-mobile');
            $wideVideoSrc = $node->attributes->getNamedItem('data-video-src-wide');
            $mainVideoSrc = $videoSrc->nodeValue ?? $wideVideoSrc->nodeValue ?? $mobileVideoSrc->nodeValue;

            $fallbackMobileImageSrc = $node->attributes->getNamedItem('data-video-fallback-src-mobile');
            $fallbackWideImageSrc = $node->attributes->getNamedItem('data-video-fallback-src-wide');
            $fallbackImageSrc = $node->attributes->getNamedItem('data-video-fallback-src');
            $mainFallbackImageSrc = $fallbackImageSrc->nodeValue ?? $fallbackWideImageSrc->nodeValue ?? $fallbackMobileImageSrc->nodeValue;

            $videoContainer = $xpath->document->createElement('div');
            $videoContainer->setAttribute('class', 'pagebuilder-video-container');

            // Check if it's a video platform URL (Vimeo, YouTube) or direct video file
            // Create iframe for video platforms
            $iframeElement = $this->createVideoIframe($xpath->document, $mainVideoSrc, $index, $isSliderVideo);
            $videoContainer->appendChild($iframeElement);

            $node->appendChild($videoContainer);
            $node->setAttribute('data-video-ready', 'true');
        }
    }

    /**
     * Create iframe element for video platforms
     *
     * @param DOMDocument $document
     * @param string $videoSrc
     * @param int $index
     * @param bool $isSliderVideo
     * @return DOMElement
     */
    private function createVideoIframe(DOMDocument $document, string $videoSrc, int $index, bool $isSliderVideo): DOMElement
    {
        $iframeElement = $document->createElement('iframe');

        $iframeElement->setAttribute('src', $videoSrc);
        $iframeElement->setAttribute('width', '100%');
        $iframeElement->setAttribute('height', '100%');
        $iframeElement->setAttribute('frameborder', '0');
        $iframeElement->setAttribute('allow', 'autoplay; fullscreen; picture-in-picture');
        $iframeElement->setAttribute('allowfullscreen', 'true');
        $iframeElement->setAttribute('class', 'pagebuilder-video-iframe');

        if ($index === 0 && $isSliderVideo) {
            $iframeElement->setAttribute('onload', '');
        } else {
            $iframeElement->setAttribute('onload', 'onPagebuilderVideoLoaded(this)');
        }

        return $iframeElement;
    }

    /**
     * Create regular video element for direct video files
     *
     * @param DOMDocument $document
     * @param string $mainVideoSrc
     * @param DOMNamedNodeMap|null $mobileVideoSrc
     * @param DOMNamedNodeMap|null $wideVideoSrc
     * @param string $fallbackImageSrc
     * @param int $index
     * @param bool $isSliderVideo
     * @return DOMElement
     */
    private function createVideoElement(DOMDocument $document, string $mainVideoSrc, $mobileVideoSrc, $wideVideoSrc, string $fallbackImageSrc, int $index, bool $isSliderVideo): DOMElement
    {
        $videoElement = $document->createElement('video');

        // Append mobile video source to video tag
        if ($mobileVideoSrc && $mobileVideoSrc->nodeValue) {
            $sourceElement = $document->createElement('source');
            $sourceElement->setAttribute('src', $mobileVideoSrc->nodeValue ?? $mainVideoSrc);
            $sourceElement->setAttribute('type', 'video/webm');
            $sourceElement->setAttribute('media', '(max-width: 768px)');
            $videoElement->appendChild($sourceElement);
        }

        // Append wide video source to video tag
        if ($wideVideoSrc && $wideVideoSrc->nodeValue) {
            $sourceElement = $document->createElement('source');
            $sourceElement->setAttribute('src', $wideVideoSrc->nodeValue ?? $mainVideoSrc);
            $sourceElement->setAttribute('type', 'video/webm');
            $sourceElement->setAttribute('media', '(min-width: 1600px)');
            $videoElement->appendChild($sourceElement);
        }

        // Append main video source to video tag
        $sourceElement = $document->createElement('source');
        $sourceElement->setAttribute('src', $mainVideoSrc);
        $sourceElement->setAttribute('type', 'video/webm');
        $videoElement->appendChild($sourceElement);

        $videoElement->setAttribute('autoplay', 'true');
        $videoElement->setAttribute('playsinline', 'true');
        $videoElement->setAttribute('muted', 'true');
        $videoElement->setAttribute('loop', 'true');
        $videoElement->setAttribute('class', 'pagebuilder-video');

        if ($index === 0 && $isSliderVideo) {
            $videoElement->setAttribute('oncanplay', '');
        } else {
            $videoElement->setAttribute('oncanplay', 'onPagebuilderVideoLoaded(this)');
        }

        return $videoElement;
    }

    /**
     * Create a DOM document from a given string
     *
     * @param string $html
     *
     * @return DOMDocument
     */
    private function getDomDocument(string $html): DOMDocument
    {
        if (!$this->domDocument) {
            $this->domDocument = $this->createDomDocument($html);
        }

        return $this->domDocument;
    }

    /**
     * Create a DOMDocument from a string
     *
     * @param string $html
     *
     * @return DOMDocument
     */
    private function createDomDocument(string $html): DOMDocument
    {
        $html = $this->maskScriptTags($html);

        $domDocument = new DOMDocument('1.0', 'UTF-8');
        set_error_handler(
            function ($errorNumber, $errorString) {
                throw new DOMException($errorString, $errorNumber);
            }
        );
        $convmap = [0x80, 0x10FFFF, 0, 0x1FFFFF];
        $string = mb_encode_numericentity(
            $html,
            $convmap,
            'UTF-8'
        );
        try {
            libxml_use_internal_errors(true);
            // LIBXML_SCHEMA_CREATE option added according to this message
            // https://stackoverflow.com/a/66473950/773018
            // Its need to avoid bug described in maskScriptTags()
            // https://bugs.php.net/bug.php?id=52012
            $domDocument->loadHTML(
                '<html><body>' . $string . '</body></html>',
                LIBXML_SCHEMA_CREATE
            );
            libxml_clear_errors();
        } catch (Exception $e) {
            restore_error_handler();
            $this->logger->critical($e);
        }
        restore_error_handler();

        return $domDocument;
    }

    /**
     * Convert encoded HTML content types to placeholders and generate decoded outer html map for future replacement
     *
     * @param DOMDocument $document
     * @return array
     * @throws LocalizedException
     */
    private function generateDecodedHtmlPlaceholderMappingInDocument(DOMDocument $document): array
    {
        $xpath = new DOMXPath($document);

        // construct xpath query to fetch top-level ancestor html content type nodes
        /** @var $htmlContentTypeNodes DOMNode[] */
        $htmlContentTypeNodes = $xpath->query(
            '//*[@data-content-type="html" and not(@data-decoded="true")]' .
                '[not(ancestor::*[@data-content-type="html"])]'
        );

        $uniqueNodeNameToDecodedOuterHtmlMap = [];

        foreach ($htmlContentTypeNodes as $htmlContentTypeNode) {
            // Set decoded attribute on all encoded html content types so we don't double decode;
            $htmlContentTypeNode->setAttribute('data-decoded', 'true');

            // if nothing exists inside the node, continue
            if (!strlen(trim($htmlContentTypeNode->nodeValue))) {
                continue;
            }

            // clone html code content type to save reference to its attributes/outerHTML, which we are not going to
            // decode
            $clonedHtmlContentTypeNode = clone $htmlContentTypeNode;

            // clear inner contents of cloned node for replacement later with $decodedInnerHtml using sprintf;
            // we want to retain html content type node and avoid doing any manipulation on it
            $clonedHtmlContentTypeNode->nodeValue = '%s';

            // remove potentially harmful attributes on html content type node itself
            while ($htmlContentTypeNode->attributes->length) {
                $htmlContentTypeNode->removeAttribute($htmlContentTypeNode->attributes->item(0)->name);
            }

            // decode outerHTML safely
            $preDecodedOuterHtml = $document->saveHTML($htmlContentTypeNode);

            // clear empty <div> wrapper around outerHTML to replace with $clonedHtmlContentTypeNode
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            $decodedInnerHtml = preg_replace('#^<[^>]*>|</[^>]*>$#', '', html_entity_decode($preDecodedOuterHtml));

            // Use $clonedHtmlContentTypeNode's placeholder to inject decoded inner html
            $decodedOuterHtml = sprintf($document->saveHTML($clonedHtmlContentTypeNode), $decodedInnerHtml);

            // generate unique node name element to replace with decoded html contents at end of processing;
            // goal is to create a document as few times as possible to prevent inadvertent parsing of contents as html
            // by the dom library
            $uniqueNodeName = $this->mathRandom->getRandomString(32, $this->mathRandom::CHARS_LOWERS);

            $uniqueNode = new DOMElement($uniqueNodeName);
            $htmlContentTypeNode->parentNode->replaceChild($uniqueNode, $htmlContentTypeNode);

            $uniqueNodeNameToDecodedOuterHtmlMap[$uniqueNodeName] = $decodedOuterHtml;
        }

        return $uniqueNodeNameToDecodedOuterHtmlMap;
    }

    /**
     * Generate the CSS for any background images on the page
     *
     * @param DOMDocument $document
     */
    protected function generateBackgroundImageStyles(DOMDocument $document): void
    {
        $xpath = new DOMXPath($document);
        $nodes = $xpath->query('//*[@data-background-images]');

        foreach ($nodes as $index => $node) {
            if ($node->attributes->getNamedItem('data-image-initialized')) continue;
            /* @var DOMElement $node */
            $backgroundImages = $node->attributes->getNamedItem('data-background-images');
            if ($backgroundImages->nodeValue !== '') {
                // phpcs:ignore Magento2.Functions.DiscouragedFunction
                $images = $this->json->unserialize(stripslashes($backgroundImages->nodeValue));
                if (count($images) > 0) {
                    $nodeClass = $node->attributes->getNamedItem('class');

                    // Adjust only for slides
                    if ($nodeClass && ($nodeClass->nodeValue === 'pagebuilder-slide-wrapper' || $nodeClass->nodeValue === 'pagebuilder-banner-wrapper')) {
                    $imageElement = $xpath->document->createElement('picture');

                    if (isset($images['mobile_image'])) {
                        $sourceElement2 = $xpath->document->createElement('source');
                        $sourceElement2->setAttribute('srcset', $images['mobile_image']);
                        $sourceElement2->setAttribute('media', '(max-width: 768px)');
                        $imageElement->appendChild($sourceElement2);
                    }

                    $sourceElement = $xpath->document->createElement('img');
                    $sourceElement->setAttribute('src', $images['desktop_image']);
                    $sourceElement->setAttribute('alt', '');
                    $sourceElement->setAttribute('class', 'w-full h-full object-cover');

                    if ($index === 1) {
                        $sourceElement->setAttribute('fetchpriority', 'high');
                    } else {
                        $sourceElement->setAttribute('loading', 'lazy');
                    }

                    $imageElement->appendChild($sourceElement);

                    $imageElement->setAttribute('alt', '');
                    $imageElement->setAttribute('class', 'w-full h-full');

                    $node->appendChild($imageElement);

                    // Append our new class to the DOM element
                    $classes = '';
                    if ($node->attributes->getNamedItem('class')) {
                        $classes = $node->attributes->getNamedItem('class')->nodeValue . ' ';
                    }
                    $node->setAttribute('class', $classes . "relative");
                    $node->setAttribute('data-image-initialized', '');
                    } else {
                        $elementClass = uniqid('background-image-');
                        $style = $xpath->document->createElement(
                            'style',
                            $this->generateCssFromImages($elementClass, $images)
                        );
                        $style->setAttribute('type', 'text/css');
                        $node->parentNode->appendChild($style);

                        // Append our new class to the DOM element
                        $classes = '';
                        if ($node->attributes->getNamedItem('class')) {
                            $classes = $node->attributes->getNamedItem('class')->nodeValue . ' ';
                        }
                        $node->setAttribute('class', $classes . $elementClass);
                    }
                }
            }
        }
    }

    /**
     * Generate CSS based on the images array from our attribute
     *
     * @param string $elementClass
     * @param array $images
     *
     * @return string
     */
    private function generateCssFromImages(string $elementClass, array $images): string
    {
        $css = [];
        if (isset($images['desktop_image'])) {
            $css['.' . $elementClass] = [
                'background-image' => 'url(' . $images['desktop_image'] . ')',
            ];
        }
        if (isset($images['mobile_image']) && $this->getMediaQuery('mobile')) {
            $css[$this->getMediaQuery('mobile')]['.' . $elementClass] = [
                'background-image' => 'url(' . $images['mobile_image'] . ')',
            ];
        }
        if (isset($images['mobile_image']) && $this->getMediaQuery('mobile-small')) {
            $css[$this->getMediaQuery('mobile-small')]['.' . $elementClass] = [
                'background-image' => 'url(' . $images['mobile_image'] . ')',
            ];
        }
        //vvv added this line to parse wide_image if avaliable
        if (isset($images['wide_image'])) {
            $css["@media (min-width: 1600px)"]['.' . $elementClass] = [
                'background-image' => 'url(' . $images['wide_image'] . ')',
            ];
        }
        return $this->cssFromArray($css);
    }

    /**
     * Generate a CSS string from an array
     *
     * @param array $css
     *
     * @return string
     */
    private function cssFromArray(array $css): string
    {
        $output = '';
        foreach ($css as $selector => $body) {
            if (is_array($body)) {
                $output .= $selector . ' {';
                $output .= $this->cssFromArray($body);
                $output .= '}';
            } else {
                $output .= $selector . ': ' . $body . ';';
            }
        }
        return $output;
    }

    /**
     * Generate the mobile media query from view configuration
     *
     * @param string $view
     * @return null|string
     */
    private function getMediaQuery(string $view): ?string
    {
        $breakpoints = $this->viewConfig->getViewConfig()->getVarValue(
            'Magento_PageBuilder',
            'breakpoints/' . $view . '/conditions'
        );
        if ($breakpoints && count($breakpoints) > 0) {
            $mobileBreakpoint = '@media only screen ';
            foreach ($breakpoints as $key => $value) {
                $mobileBreakpoint .= 'and (' . $key . ': ' . $value . ') ';
            }
            return rtrim($mobileBreakpoint);
        }
        return null;
    }

    /**
     * Masks "x-magento-template" script tags in html content before loading it into DOM parser
     *
     * DOMDocument::loadHTML() will remove any closing tag inside script tag and will result in broken html template
     *
     * @param string $content
     * @return string
     * @see https://bugs.php.net/bug.php?id=52012
     */
    private function maskScriptTags(string $content): string
    {
        $tag = 'script';
        $content = preg_replace_callback(
            sprintf('#<%1$s[^>]*type="text/x-magento-template\"[^>]*>.*?</%1$s>#is', $tag),
            function ($matches) {
                $key = $this->mathRandom->getRandomString(32, $this->mathRandom::CHARS_LOWERS);
                $this->scripts[$key] = $matches[0];
                return '<' . $key . '>' . '</' . $key . '>';
            },
            $content
        );
        return $content;
    }

    /**
     * Replaces masked "x-magento-template" script tags with their corresponding content
     *
     * @param string $content
     * @return string
     * @see maskScriptTags()
     */
    private function unmaskScriptTags(string $content): string
    {
        foreach ($this->scripts as $key => $script) {
            $content = str_replace(
                '<' . $key . '>' . '</' . $key . '>',
                $script,
                $content
            );
        }
        return $content;
    }
}
