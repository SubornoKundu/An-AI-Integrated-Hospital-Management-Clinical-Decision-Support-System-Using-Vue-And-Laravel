<?php

namespace App\Services;

class SvgSanitizer
{
    /**
     * Strip the parts of an SVG that make it a stored-XSS vector, while
     * leaving the actual graphic intact. SVG is XML, so it can legally
     * contain <script>, event-handler attributes (onload, onclick, ...),
     * and javascript: URIs — none of which are needed to render a logo,
     * all of which execute if the file is ever opened directly rather than
     * only used inside an <img> tag.
     *
     * Approach: parse as XML (not regex — regex-based HTML/XML sanitizing
     * is well documented as unreliable, e.g. missing obfuscated or
     * whitespace-split tag names) and remove the dangerous nodes/attributes
     * by structure instead of by pattern-matching text.
     *
     * Returns null if the content isn't parseable as XML at all — callers
     * should treat that as "reject the upload", not "fall back to the
     * original", since an unparseable file has no safe representation here.
     */
    public static function clean(string $svgContents): ?string
    {
        $previous = libxml_use_internal_errors(true);
        // LIBXML_NONET blocks network access during parsing (defense in
        // depth against SSRF via an external DTD/entity reference). PHP 8+
        // already disables external entity loading by default here since no
        // DTD validation is requested, but the flag costs nothing to keep.
        $doc = new \DOMDocument();
        $loaded = $doc->loadXML($svgContents, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded || ! $doc->documentElement) {
            return null;
        }

        if (strtolower($doc->documentElement->nodeName) !== 'svg') {
            return null;
        }

        $xpath = new \DOMXPath($doc);

        // Remove entire elements that have no legitimate purpose in a
        // static logo and are common script-execution vectors.
        foreach (['script', 'foreignObject', 'iframe', 'embed', 'object'] as $tag) {
            foreach (iterator_to_array($xpath->query("//*[local-name()='{$tag}']")) as $node) {
                $node->parentNode?->removeChild($node);
            }
        }

        // Remove every event-handler attribute (onload, onclick, onerror,
        // ...) and any href/xlink:href that points at a javascript: URI,
        // on every remaining element.
        foreach (iterator_to_array($xpath->query('//*')) as $element) {
            if (! $element instanceof \DOMElement) {
                continue;
            }
            foreach (iterator_to_array($element->attributes ?? []) as $attr) {
                $attrName = strtolower($attr->nodeName);
                $isEventHandler = str_starts_with($attrName, 'on');
                $isHrefLike = in_array($attrName, ['href', 'xlink:href'], true);
                $isJsUri = $isHrefLike && preg_match('/^\s*javascript:/i', $attr->nodeValue ?? '');

                if ($isEventHandler || $isJsUri) {
                    $element->removeAttribute($attr->nodeName);
                }
            }
        }

        $clean = $doc->saveXML($doc->documentElement);

        return $clean === false ? null : $clean;
    }
}
