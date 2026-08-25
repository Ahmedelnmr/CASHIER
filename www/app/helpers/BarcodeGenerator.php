<?php
/**
 * Barcode Generator - Pure PHP Code 128B
 * Generates barcode images using GD library
 */

class BarcodeGenerator {
    
    // Code 128B character set
    private static $charSet = array(
        ' ', '!', '"', '#', '$', '%', '&', "'", '(', ')', '*', '+', ',', '-', '.', '/',
        '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', ':', ';', '<', '=', '>', '?',
        '@', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O',
        'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '[', '\\', ']', '^', '_',
        '`', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o',
        'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', '{', '|', '}', '~', chr(127),
        chr(128), chr(129), chr(130), chr(131), chr(132), chr(133), chr(134), chr(135),
        chr(136), chr(137), chr(138), chr(139), chr(140), chr(141), chr(142), chr(143),
        chr(144), chr(145), chr(146), chr(147), chr(148), chr(149), chr(150), chr(151),
        chr(152), chr(153), chr(154), chr(155), chr(156), chr(157), chr(158), chr(159),
        chr(160), chr(161), chr(162), chr(163), chr(164), chr(165), chr(166), chr(167),
        chr(168), chr(169), chr(170), chr(171), chr(172), chr(173), chr(174), chr(175),
        chr(176), chr(177), chr(178), chr(179), chr(180), chr(181), chr(182), chr(183),
        chr(184), chr(185), chr(186), chr(187), chr(188), chr(189), chr(190), chr(191),
        chr(192), chr(193), chr(194), chr(195), chr(196), chr(197), chr(198), chr(199),
        chr(200), chr(201), chr(202)
    );

    // Code 128B bar patterns (widths of bars and spaces)
    private static $patterns = array(
        '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312',
        '132212', '221213', '221312', '231212', '112232', '122132', '122231', '113222',
        '123122', '123221', '223211', '221132', '221231', '213212', '223112', '312131',
        '311222', '321122', '321221', '312212', '322112', '322211', '212123', '212321',
        '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
        '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121',
        '313121', '211331', '231131', '213113', '213311', '213131', '311123', '311321',
        '331121', '312113', '312311', '332111', '314111', '221411', '431111', '111224',
        '111422', '121124', '121421', '141122', '141221', '112214', '112412', '122114',
        '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
        '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112',
        '421211', '212141', '214121', '412121', '111143', '111341', '131141', '114113',
        '114311', '411113', '411311', '113141', '114131', '311141', '411131', '211412',
        '211214', '211232', '2331112'
    );

    /**
     * Generate barcode image
     * @param string $data The data to encode
     * @param int $width Width of each bar in pixels
     * @param int $height Height of barcode in pixels
     * @param bool $showText Whether to show text below barcode
     * @return string PNG image data
     */
    public static function generate($data, $width = 2, $height = 60, $showText = true) {
        if (empty($data)) return null;

        // Encode data to Code 128B
        $encoded = self::encodeCode128B($data);
        if (!$encoded) return null;

        $barCount = strlen($encoded);
        $imgWidth = ($barCount * $width) + 20; // 10px padding each side
        $imgHeight = $showText ? $height + 20 : $height;

        // Create image
        $img = imagecreate($imgWidth, $imgHeight);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);

        // Draw bars
        $x = 10;
        for ($i = 0; $i < $barCount; $i++) {
            if ($encoded[$i] === '1') {
                imagefilledrectangle($img, $x, 5, $x + $width - 1, $height + 4, $black);
            }
            $x += $width;
        }

        // Draw text
        if ($showText) {
            $font = 5; // Built-in font
            $textWidth = imagefontwidth($font) * strlen($data);
            $textX = max(10, ($imgWidth - $textWidth) / 2);
            imagestring($img, $font, $textX, $height + 6, $data, $black);
        }

        // Output
        ob_start();
        imagepng($img);
        $imageData = ob_get_clean();
        imagedestroy($img);

        return $imageData;
    }

    /**
     * Encode string to Code 128B binary pattern
     */
    private static function encodeCode128B($data) {
        $charMap = array_flip(self::$charSet);
        
        // Start Code B = 104
        $checksum = 104;
        $encoded = self::$patterns[104]; // Start B

        for ($i = 0; $i < strlen($data); $i++) {
            $char = $data[$i];
            if (!isset($charMap[$char])) {
                // Try to use space for unsupported chars
                $char = ' ';
            }
            $value = $charMap[$char];
            $encoded .= self::$patterns[$value];
            $checksum += $value * ($i + 1);
        }

        // Checksum
        $checksum = $checksum % 103;
        $encoded .= self::$patterns[$checksum];

        // Stop
        $encoded .= self::$patterns[106]; // Stop

        return $encoded;
    }

    /**
     * Generate barcode and output as PNG
     */
    public static function output($data, $width = 2, $height = 60, $showText = true) {
        $imageData = self::generate($data, $width, $height, $showText);
        if ($imageData) {
            header('Content-Type: image/png');
            header('Cache-Control: public, max-age=86400');
            echo $imageData;
        } else {
            header('HTTP/1.0 400 Bad Request');
            echo 'Invalid barcode data';
        }
    }
}
