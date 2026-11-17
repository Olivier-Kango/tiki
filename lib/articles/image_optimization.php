<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/** Cached display-only WebP derivatives. Originals and download responses stay intact. */
function article_accepts_webp(): bool
{
    foreach (explode(',', $_SERVER['HTTP_ACCEPT'] ?? '') as $entry) {
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

function article_optimized_image(string $bytes, string $mime, string $cacheDirectory): array
{
    if (! article_accepts_webp() || ! function_exists('imagewebp') || ! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        return [$bytes, $mime];
    }
    $size = @getimagesizefromstring($bytes);
    // Avoid allocating excessive memory for exceptionally large source images.
    if (! $size || $size[0] * $size[1] > 16000000 || strlen($bytes) > 20000000) {
        return [$bytes, $mime];
    }
    if ($mime === 'image/webp' && max($size[0], $size[1]) <= 1600 && strlen($bytes) < 120000) {
        return [$bytes, $mime];
    }
    // Preserve animated WebP and APNG files.
    if (($mime === 'image/webp' && strpos($bytes, 'ANIM') !== false) || ($mime === 'image/png' && strpos($bytes, 'acTL') !== false)) {
        return [$bytes, $mime];
    }
    $key = hash('sha256', 'webp-v1-1600-q76-' . $bytes);
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
    $image = @imagecreatefromstring($bytes);
    if (! $image) {
        return [$bytes, $mime];
    }
    // Keep JPEG orientation when it is recorded in EXIF metadata.
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $stream = tmpfile();
        if ($stream) {
            fwrite($stream, $bytes);
            rewind($stream);
            $exif = @exif_read_data($stream);
            fclose($stream);
            $orientation = (int) ($exif['Orientation'] ?? 1);
            if (in_array($orientation, [2, 4, 5, 7], true)) {
                imageflip($image, IMG_FLIP_HORIZONTAL);
            }
            $angle = [3 => 180, 4 => 180, 5 => 90, 6 => -90, 7 => -90, 8 => 90][$orientation] ?? 0;
            if ($angle) {
                $rotated = imagerotate($image, $angle, 0);
                if ($rotated) {
                    imagedestroy($image);
                    $image = $rotated;
                }
            }
        }
    }
    $width = imagesx($image);
    $height = imagesy($image);
    $ratio = min(1, 1600 / max($width, $height));
    $output = imagecreatetruecolor(max(1, (int) round($width * $ratio)), max(1, (int) round($height * $ratio)));
    imagealphablending($output, false);
    imagesavealpha($output, true);
    imagefill($output, 0, 0, imagecolorallocatealpha($output, 0, 0, 0, 127));
    imagecopyresampled($output, $image, 0, 0, 0, 0, imagesx($output), imagesy($output), $width, $height);
    ob_start();
    $success = imagewebp($output, null, 76);
    $optimized = ob_get_clean();
    imagedestroy($image);
    imagedestroy($output);
    if (! $success || ! $optimized || strlen($optimized) >= strlen($bytes)) {
        return [$bytes, $mime];
    }
    if (is_dir($cacheDirectory) || @mkdir($cacheDirectory, 0775, true)) {
        // Content-addressed entries never change, and permissions are checked by the caller.
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
