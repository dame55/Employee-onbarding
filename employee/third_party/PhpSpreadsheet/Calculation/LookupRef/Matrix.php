<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\LookupRef;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Matrix
{
    use ArrayEnabled;

        public static function isColumnVector(array $values): bool
    {
        return count($values, COUNT_RECURSIVE) === (count($values, COUNT_NORMAL) * 2);
    }

        public static function isRowVector(array $values): bool
    {
        return count($values, COUNT_RECURSIVE) > 1
            && (count($values, COUNT_NORMAL) === 1 || count($values, COUNT_RECURSIVE) === count($values, COUNT_NORMAL));
    }

        public static function transpose($matrixData): array
    {
        $returnMatrix = [];
        if (!is_array($matrixData)) {
            $matrixData = [[$matrixData]];
        }

        $column = 0;
        foreach ($matrixData as $matrixRow) {
            $row = 0;
            foreach ($matrixRow as $matrixCell) {
                $returnMatrix[$row][$column] = $matrixCell;
                ++$row;
            }
            ++$column;
        }

        return $returnMatrix;
    }

        public static function index(mixed $matrix, mixed $rowNum = 0, mixed $columnNum = null): mixed
    {
        if (is_array($rowNum) || is_array($columnNum)) {
            return self::evaluateArrayArgumentsSubsetFrom([self::class, __FUNCTION__], 1, $matrix, $rowNum, $columnNum);
        }

        $rowNum = $rowNum ?? 0;
        $originalColumnNum = $columnNum;
        $columnNum = $columnNum ?? 0;

        try {
            $rowNum = LookupRefValidations::validatePositiveInt($rowNum);
            $columnNum = LookupRefValidations::validatePositiveInt($columnNum);
        } catch (Exception $e) {
            return $e->getMessage();
        }

        if (!is_array($matrix) || ($rowNum > count($matrix))) {
            return ExcelError::REF();
        }

        $rowKeys = array_keys($matrix);
        $columnKeys = @array_keys($matrix[$rowKeys[0]]);

        if ($columnNum > count($columnKeys)) {
            return ExcelError::REF();
        }
        if ($originalColumnNum === null && 1 < count($columnKeys)) {
            return ExcelError::REF();
        }

        if ($columnNum === 0) {
            return self::extractRowValue($matrix, $rowKeys, $rowNum);
        }

        $columnNum = $columnKeys[--$columnNum];
        if ($rowNum === 0) {
            return array_map(
                fn ($value): array => [$value],
                array_column($matrix, $columnNum)
            );
        }
        $rowNum = $rowKeys[--$rowNum];

        return $matrix[$rowNum][$columnNum];
    }

    private static function extractRowValue(array $matrix, array $rowKeys, int $rowNum): mixed
    {
        if ($rowNum === 0) {
            return $matrix;
        }

        $rowNum = $rowKeys[--$rowNum];
        $row = $matrix[$rowNum];
        if (is_array($row)) {
            return [$rowNum => $row];
        }

        return $row;
    }
}
