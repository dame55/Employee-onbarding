<?php

namespace PhpOffice\PhpSpreadsheet\Style;

class RgbTint
{
    private const ONE_THIRD = 1.0 / 3.0;
    private const ONE_SIXTH = 1.0 / 6.0;
    private const TWO_THIRD = 2.0 / 3.0;
    private const RGBMAX = 255.0;
        private const HLSMAX = 240.0;

        private static function rgbToHls(float $red, float $green, float $blue): array
    {
        $maxc = max($red, $green, $blue);
        $minc = min($red, $green, $blue);
        $luminance = ($minc + $maxc) / 2.0;
        if ($minc === $maxc) {
            return [0.0, $luminance, 0.0];
        }
        $maxMinusMin = $maxc - $minc;
        if ($luminance <= 0.5) {
            $s = $maxMinusMin / ($maxc + $minc);
        } else {
            $s = $maxMinusMin / (2.0 - $maxc - $minc);
        }
        $rc = ($maxc - $red) / $maxMinusMin;
        $gc = ($maxc - $green) / $maxMinusMin;
        $bc = ($maxc - $blue) / $maxMinusMin;
        if ($red === $maxc) {
            $h = $bc - $gc;
        } elseif ($green === $maxc) {
            $h = 2.0 + $rc - $bc;
        } else {
            $h = 4.0 + $gc - $rc;
        }
        $h = self::positiveDecimalPart($h / 6.0);

        return [$h, $luminance, $s];
    }

        private static function hlsToRgb(float $hue, float $luminance, float $saturation): array
    {
        if ($saturation === 0.0) {
            return [$luminance, $luminance, $luminance];
        }
        if ($luminance <= 0.5) {
            $m2 = $luminance * (1.0 + $saturation);
        } else {
            $m2 = $luminance + $saturation - ($luminance * $saturation);
        }
        $m1 = 2.0 * $luminance - $m2;

        return [
            self::vFunction($m1, $m2, $hue + self::ONE_THIRD),
            self::vFunction($m1, $m2, $hue),
            self::vFunction($m1, $m2, $hue - self::ONE_THIRD),
        ];
    }

    private static function vFunction(float $m1, float $m2, float $hue): float
    {
        $hue = self::positiveDecimalPart($hue);
        if ($hue < self::ONE_SIXTH) {
            return $m1 + ($m2 - $m1) * $hue * 6.0;
        }
        if ($hue < 0.5) {
            return $m2;
        }
        if ($hue < self::TWO_THIRD) {
            return $m1 + ($m2 - $m1) * (self::TWO_THIRD - $hue) * 6.0;
        }

        return $m1;
    }

    private static function positiveDecimalPart(float $hue): float
    {
        $hue = fmod($hue, 1.0);

        return ($hue >= 0.0) ? $hue : (1.0 + $hue);
    }

        private static function rgbToMsHls(int $red, int $green, int $blue): array
    {
        $red01 = $red / self::RGBMAX;
        $green01 = $green / self::RGBMAX;
        $blue01 = $blue / self::RGBMAX;
        [$hue, $luminance, $saturation] = self::rgbToHls($red01, $green01, $blue01);

        return [
            (int) round($hue * self::HLSMAX),
            (int) round($luminance * self::HLSMAX),
            (int) round($saturation * self::HLSMAX),
        ];
    }

        private static function msHlsToRgb(int $hue, int $lightness, int $saturation): array
    {
        return self::hlsToRgb($hue / self::HLSMAX, $lightness / self::HLSMAX, $saturation / self::HLSMAX);
    }

        private static function tintLuminance(float $tint, float $luminance): int
    {
        if ($tint < 0) {
            return (int) round($luminance * (1.0 + $tint));
        }

        return (int) round($luminance * (1.0 - $tint) + (self::HLSMAX - self::HLSMAX * (1.0 - $tint)));
    }

        public static function rgbAndTintToRgb(int $red, int $green, int $blue, float $tint): string
    {
        [$hue, $luminance, $saturation] = self::rgbToMsHls($red, $green, $blue);
        [$red, $green, $blue] = self::msHlsToRgb($hue, self::tintLuminance($tint, $luminance), $saturation);

        return sprintf(
            '%02X%02X%02X',
            (int) round($red * self::RGBMAX),
            (int) round($green * self::RGBMAX),
            (int) round($blue * self::RGBMAX)
        );
    }
}
