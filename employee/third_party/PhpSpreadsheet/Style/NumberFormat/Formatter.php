<?php

namespace PhpOffice\PhpSpreadsheet\Style\NumberFormat;

use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Reader\Xls\Color\BIFF8;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class Formatter extends BaseFormatter
{
        private const SYMBOL_AT = '/@(?=(?:[^"]*"[^"]*")*[^"]*\Z)/miu';

        private const SECTION_SPLIT = '/;(?=(?:[^"]*"[^"]*")*[^"]*\Z)/miu';

    private static function splitFormatComparison(
        mixed $value,
        ?string $condition,
        mixed $comparisonValue,
        string $defaultCondition,
        mixed $defaultComparisonValue
    ): bool {
        if (!$condition) {
            $condition = $defaultCondition;
            $comparisonValue = $defaultComparisonValue;
        }

        return match ($condition) {
            '>' => $value > $comparisonValue,
            '<' => $value < $comparisonValue,
            '<=' => $value <= $comparisonValue,
            '<>' => $value != $comparisonValue,
            '=' => $value == $comparisonValue,
            default => $value >= $comparisonValue,
        };
    }

    private static function splitFormatForSectionSelection(array $sections, mixed $value): array
    {
                                                                $sectionCount = count($sections);
                $color_regex = '/\\[(' . implode('|', Color::NAMED_COLORS) . '|color\\s*(\\d+))\\]/mui';
        $cond_regex = '/\\[(>|>=|<|<=|=|<>)([+-]?\\d+([.]\\d+)?)\\]/';
        $colors = ['', '', '', '', ''];
        $conditionOperations = ['', '', '', '', ''];
        $conditionComparisonValues = [0, 0, 0, 0, 0];
        for ($idx = 0; $idx < $sectionCount; ++$idx) {
            if (preg_match($color_regex, $sections[$idx], $matches)) {
                if (isset($matches[2])) {
                    $colors[$idx] = '#' . BIFF8::lookup((int) $matches[2] + 7)['rgb'];
                } else {
                    $colors[$idx] = $matches[0];
                }
                $sections[$idx] = (string) preg_replace($color_regex, '', $sections[$idx]);
            }
            if (preg_match($cond_regex, $sections[$idx], $matches)) {
                $conditionOperations[$idx] = $matches[1];
                $conditionComparisonValues[$idx] = $matches[2];
                $sections[$idx] = (string) preg_replace($cond_regex, '', $sections[$idx]);
            }
        }
        $color = $colors[0];
        $format = $sections[0];
        $absval = $value;
        switch ($sectionCount) {
            case 2:
                $absval = abs($value);
                if (!self::splitFormatComparison($value, $conditionOperations[0], $conditionComparisonValues[0], '>=', 0)) {
                    $color = $colors[1];
                    $format = $sections[1];
                }

                break;
            case 3:
            case 4:
                $absval = abs($value);
                if (!self::splitFormatComparison($value, $conditionOperations[0], $conditionComparisonValues[0], '>', 0)) {
                    if (self::splitFormatComparison($value, $conditionOperations[1], $conditionComparisonValues[1], '<', 0)) {
                        $color = $colors[1];
                        $format = $sections[1];
                    } else {
                        $color = $colors[2];
                        $format = $sections[2];
                    }
                }

                break;
        }

        return [$color, $format, $absval];
    }

        public static function toFormattedString($value, string $format, ?array $callBack = null): string
    {
        if (is_bool($value)) {
            return $value ? Calculation::getTRUE() : Calculation::getFALSE();
        }
                        if (preg_match(self::SECTION_SPLIT, $format) === 0 && preg_match(self::SYMBOL_AT, $format) === 1) {
            return str_replace('"', '', preg_replace(self::SYMBOL_AT, (string) $value, $format) ?? '');
        }

                if (!is_numeric($value)) {
            return (string) $value;
        }

                        if (($format === NumberFormat::FORMAT_GENERAL) || ($format === NumberFormat::FORMAT_TEXT)) {
            return self::adjustSeparators((string) $value);
        }

                $format = (string) preg_replace('/^\[\$-[^\]]*\]/', '', $format);

        $format = (string) preg_replace_callback(
            '/(["])(?:(?=(\\\\?))\\2.)*?\\1/u',
            fn (array $matches): string => str_replace('.', chr(0x00), $matches[0]),
            $format
        );

                $format = (string) preg_replace('/(\\\(((.)(?!((AM\/PM)|(A\/P))))|([^ ])))(?=(?:[^"]|"[^"]*")*$)/ui', '"${2}"', $format);

                $sections = preg_split(self::SECTION_SPLIT, $format) ?: [];

        [$colors, $format, $value] = self::splitFormatForSectionSelection($sections, $value);

                        $format = (string) preg_replace('/_.?/ui', ' ', $format);

                if (
                        (preg_match('/(\[\$[A-Z]*-[0-9A-F]*\])*[hmsdy](?=(?:[^"]|"[^"]*")*$)/miu', $format))
                        && (preg_match('/[0\?#]\.(?![^\[]*\])/miu', $format) === 0)
        ) {
                        $value = DateFormatter::format($value, $format);
        } else {
            if (str_starts_with($format, '"') && str_ends_with($format, '"') && substr_count($format, '"') === 2) {
                $value = substr($format, 1, -1);
            } elseif (preg_match('/[0#, ]%/', $format)) {
                                $value = PercentageFormatter::format(0 + (float) $value, $format);
            } else {
                $value = NumberFormatter::format($value, $format);
            }
        }

                if ($callBack !== null) {
            [$writerInstance, $function] = $callBack;
            $value = $writerInstance->$function($value, $colors);
        }

        return str_replace(chr(0x00), '.', $value);
    }
}
