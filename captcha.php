<?php
session_start();

$code = substr(str_shuffle("ABCDEFGHJKLMNPQRSTUVWXYZ23456789"), 0, 6);
$_SESSION['captcha'] = $code;

header('Content-type: image/png');

$img = imagecreatetruecolor(150, 50);
$bg_color = imagecolorallocate($img, 255, 255, 255); // white
$text_color = imagecolorallocate($img, 0, 0, 0);      // black
$line_color = imagecolorallocate($img, 100, 100, 100); // gray

imagefilledrectangle($img, 0, 0, 150, 50, $bg_color);

// Add noise lines
for ($i = 0; $i < 5; $i++) {
    imageline($img, rand(0, 150), rand(0, 50), rand(0, 150), rand(0, 50), $line_color);
}

$font = __DIR__ . '/Roboto-Regular.ttf';  // Adjust path if needed
if (file_exists($font)) {
    imagettftext($img, 20, 0, 20, 35, $text_color, $font, $code);
} else {
    // Optional: fallback if font is missing
    imagestring($img, 5, 20, 18, $code, $text_color);
}

imagepng($img);
imagedestroy($img);
?>
