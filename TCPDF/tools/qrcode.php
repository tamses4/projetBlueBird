<?php
// qrcode.php
class QRcode {
    public static function png($text, $file, $level = 0, $size = 3) {
        $url = "https://chart.googleapis.com/chart?chs={$size}0x{$size}0&cht=qr&chl=" . urlencode($text);
        file_put_contents($file, file_get_contents($url));
    }
}