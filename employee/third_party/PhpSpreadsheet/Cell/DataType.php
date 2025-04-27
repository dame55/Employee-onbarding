<?php

namespace PhpOffice\PhpSpreadsheet\Cell;

use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;

class DataType
{
        const TYPE_STRING2 = 'str';
    const TYPE_STRING = 's';
    const TYPE_FORMULA = 'f';
    const TYPE_NUMERIC = 'n';
    const TYPE_BOOL = 'b';
    const TYPE_NULL = 'null';
    const TYPE_INLINE = 'inlineStr';
    const TYPE_ERROR = 'e';
    const TYPE_ISO_DATE = 'd';

        private static array $errorCodes = [
        '#NULL!' => 0,
        '#DIV/0!' => 1,
        '#VALUE!' => 2,
        '#REF!' => 3,
        '#NAME?' => 4,
        '#NUM!' => 5,
        '#N/A' => 6,
        '#CALC!' => 7,
    ];

    public const MAX_STRING_LENGTH = 32767;

        public static function getErrorCodes(): array
    {
        return self::$errorCodes;
    }

        public static function checkString(null|RichText|string $textValue): RichText|string
    {
        if ($textValue instanceof RichText) {
                        return $textValue;
        }

                $textValue = StringHelper::substring((string) $textValue, 0, self::MAX_STRING_LENGTH);

                $textValue = str_replace(["\r\n", "\r"], "\n", $textValue);

        return $textValue;
    }

        public static function checkErrorCode(mixed $value): string
    {
        $value = (string) $value;

        if (!isset(self::$errorCodes[$value])) {
            $value = '#NULL!';
        }

        return $value;
    }
}
