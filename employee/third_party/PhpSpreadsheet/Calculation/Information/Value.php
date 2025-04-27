<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\Information;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class Value
{
    use ArrayEnabled;

        public static function isBlank(mixed $value = null): array|bool
    {
        if (is_array($value)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $value);
        }

        return $value === null;
    }

        public static function isRef(mixed $value, ?Cell $cell = null): bool
    {
        if ($cell === null || $value === $cell->getCoordinate()) {
            return false;
        }

        $cellValue = Functions::trimTrailingRange($value);
        if (preg_match('/^' . Calculation::CALCULATION_REGEXP_CELLREF . '$/ui', $cellValue) === 1) {
            [$worksheet, $cellValue] = Worksheet::extractSheetTitle($cellValue, true);
            if (!empty($worksheet) && $cell->getWorksheet()->getParentOrThrow()->getSheetByName($worksheet) === null) {
                return false;
            }
            [$column, $row] = Coordinate::indexesFromString($cellValue ?? '');
            if ($column > 16384 || $row > 1048576) {
                return false;
            }

            return true;
        }

        $namedRange = $cell->getWorksheet()->getParentOrThrow()->getNamedRange($value);

        return $namedRange instanceof NamedRange;
    }

        public static function isEven(mixed $value = null): array|string|bool
    {
        if (is_array($value)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $value);
        }

        if ($value === null) {
            return ExcelError::NAME();
        } elseif ((is_bool($value)) || ((is_string($value)) && (!is_numeric($value)))) {
            return ExcelError::VALUE();
        }

        return ((int) fmod($value, 2)) === 0;
    }

        public static function isOdd(mixed $value = null): array|string|bool
    {
        if (is_array($value)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $value);
        }

        if ($value === null) {
            return ExcelError::NAME();
        } elseif ((is_bool($value)) || ((is_string($value)) && (!is_numeric($value)))) {
            return ExcelError::VALUE();
        }

        return ((int) fmod($value, 2)) !== 0;
    }

        public static function isNumber(mixed $value = null): array|bool
    {
        if (is_array($value)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $value);
        }

        if (is_string($value)) {
            return false;
        }

        return is_numeric($value);
    }

        public static function isLogical(mixed $value = null): array|bool
    {
        if (is_array($value)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $value);
        }

        return is_bool($value);
    }

        public static function isText(mixed $value = null): array|bool
    {
        if (is_array($value)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $value);
        }

        return is_string($value) && !ErrorValue::isError($value);
    }

        public static function isNonText(mixed $value = null): array|bool
    {
        if (is_array($value)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $value);
        }

        return !self::isText($value);
    }

        public static function isFormula(mixed $cellReference = '', ?Cell $cell = null): array|bool|string
    {
        if ($cell === null) {
            return ExcelError::REF();
        }

        $fullCellReference = Functions::expandDefinedName((string) $cellReference, $cell);

        if (str_contains($cellReference, '!')) {
            $cellReference = Functions::trimSheetFromCellReference($cellReference);
            $cellReferences = Coordinate::extractAllCellReferencesInRange($cellReference);
            if (count($cellReferences) > 1) {
                return self::evaluateArrayArgumentsSubset([self::class, __FUNCTION__], 1, $cellReferences, $cell);
            }
        }

        $fullCellReference = Functions::trimTrailingRange($fullCellReference);

        preg_match('/^' . Calculation::CALCULATION_REGEXP_CELLREF . '$/i', $fullCellReference, $matches);

        $fullCellReference = $matches[6] . $matches[7];
        $worksheetName = str_replace("''", "'", trim($matches[2], "'"));

        $worksheet = (!empty($worksheetName))
            ? $cell->getWorksheet()->getParentOrThrow()->getSheetByName($worksheetName)
            : $cell->getWorksheet();

        return ($worksheet !== null) ? $worksheet->getCell($fullCellReference)->isFormula() : ExcelError::REF();
    }

        public static function asNumber($value = null)
    {
        while (is_array($value)) {
            $value = array_shift($value);
        }

        switch (gettype($value)) {
            case 'double':
            case 'float':
            case 'integer':
                return $value;
            case 'boolean':
                return (int) $value;
            case 'string':
                                if (($value !== '') && ($value[0] == '#')) {
                    return $value;
                }

                break;
        }

        return 0;
    }

        public static function type($value = null): int
    {
        $value = Functions::flattenArrayIndexed($value);
        if (is_array($value) && (count($value) > 1)) {
            end($value);
            $a = key($value);
                        if (Functions::isCellValue($a)) {
                return 16;
                        } elseif (Functions::isMatrixValue($a)) {
                return 64;
            }
        } elseif (empty($value)) {
                        return 1;
        }

        $value = Functions::flattenSingleValue($value);
        if (($value === null) || (is_float($value)) || (is_int($value))) {
            return 1;
        } elseif (is_bool($value)) {
            return 4;
        } elseif (is_array($value)) {
            return 64;
        } elseif (is_string($value)) {
                        if (($value !== '') && ($value[0] == '#')) {
                return 16;
            }

            return 2;
        }

        return 0;
    }
}
