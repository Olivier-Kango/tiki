<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Seo;

/** Explicit metadata, independent of the legacy header.tpl and HtmlHead generators. */
class MetaTagOverride
{
    public const FIELDS = ['description', 'keywords', 'robots'];

    public static function values(array $attributes, string $prefix, string $language = ''): array
    {
        $values = [];
        foreach (self::FIELDS as $field) {
            $key = $prefix . '.' . $field;
            $localized = $language !== '' ? trim((string) ($attributes[$key . '.' . $language] ?? '')) : '';
            $values[$field] = $localized !== '' ? $localized : trim((string) ($attributes[$key] ?? ''));
        }
        return $values;
    }

    /** Each field inherits independently; category IDs are sorted by the caller. */
    public static function resolve(array $object, array $categories, string $language = '', string $legacyRobots = ''): array
    {
        $values = self::values($object, 'tiki.object.metatag', $language);
        if ($values['robots'] === '') {
            $values['robots'] = $legacyRobots;
        }
        foreach ($categories as $attributes) {
            $category = self::values($attributes, 'tiki.category.metatag', $language);
            foreach (self::FIELDS as $field) {
                if ($values[$field] === '') {
                    $values[$field] = $category[$field];
                }
            }
        }
        return $values;
    }

    /** Replace only named meta elements inside the head; leave JSON-LD untouched. */
    public static function apply(string $html, array $values): string
    {
        if (! array_filter($values, static function ($value) {
            return $value !== '';
        })) {
            return $html;
        }
        return preg_replace_callback('~(<head\b[^>]*>)(.*?)(</head\s*>)~is', function ($match) use ($values) {
            $head = $match[2];
            foreach (self::FIELDS as $field) {
                if (($values[$field] ?? '') === '') {
                    continue;
                }
                $names = $field === 'robots' ? ['robots', 'googlebot'] : [$field];
                foreach ($names as $name) {
                    $parts = preg_split('~(<script\b[^>]*>.*?</script\s*>)~is', $head, -1, PREG_SPLIT_DELIM_CAPTURE);
                    foreach ($parts as $index => $part) {
                        if ($index % 2 === 0) {
                            $parts[$index] = preg_replace('~<meta\b(?=[^>]*\sname\s*=\s*([\x22\x27])' . $name . '\1)[^>]*>~i', '', $part);
                        }
                    }
                    $head = implode('', $parts);
                    $head .= "\n" . '<meta name="' . $name . '" content="' . htmlspecialchars($values[$field], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
                }
            }
            return $match[1] . $head . "\n" . $match[3];
        }, $html);
    }

    public static function filterOutput($html, $template)
    {
        global $prefs, $info;
        if (stripos($html, '<head') === false) {
            return $html;
        }
        // Editing/admin screens retain their own indexing restrictions.
        $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
        if (preg_match('/^tiki-(?:edit|admin)/', $script)) {
            return $html;
        }
        $object = current_object();
        if (! $object) {
            return $html;
        }
        $attributes = \TikiLib::lib('attribute');
        $language = ($object['type'] === 'wiki page' ? ($info['lang'] ?? '') : '') ?: ($prefs['language'] ?? '');
        $categoryIds = [];
        if (($prefs['feature_categories'] ?? 'n') === 'y') {
            $categoryIds = $object['type'] === 'category' ? [(int) $object['object']]
                : \TikiLib::lib('categ')->get_object_categories($object['type'], $object['object']);
        }
        sort($categoryIds, SORT_NUMERIC);
        $categories = [];
        foreach ($categoryIds as $id) {
            $categories[] = $attributes->get_attributes('category', $id);
        }
        $legacyRobots = '';
        if ($object['type'] === 'wiki page' && ($prefs['metatag_robotscustom'] ?? 'n') === 'y') {
            $legacyRobots = (string) \TikiLib::lib('wiki')->getPageMetatagRobotscustom($object['object']);
        }
        $values = self::resolve($attributes->get_attributes($object['type'], $object['object']), $categories, $language, $legacyRobots);
        if ($values['robots'] !== '') {
            \TikiLib::lib('header')->setXRobotsTag($values['robots']);
        }
        return self::apply($html, $values);
    }

    public static function save($attributes, string $type, $id, string $prefix, array $input, string $language = ''): void
    {
        foreach (self::FIELDS as $field) {
            if (isset($input[$field]) && is_scalar($input[$field])) {
                $value = trim(strip_tags((string) $input[$field]));
                if ($field === 'robots') {
                    $value = str_replace(["\r", "\n"], '', $value);
                }
                $attributes->set_attribute($type, $id, $prefix . '.' . $field . ($language !== '' ? '.' . $language : ''), $value !== '' ? $value : null);
            }
        }
    }
}
