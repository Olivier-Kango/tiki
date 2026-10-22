<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Seo;

/** Synchronize generated social descriptions after an explicit SEO override. */
class SocialDescriptionFallback
{
    /** Only repair social fallbacks when the underlying filter changes the description. */
    public static function synchronize(string $before, string $after): string
    {
        $headPattern = '~(<head\b[^>]*>)(.*?)(</head\s*>)~is';
        if (! preg_match($headPattern, $before, $oldHead) || ! preg_match($headPattern, $after, $newHead)) {
            return $after;
        }
        $previous = self::descriptions(self::headParts($oldHead[2]));
        $parts = self::headParts($newHead[2]);
        $current = self::descriptions($parts);
        // Ambiguous standard descriptions must be corrected at their source.
        if (count($current) !== 1 || $current === $previous) {
            return $after;
        }
        $description = reset($current);
        foreach (['og:description' => 'property', 'twitter:description' => 'name'] as $name => $attribute) {
            $custom = false;
            foreach ($parts as $index => $part) {
                if ($index % 2 !== 0) {
                    continue;
                }
                preg_match_all(self::metaPattern(), $part, $tags);
                foreach ($tags[0] as $tag) {
                    $values = self::attributes($tag);
                    $content = trim($values['content'] ?? '');
                    if (self::name($values) === $name && $content !== '' && ! in_array($content, $previous, true)) {
                        $custom = true;
                    }
                }
            }
            foreach ($parts as $index => $part) {
                if ($index % 2 === 0) {
                    $parts[$index] = preg_replace_callback(self::metaPattern(), static function ($match) use ($name, $previous) {
                        $values = self::attributes($match[0]);
                        $content = trim($values['content'] ?? '');
                        if (self::name($values) === $name && ($content === '' || in_array($content, $previous, true))) {
                            return '';
                        }
                        return $match[0];
                    }, $part);
                }
            }
            if (! $custom) {
                $parts[0] .= "\n<meta " . $attribute . '="' . $name . '" content="'
                    . htmlspecialchars($description, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">';
            }
        }
        $head = $newHead[1] . implode('', $parts) . $newHead[3];
        return substr_replace($after, $head, strpos($after, $newHead[0]), strlen($newHead[0]));
    }

    /** Keep scripts, JSON-LD, comments and literal text containers byte-for-byte. */
    private static function headParts(string $head): array
    {
        return preg_split('~(<!--.*?-->|<(?:script|style|title|textarea|template)\b[^>]*>.*?</(?:script|style|title|textarea|template)\s*>)~is', $head, -1, PREG_SPLIT_DELIM_CAPTURE);
    }

    private static function metaPattern(): string
    {
        return '~<meta\b(?:[^>\x22\x27]|\x22[^\x22]*\x22|\x27[^\x27]*\x27)*>~i';
    }

    private static function attributes(string $tag): array
    {
        preg_match_all('~\s(name|property|content)\s*=\s*(?:\x22([^\x22]*)\x22|\x27([^\x27]*)\x27|([^\s>]+))~is', $tag, $matches, PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL);
        $attributes = [];
        foreach ($matches as $match) {
            $attributes[strtolower($match[1])] = html_entity_decode($match[2] ?? $match[3] ?? $match[4], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        return $attributes;
    }

    private static function name(array $attributes): string
    {
        return strtolower($attributes['name'] ?? $attributes['property'] ?? '');
    }

    private static function descriptions(array $parts): array
    {
        $descriptions = [];
        foreach ($parts as $index => $part) {
            if ($index % 2 !== 0) {
                continue;
            }
            preg_match_all(self::metaPattern(), $part, $tags);
            foreach ($tags[0] as $tag) {
                $values = self::attributes($tag);
                $content = trim($values['content'] ?? '');
                if (self::name($values) === 'description' && $content !== '') {
                    $descriptions[] = $content;
                }
            }
        }
        return array_values(array_unique($descriptions));
    }
}
