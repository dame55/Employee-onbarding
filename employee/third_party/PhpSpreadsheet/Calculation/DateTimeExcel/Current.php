<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel;

use DateTimeImmutable;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;

class Current
{
        public static function today(): mixed
    {
        $dti = new DateTimeImmutable();
        $dateArray = Helpers::dateParse($dti->format('c'));

        return Helpers::dateParseSucceeded($dateArray) ? Helpers::returnIn3FormatsArray($dateArray, true) : ExcelError::VALUE();
    }

        public static function now(): mixed
    {
        $dti = new DateTimeImmutable();
        $dateArray = Helpers::dateParse($dti->format('c'));

        return Helpers::dateParseSucceeded($dateArray) ? Helpers::returnIn3FormatsArray($dateArray) : ExcelError::VALUE();
    }
}
