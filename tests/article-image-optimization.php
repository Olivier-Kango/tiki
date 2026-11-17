<?php

require dirname(__DIR__) . '/lib/articles/image_optimization.php';
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
check(! article_accepts_webp(), 'q=0 must disable WebP');
$_SERVER['HTTP_ACCEPT'] = 'image/webp;q=0.8,image/png';
check(article_accepts_webp(), 'WebP acceptance');
if (! function_exists('imagewebp')) {
    check(article_optimized_image('original', 'image/png', $cache) === ['original', 'image/png'], 'GD unavailable fallback');
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
[$bytes, $mime] = article_optimized_image($png, 'image/png', $cache);
check($mime === 'image/webp', 'PNG display converted');
check(strlen($bytes) < strlen($png), 'WebP is smaller');
$decoded = imagecreatefromstring($bytes);
check(imagesx($decoded) === 1600 && imagesy($decoded) === 800, 'Aspect ratio and display dimensions');
check((imagecolorat($decoded, 0, 0) >> 24) === 127, 'Transparent pixels preserved');
imagedestroy($decoded);
check(article_optimized_image($png, 'image/png', $cache) === [$bytes, $mime], 'Cache produces identical derivative');
$_SERVER['HTTP_ACCEPT'] = 'image/png';
check(article_optimized_image($png, 'image/png', $cache) === [$png, 'image/png'], 'Unsupported browser retains original');
$_SERVER['HTTP_ACCEPT'] = 'image/webp';
check(article_optimized_image('invalid image', 'image/png', $cache) === ['invalid image', 'image/png'], 'Invalid data safely returned');
check(article_optimized_image('GIF89a', 'image/gif', $cache) === ['GIF89a', 'image/gif'], 'GIF animation is not converted');
$svg = '<svg xmlns="http://www.w3.org/2000/svg"></svg>';
check(article_optimized_image($svg, 'image/svg+xml', $cache) === [$svg, 'image/svg+xml'], 'Vector artwork remains vector');
$jpegImage = imagecreatetruecolor(1800, 1200);
$white = imagecolorallocate($jpegImage, 245, 238, 226);
imagefill($jpegImage, 0, 0, $white);
$red = imagecolorallocate($jpegImage, 120, 25, 45);
imagefilledrectangle($jpegImage, 200, 200, 1600, 1000, $red);
ob_start();
imagejpeg($jpegImage, null, 95);
$jpeg = ob_get_clean();
imagedestroy($jpegImage);
[$jpegBytes, $jpegMime] = article_optimized_image($jpeg, 'image/jpeg', $cache);
check($jpegMime === 'image/webp' && strlen($jpegBytes) < strlen($jpeg), 'JPEG photos converted safely');
echo "WebP display: transparency, dimensions, cache, negotiation and fallbacks passed\n";
