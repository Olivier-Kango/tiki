<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Image;

/**
 * Cached display-only WebP derivatives for any content feature.
 * Callers retain responsibility for access checks, preferences and HTTP response headers.
 * Original bytes are returned whenever conversion is unavailable or would not reduce size.
 */
final class DisplayOptimizer
{
    public static function acceptsWebp(string $acceptHeader): bool
    {
        foreach (explode(',', $acceptHeader) as $entry) {
            $parts = array_map('trim', explode(';', strtolower($entry)));
            if ($parts[0] !== 'image/webp') {
                continue;
            }
            foreach (array_slice($parts, 1) as $parameter) {
                if (preg_match('/^q\s*=\s*([0-9.]+)$/', $parameter, $match) && (float) $match[1] <= 0) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Dimensions and quality are caller-specific and included in the cache identity.
     *
     * @return array{0: string, 1: string} Display bytes and their MIME type.
     */
    public static function optimize(
        string $bytes,
        string $mime,
        string $cacheDirectory,
        string $acceptHeader,
        int $maxDimension = 1600,
        int $quality = 76
    ): array {
        if (! self::acceptsWebp($acceptHeader) || ! Image::isAvailable() || ! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return [$bytes, $mime];
        }
        if ($maxDimension < 1 || $quality < 0 || $quality > 100) {
            return [$bytes, $mime];
        }
        $size = @getimagesizefromstring($bytes);
        // Avoid allocating excessive memory for exceptionally large source images.
        if (! $size || $size[0] * $size[1] > 16000000 || strlen($bytes) > 20000000) {
            return [$bytes, $mime];
        }
        if ($mime === 'image/webp' && max($size[0], $size[1]) <= $maxDimension && strlen($bytes) < 120000) {
            return [$bytes, $mime];
        }
        // Preserve animated WebP and APNG files.
        if (($mime === 'image/webp' && strpos($bytes, 'ANIM') !== false) || ($mime === 'image/png' && strpos($bytes, 'acTL') !== false)) {
            return [$bytes, $mime];
        }
        $key = hash('sha256', 'webp-v2-' . $maxDimension . '-q' . $quality . '-' . $bytes);
        $cacheFile = $cacheDirectory . '/' . $key . '.webp';
        if (is_file($cacheFile)) {
            $cached = @file_get_contents($cacheFile);
            if ($cached !== false && strlen($cached) > 0) {
                return [$cached, 'image/webp'];
            }
        }
        $limit = ini_get('memory_limit');
        $memoryLimit = (int) $limit;
        if (stripos($limit, 'G') !== false) {
            $memoryLimit *= 1073741824;
        } elseif (stripos($limit, 'M') !== false) {
            $memoryLimit *= 1048576;
        } elseif (stripos($limit, 'K') !== false) {
            $memoryLimit *= 1024;
        }
        if ($memoryLimit > 0 && memory_get_usage(true) + $size[0] * $size[1] * 10 + 16000000 > $memoryLimit) {
            return [$bytes, $mime];
        }
        // Mirrored EXIF orientations are left untouched until the shared image API supports flipping.
        $orientation = 1;
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $stream = tmpfile();
            if ($stream) {
                fwrite($stream, $bytes);
                rewind($stream);
                $exif = @exif_read_data($stream);
                fclose($stream);
                $orientation = (int) ($exif['Orientation'] ?? 1);
            }
        }
        if (in_array($orientation, [2, 4, 5, 7], true)) {
            return [$bytes, $mime];
        }
        try {
            $image = Image::create($bytes, false, substr($mime, 6));
            if (! $image || ! $image->isSupported('webp')) {
                return [$bytes, $mime];
            }
            $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;
            if ($angle && ! $image->rotate($angle)) {
                return [$bytes, $mime];
            }
            $image->resizeMax($maxDimension);
            $image->setQuality($quality);
            if (! $image->convert('webp')) {
                return [$bytes, $mime];
            }
            $optimized = $image->display();
        } catch (\Throwable) {
            return [$bytes, $mime];
        }
        if (! $optimized || strlen($optimized) >= strlen($bytes)) {
            return [$bytes, $mime];
        }
        if (is_dir($cacheDirectory) || @mkdir($cacheDirectory, 0775, true)) {
            // The caller is responsible for permissions; this service only caches derivatives.
            $temporary = @tempnam($cacheDirectory, 'webp-');
            if ($temporary !== false) {
                if (@file_put_contents($temporary, $optimized) !== false) {
                    @chmod($temporary, 0664);
                    @rename($temporary, $cacheFile);
                }
                if (is_file($temporary)) {
                    @unlink($temporary);
                }
            }
        }
        return [$optimized, 'image/webp'];
    }
}
