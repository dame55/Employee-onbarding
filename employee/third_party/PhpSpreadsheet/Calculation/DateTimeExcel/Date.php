<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel;

use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Exception;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Shared\Date as SharedDateHelper;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;

class Date
{
    use ArrayEnabled;

        public static function fromYMD(array|float|int|string $year, array|float|int|string $month, array|float|int|string $day): mixed
    {
        if (is_array($year) || is_array($month) || is_array($day)) {
            return self::evaluateArrayArguments([self::class, __FUNCTION__], $year, $month, $day);
        }

        $baseYear = SharedDateHelper::getExcelCalendar();

        try {
            $year = self::getYear($year, $baseYear);
            $month = self::getMonth($month);
            $day = self::getDay($day);
            self::adjustYearMonth($year, $month, $baseYear);
        } catch (Exception $e) {
            return $e->getMessage();
        }

                $excelDateValue = SharedDateHelper::formattedPHPToExcel($year, $month, $day);

        return Helpers::returnIn3FormatsFloat($excelDateValue);
    }

        private static function getYear(mixed $year, int $baseYear): int
    {
        $year = ($year !== null) ? StringHelper::testStringAsNumeric((string) $year) : 0;
        if (!is_numeric($year)) {
            throw new Exception(ExcelError::VALUE());
        }
        $year = (int) $year;

        if ($year < ($baseYear - 1900)) {
            throw new Exception(ExcelError::NAN());
        }
        if ((($baseYear - 1900) !== 0) && ($year < $baseYear) && ($year >= 1900)) {
            throw new Exception(ExcelError::NAN());
        }

        if (($year < $baseYear) && ($year >= ($baseYear - 1900))) {
            $year += 1900;
        }

        return (int) $year;
    }

        private static function getMonth(mixed $month): int
    {
        if (($month !== null) && (!is_numeric($month))) {
            $month = SharedDateHelper::monthStringToNumber($month);
        }

        $month = ($month !== null) ? StringHelper::testStringAsNumeric((string) $month) : 0;
        if (!is_numeric($month)) {
            throw new Exception(ExcelError::VALUE());
        }

        return (int) $month;
    }

        private static function getDay(mixed $day): int
    {
        if (($day !== null) && (!is_numeric($day))) {
            $day = SharedDateHelper::dayStringToNumber($day);
        }

        $day = ($day !== null) ? StringHelper::testStringAsNumeric((string) $day) : 0;
        if (!is_numeric($day)) {
            throw new Exception(ExcelError::VALUE());
        }

        return (int) $day;
    }

    private static function adjustYearMonth(int &$year, int &$month, int $baseYear): void
    {
        if ($month < 1) {
                        --$month;
            $year += ceil($month / 12) - 1;
            $month = 13 - abs($month % 12);
        } elseif ($month > 12) {
                        $year += floor($month / 12);
            $month = ($month % 12);
        }

                if (($year < $baseYear) || ($year >= 10000)) {
            throw new Exception(ExcelError::NAN());
        }
    }
}
