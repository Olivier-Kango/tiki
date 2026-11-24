<?php

use Tiki\Lib\Image\DisplayOptimizer;

require dirname(__DIR__) . '/vendor_bundled/vendor/autoload.php';
$cache = sys_get_temp_dir() . '/tiki-webp-test-' . bin2hex(random_bytes(6));
register_shutdown_function(static function () use ($cache) {
    foreach (glob($cache . '/*') ?: [] as $file) {
        unlink($file);
    }
    if (is_dir($cache)) {
        rmdir($cache);
    }
});
function check($condition, $message)
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}
$_SERVER['HTTP_ACCEPT'] = 'image/webp;q=0,image/png';
check(! DisplayOptimizer::acceptsWebp($_SERVER['HTTP_ACCEPT']), 'q=0 must disable WebP');
$_SERVER['HTTP_ACCEPT'] = 'image/webp;q=0.8,image/png';
check(DisplayOptimizer::acceptsWebp($_SERVER['HTTP_ACCEPT']), 'WebP acceptance');
if (! function_exists('imagewebp')) {
    check(DisplayOptimizer::optimize('original', 'image/png', $cache, $_SERVER['HTTP_ACCEPT']) === ['original', 'image/png'], 'GD unavailable fallback');
    echo "PASS: GD unavailable, originals retained\n";
    exit;
}
$image = imagecreatetruecolor(2000, 1000);
imagealphablending($image, false);
imagesavealpha($image, true);
$clear = imagecolorallocatealpha($image, 0, 0, 0, 127);
imagefill($image, 0, 0, $clear);
$color = imagecolorallocatealpha($image, 120, 30, 50, 0);
imagefilledrectangle($image, 500, 200, 1500, 800, $color);
ob_start();
imagepng($image, null, 0);
$png = ob_get_clean();
imagedestroy($image);
[$bytes, $mime] = DisplayOptimizer::optimize($png, 'image/png', $cache, $_SERVER['HTTP_ACCEPT']);
check($mime === 'image/webp', 'PNG display converted');
check(strlen($bytes) < strlen($png), 'WebP is smaller');
$decoded = imagecreatefromstring($bytes);
check(imagesx($decoded) === 1600 && imagesy($decoded) === 800, 'Aspect ratio and display dimensions');
check((imagecolorat($decoded, 0, 0) >> 24) === 127, 'Transparent pixels preserved');
imagedestroy($decoded);
check(DisplayOptimizer::optimize($png, 'image/png', $cache, $_SERVER['HTTP_ACCEPT']) === [$bytes, $mime], 'Cache produces identical derivative');
$_SERVER['HTTP_ACCEPT'] = 'image/png';
check(DisplayOptimizer::optimize($png, 'image/png', $cache, $_SERVER['HTTP_ACCEPT']) === [$png, 'image/png'], 'Unsupported browser retains original');
$_SERVER['HTTP_ACCEPT'] = 'image/webp';
check(DisplayOptimizer::optimize('invalid image', 'image/png', $cache, $_SERVER['HTTP_ACCEPT']) === ['invalid image', 'image/png'], 'Invalid data safely returned');
check(DisplayOptimizer::optimize('GIF89a', 'image/gif', $cache, $_SERVER['HTTP_ACCEPT']) === ['GIF89a', 'image/gif'], 'GIF animation is not converted');
$svg = '<svg xmlns="http://www.w3.org/2000/svg"></svg>';
check(DisplayOptimizer::optimize($svg, 'image/svg+xml', $cache, $_SERVER['HTTP_ACCEPT']) === [$svg, 'image/svg+xml'], 'Vector artwork remains vector');
$jpegImage = imagecreatetruecolor(1800, 1200);
$white = imagecolorallocate($jpegImage, 245, 238, 226);
imagefill($jpegImage, 0, 0, $white);
$red = imagecolorallocate($jpegImage, 120, 25, 45);
imagefilledrectangle($jpegImage, 200, 200, 1600, 1000, $red);
ob_start();
imagejpeg($jpegImage, null, 95);
$jpeg = ob_get_clean();
imagedestroy($jpegImage);
[$jpegBytes, $jpegMime] = DisplayOptimizer::optimize($jpeg, 'image/jpeg', $cache, $_SERVER['HTTP_ACCEPT']);
check($jpegMime === 'image/webp' && strlen($jpegBytes) < strlen($jpeg), 'JPEG photos converted safely');
echo "WebP display: transparency, dimensions, cache, negotiation and fallbacks passed\n";

// Other consumers can choose their display size and quality independently of Articles.
[$smallBytes, $smallMime] = DisplayOptimizer::optimize($png, 'image/png', $cache, 'image/webp', 320, 60);
check($smallMime === 'image/webp', 'Shared optimizer accepts caller-specific settings');
$small = getimagesizefromstring($smallBytes);
check($small[0] === 320 && $small[1] === 160, 'Caller-specific dimensions preserve proportions');
check(DisplayOptimizer::optimize($png, 'image/png', $cache, 'image/webp', 320, 60) === [$smallBytes, $smallMime], 'Caller-specific derivative is cached separately');
check(DisplayOptimizer::optimize($png, 'image/png', $cache, 'image/webp', 0) === [$png, 'image/png'], 'Invalid dimension keeps original');
check(DisplayOptimizer::optimize($png, 'image/png', $cache, 'image/webp', 320, 101) === [$png, 'image/png'], 'Invalid quality keeps original');
check(DisplayOptimizer::optimize($png, 'image/png', $cache, 'image/webp;q=0') === [$png, 'image/png'], 'Explicit WebP refusal keeps original');

if (function_exists('exif_read_data')) {
    foreach ([2, 3, 4, 5, 6, 7, 8] as $orientation) {
        // A minimal EXIF APP1 segment containing only the orientation tag.
        $tiff = 'II' . pack('vV', 42, 8) . pack('v', 1)
            . pack('vvVv', 0x0112, 3, 1, $orientation) . "\0\0" . pack('V', 0);
        $exif = "Exif\0\0" . $tiff;
        $orientedJpeg = substr($jpeg, 0, 2) . "\xff\xe1" . pack('n', strlen($exif) + 2) . $exif . substr($jpeg, 2);
        [$result, $resultMime] = DisplayOptimizer::optimize($orientedJpeg, 'image/jpeg', $cache, 'image/webp');
        if (in_array($orientation, [2, 4, 5, 7], true)) {
            check([$result, $resultMime] === [$orientedJpeg, 'image/jpeg'], 'Mirrored EXIF orientation safely retains original');
        } else {
            check($resultMime === 'image/webp', 'Rotated JPEG is optimized');
            $dimensions = getimagesizefromstring($result);
            check($orientation === 3 ? $dimensions[0] > $dimensions[1] : $dimensions[1] > $dimensions[0], 'JPEG orientation retained');
        }
    }
}
echo "Shared optimizer: caller settings, cache isolation and EXIF orientation passed\n";
