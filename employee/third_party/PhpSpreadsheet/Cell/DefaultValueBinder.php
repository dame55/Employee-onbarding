<?php

namespace PhpOffice\PhpSpreadsheet\Cell;

use DateTimeInterface;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use Stringable;

class DefaultValueBinder implements IValueBinder
{
        public function bindValue(Cell $cell, $value): bool
    {
                if (is_string($value)) {
            $value = StringHelper::sanitizeUTF8($value);
        } elseif ($value === null || is_scalar($value) || $value instanceof RichText) {
                    } elseif ($value instanceof DateTimeInterface) {
            $value = $value->format('Y-m-d H:i:s');
        } elseif ($value instanceof Stringable) {
            $value = (string) $value;
        } else {
            throw new SpreadsheetException('Unable to bind unstringable ' . gettype($value));
        }

                $cell->setValueExplicit($value, static::dataTypeForValue($value));

                return true;
    }

        public static function dataTypeForValue(mixed $value): string
    {
                if ($value === null) {
            return DataType::TYPE_NULL;
        } elseif (is_float($value) || is_int($value)) {
            return DataType::TYPE_NUMERIC;
        } elseif (is_bool($value)) {
            return DataType::TYPE_BOOL;
        } elseif ($value === '') {
            return DataType::TYPE_STRING;
        } elseif ($value instanceof RichText) {
            return DataType::TYPE_INLINE;
        } elseif (is_string($value) && strlen($value) > 1 && $value[0] === '=') {
            return DataType::TYPE_FORMULA;
        } elseif (preg_match('/^[\+\-]?(\d+\\.?\d*|\d*\\.?\d+)([Ee][\-\+]?[0-2]?\d{1,3})?$/', $value)) {
            $tValue = ltrim($value, '+-');
            if (is_string($value) && strlen($tValue) > 1 && $tValue[0] === '0' && $tValue[1] !== '.') {
                return DataType::TYPE_STRING;
            } elseif ((!str_contains($value, '.')) && ($value > PHP_INT_MAX)) {
                return DataType::TYPE_STRING;
            } elseif (!is_numeric($value)) {
                return DataType::TYPE_STRING;
            }

            return DataType::TYPE_NUMERIC;
        } elseif (is_string($value)) {
            $errorCodes = DataType::getErrorCodes();
            if (isset($errorCodes[$value])) {
                return DataType::TYPE_ERROR;
            }
        }

        return DataType::TYPE_STRING;
    }
}
