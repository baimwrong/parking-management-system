<?php

namespace App\Services;

class BarcodeService
{
    /**
     * Code 128 pattern table (107 symbols).
     * Alternating bar and space widths.
     */
    protected static array $patterns = [
        0 => '212222', 1 => '222122', 2 => '222221', 3 => '121223', 4 => '121322',
        5 => '131222', 6 => '122213', 7 => '122312', 8 => '132212', 9 => '221213',
        10 => '221312', 11 => '231212', 12 => '112232', 13 => '122132', 14 => '122231',
        15 => '113222', 16 => '123122', 17 => '123221', 18 => '223211', 19 => '221132',
        20 => '221231', 21 => '213212', 22 => '223112', 23 => '312131', 24 => '311222',
        25 => '321122', 26 => '321221', 27 => '312212', 28 => '322112', 29 => '322211',
        30 => '212123', 31 => '212321', 32 => '232121', 33 => '111323', 34 => '131123',
        35 => '131321', 36 => '112313', 37 => '132113', 38 => '132311', 39 => '211313',
        40 => '231113', 41 => '231311', 42 => '112133', 43 => '112331', 44 => '132131',
        45 => '113123', 46 => '113321', 47 => '133121', 48 => '313121', 49 => '211331',
        50 => '231131', 51 => '213113', 52 => '213311', 53 => '213131', 54 => '311123',
        55 => '311321', 56 => '331121', 57 => '312113', 58 => '312311', 59 => '332111',
        60 => '314111', 61 => '221411', 62 => '431111', 63 => '111224', 64 => '111422',
        65 => '121124', 66 => '121421', 67 => '141122', 68 => '141221', 69 => '112214',
        70 => '112412', 71 => '122114', 72 => '122411', 73 => '142112', 74 => '142211',
        75 => '241211', 76 => '221114', 77 => '413111', 78 => '241112', 79 => '134111',
        80 => '111242', 81 => '121142', 82 => '121241', 83 => '114212', 84 => '124112',
        85 => '124211', 86 => '411212', 87 => '421112', 88 => '421211', 89 => '212141',
        90 => '214121', 91 => '412121', 92 => '111143', 93 => '111341', 94 => '131141',
        95 => '114113', 96 => '114311', 97 => '411113', 98 => '411311', 99 => '113141',
        100 => '114131', 101 => '311141', 102 => '411131', 103 => '211412', 104 => '211214',
        105 => '211232', 106 => '2331112',
    ];

    /**
     * Generate an authentic Code-128 (Subset B) SVG barcode.
     *
     * @param string $text ASCII string to encode
     * @param int $height Height of barcode in pixels
     * @param float $barWidth Width of standard 1-module bar
     * @return string SVG markup
     */
    public static function getCode128Svg(string $text, int $height = 50, float $barWidth = 1.6): string
    {
        $text = trim($text);
        if ($text === '') {
            $text = 'PARKING';
        }

        // Start Code B is index 104
        $symbols = [104];
        $checksum = 104;

        $length = strlen($text);
        for ($i = 0; $i < $length; $i++) {
            $charVal = ord($text[$i]) - 32;
            if ($charVal < 0 || $charVal > 95) {
                $charVal = 0; // fallback to space
            }
            $symbols[] = $charVal;
            $checksum += $charVal * ($i + 1);
        }

        // Check digit
        $symbols[] = $checksum % 103;

        // Stop symbol is index 106
        $symbols[] = 106;

        // Build bars
        $rects = [];
        $currentX = 10.0; // Left quiet zone

        foreach ($symbols as $symIndex) {
            $pattern = self::$patterns[$symIndex] ?? '211214';
            $patternLen = strlen($pattern);
            for ($p = 0; $p < $patternLen; $p++) {
                $widthUnits = (int) $pattern[$p];
                $pixelWidth = $widthUnits * $barWidth;
                $isBar = ($p % 2 === 0);

                if ($isBar) {
                    $rects[] = sprintf('<rect x="%.2f" y="0" width="%.2f" height="%d" fill="#000000" />', $currentX, $pixelWidth, $height);
                }
                $currentX += $pixelWidth;
            }
        }

        $currentX += 10.0; // Right quiet zone
        $totalWidth = ceil($currentX);

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" width="100%%" height="%d" style="display:block;margin:0 auto;max-width:%dpx;">%s</svg>',
            $totalWidth,
            $height,
            $height,
            $totalWidth,
            implode('', $rects)
        );
    }
}
