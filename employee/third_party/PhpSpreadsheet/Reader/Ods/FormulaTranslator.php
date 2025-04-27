<?php

namespace PhpOffice\PhpSpreadsheet\Reader\Ods;

use PhpOffice\PhpSpreadsheet\Calculation\Calculation;

class FormulaTranslator
{
    public static function convertToExcelAddressValue(string $openOfficeAddress): string
    {
        $excelAddress = $openOfficeAddress;

                                $excelAddress = (string) preg_replace(
            [
                '/\$?([^\.]+)\.([^\.]+):\$?([^\.]+)\.([^\.]+)/miu',
                '/\$?([^\.]+)\.([^\.]+):\.([^\.]+)/miu',                 '/\$?([^\.]+)\.([^\.]+)/miu',                 '/\.([^\.]+):\.([^\.]+)/miu',                 '/\.([^\.]+)/miu',             ],
            [
                '$1!$2:$4',
                '$1!$2:$3',
                '$1!$2',
                '$1:$2',
                '$1',
            ],
            $excelAddress
        );

        return $excelAddress;
    }

    public static function convertToExcelFormulaValue(string $openOfficeFormula): string
    {
        $temp = explode(Calculation::FORMULA_STRING_QUOTE, $openOfficeFormula);
        $tKey = false;
        $inMatrixBracesLevel = 0;
        $inFunctionBracesLevel = 0;
        foreach ($temp as &$value) {
                                                $tKey = $tKey === false;
            if ($tKey) {
                $value = (string) preg_replace(
                    [
                        '/\[\$?([^\.]+)\.([^\.]+):\.([^\.]+)\]/miu',                         '/\[\$?([^\.]+)\.([^\.]+)\]/miu',                         '/\[\.([^\.]+):\.([^\.]+)\]/miu',                         '/\[\.([^\.]+)\]/miu',                     ],
                    [
                        '$1!$2:$3',
                        '$1!$2',
                        '$1:$2',
                        '$1',
                    ],
                    $value
                );
                                $value = str_replace('$$', '', $value);

                                $value = Calculation::translateSeparator(';', ',', $value, $inFunctionBracesLevel);

                                $value = Calculation::translateSeparator(
                    ';',
                    ',',
                    $value,
                    $inMatrixBracesLevel,
                    Calculation::FORMULA_OPEN_MATRIX_BRACE,
                    Calculation::FORMULA_CLOSE_MATRIX_BRACE
                );
                $value = Calculation::translateSeparator(
                    '|',
                    ';',
                    $value,
                    $inMatrixBracesLevel,
                    Calculation::FORMULA_OPEN_MATRIX_BRACE,
                    Calculation::FORMULA_CLOSE_MATRIX_BRACE
                );

                $value = (string) preg_replace('/COM\.MICROSOFT\./ui', '', $value);
            }
        }

                $excelFormula = implode('"', $temp);

        return $excelFormula;
    }
}
