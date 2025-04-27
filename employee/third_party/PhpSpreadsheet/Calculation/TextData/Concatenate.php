<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\TextData;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ErrorValue;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;

class Concatenate
{
    use ArrayEnabled;

        public static function CONCATENATE(...$args): string
    {
        $returnValue = '';

                $aArgs = Functions::flattenArray($args);

        foreach ($aArgs as $arg) {
            $value = Helpers::extractString($arg);
            if (ErrorValue::isError($value)) {
                $returnValue = $value;

                break;
            }
            $returnValue .= Helpers::extractString($arg);
            if (StringHelper::countCharacters($returnValue) > DataType::MAX_STRING_LENGTH) {
                $returnValue = ExcelError::CALC();

                break;
            }
        }

        return $returnValue;
    }

        public static function TEXTJOIN(mixed $delimiter = '', mixed $ignoreEmpty = true, mixed ...$args): array|string
    {
        if (is_array($delimiter) || is_array($ignoreEmpty)) {
            return self::evaluateArrayArgumentsSubset(
                [self::class, __FUNCTION__],
                2,
                $delimiter,
                $ignoreEmpty,
                ...$args
            );
        }

        $delimiter ??= '';
        $ignoreEmpty ??= true;
        $aArgs = Functions::flattenArray($args);
        $returnValue = self::evaluateTextJoinArray($ignoreEmpty, $aArgs);

        $returnValue ??= implode($delimiter, $aArgs);
        if (StringHelper::countCharacters($returnValue) > DataType::MAX_STRING_LENGTH) {
            $returnValue = ExcelError::CALC();
        }

        return $returnValue;
    }

    private static function evaluateTextJoinArray(bool $ignoreEmpty, array &$aArgs): ?string
    {
        foreach ($aArgs as $key => &$arg) {
            $value = Helpers::extractString($arg);
            if (ErrorValue::isError($value)) {
                return $value;
            }

            if ($ignoreEmpty === true && ((is_string($arg) && trim($arg) === '') || $arg === null)) {
                unset($aArgs[$key]);
            } elseif (is_bool($arg)) {
                $arg = Helpers::convertBooleanValue($arg);
            }
        }

        return null;
    }

        public static function builtinREPT(mixed $stringValue, mixed $repeatCount): array|string
    {
        if (is_array($stringValue) || is_array($repeatCount)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $stringValue, $repeatCount);
        }

        $stringValue = Helpers::extractString($stringValue);

        if (!is_numeric($repeatCount) || $repeatCount < 0) {
            $returnValue = ExcelError::VALUE();
        } elseif (ErrorValue::isError($stringValue)) {
            $returnValue = $stringValue;
        } else {
            $returnValue = str_repeat($stringValue, (int) $repeatCount);
            if (StringHelper::countCharacters($returnValue) > DataType::MAX_STRING_LENGTH) {
                $returnValue = ExcelError::VALUE();             }
        }

        return $returnValue;
    }
}
