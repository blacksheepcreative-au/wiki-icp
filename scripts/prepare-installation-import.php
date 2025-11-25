#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Transform a WXR export so Installation Videos import cleanly.
 *
 * Usage:
 *   php scripts/prepare-installation-import.php --input=starlinesecurity.WordPress.2025-11-15.xml --output=installation-videos.wxr
 *
 * Optional flags:
 *   --category=installation-videos   Only convert posts in this WP category (use "*" to convert all).
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

$options = getopt('', ['input:', 'output:', 'category::']);

if (empty($options['input']) || empty($options['output'])) {
    fwrite(STDERR, "Usage: php scripts/prepare-installation-import.php --input=source.wxr --output=target.wxr [--category=installation-videos]\n");
    exit(1);
}

$inputPath  = $options['input'];
$outputPath = $options['output'];
$category   = $options['category'] ?? 'installation-videos';

if (!is_readable($inputPath)) {
    fwrite(STDERR, "Input file not readable: {$inputPath}\n");
    exit(1);
}

$xml = simplexml_load_file($inputPath);
if (!$xml) {
    fwrite(STDERR, "Unable to parse XML export.\n");
    exit(1);
}

$namespaces = $xml->getNamespaces(true);
$converted  = 0;

foreach ($xml->channel->item as $item) {
    $wp = $item->children($namespaces['wp']);
    if ((string) $wp->post_type !== 'post') {
        continue;
    }

    if ($category !== '*' && $category !== '' && !installation_video_has_category($item, $category)) {
        continue;
    }

    $wp->post_type = 'installation_video';
    convert_stackable_content($item, $namespaces);
    convert_video_meta($wp);
    $converted++;
}

$dom = dom_import_simplexml($xml)->ownerDocument;
$dom->formatOutput = true;
$dom->preserveWhiteSpace = false;

if (false === $dom->save($outputPath)) {
    fwrite(STDERR, "Failed to write output file: {$outputPath}\n");
    exit(1);
}

fwrite(STDOUT, sprintf("Converted %d posts. Output written to %s\n", $converted, $outputPath));

/**
 * Check whether the <item> has the requested category nicename.
 */
function installation_video_has_category(SimpleXMLElement $item, string $expected): bool {
    foreach ($item->category as $category) {
        if ((string) $category['nicename'] === $expected) {
            return true;
        }
    }
    return false;
}

/**
 * Replace Stackable container markup with a core Group block enclosing the same content.
 */
function convert_stackable_content(SimpleXMLElement $item, array $namespaces): void {
    $contentNode = $item->children($namespaces['content'])->encoded ?? null;
    if (!$contentNode) {
        return;
    }

    $content = (string) $contentNode;
    if (strpos($content, 'wp-block-ugb-container') === false) {
        return;
    }

    $content = preg_replace(
        '#<!--\s*wp:(?:ugb/container|html)[^>]*-->#i',
        '<!-- wp:group {"layout":{"type":"constrained"}} -->',
        $content,
        -1
    ) ?? $content;

    $content = preg_replace('#<!--\s*/wp:(?:ugb/container|html)\s*-->#i', '<!-- /wp:group -->', $content, -1) ?? $content;

    $content = preg_replace(
        '#<div class="wp-block-ugb-container[\s\S]*?<div class="ugb-container__content-wrapper[^>]*?>#i',
        '<div class="wp-block-group">',
        $content,
        -1
    ) ?? $content;

    $content = preg_replace('#</div>\s*</div>\s*</div>\s*</div>\s*</div>\s*</div>#i', '</div>', $content, -1) ?? $content;

    set_cdata($contentNode, $content);
}

/**
 * Rename block_video_url meta + remove Stackable-specific fields.
 */
function convert_video_meta(SimpleXMLElement $wp): void {
    $nodesToRemove = [];

    foreach ($wp->postmeta as $meta) {
        $key = (string) $meta->meta_key;

        if ($key === 'block_video_url') {
            $meta->meta_key = 'youtube_video';
        }

        if ($key === 'stackable_optimized_css' || $key === 'stackable_optimized_css_raw') {
            $nodesToRemove[] = $meta;
        }

        if ($key === '_block_video_url') {
            $nodesToRemove[] = $meta;
        }
    }

    foreach ($nodesToRemove as $meta) {
        $domMeta = dom_import_simplexml($meta);
        if ($domMeta && $domMeta->parentNode) {
            $domMeta->parentNode->removeChild($domMeta);
        }
    }
}

function set_cdata(SimpleXMLElement $node, string $value): void {
    $dom = dom_import_simplexml($node);
    if (!$dom) {
        return;
    }

    while ($dom->firstChild) {
        $dom->removeChild($dom->firstChild);
    }

    $dom->appendChild($dom->ownerDocument->createCDATASection($value));
}
