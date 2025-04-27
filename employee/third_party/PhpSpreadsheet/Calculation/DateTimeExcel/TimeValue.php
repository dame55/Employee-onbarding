<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel;

use Datetime;
use PhpOffice\PhpSpreadsheet\Calculation\ArrayEnabled;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Shared\Date as SharedDateHelper;

class TimeValue
{
    use ArrayEnabled;

        public static function fromString(null|array|string|int|bool $timeValue): array|string|Datetime|int|float
    {
        if (is_array($timeValue)) {
            return self::evaluateSingleArgumentArray([self::class, __FUNCTION__], $timeValue);
        }

                if (is_string($timeValue) && preg_match('/\\d/', $timeValue) !== 1) {
            return ExcelError::VALUE();
        }

        $timeValue = trim((string) $timeValue, '"');
        $timeValue = str_replace(['/', '.'], '-', $timeValue);

        $arraySplit = preg_split('/[\/:\-\s]/', $timeValue) ?: [];
        if ((count($arraySplit) == 2 || count($arraySplit) == 3) && $arraySplit[0] > 24) {
            $arraySplit[0] = ($arraySplit[0] % 24);
            $timeValue = implode(':', $arraySplit);
        }

        $PHPDateArray = Helpers::dateParse($timeValue);
        $retValue = ExcelError::VALUE();
        if (Helpers::dateParseSucceeded($PHPDateArray)) {
            $hour = $PHPDateArray['hour'];
            $minute = $PHPDateArray['minute'];
            $second = $PHPDateArray['second'];
                        $excelDateValue = SharedDateHelper::formattedPHPToExcel(1900, 1, 1, $hour, $minute, $second) - 1;

            $retType = Functions::getReturnDateType();
            if ($retType === Functions::RETURNDATE_EXCEL) {
                $retValue = (float) $excelDateValue;
            } elseif ($retType === Functions::RETURNDATE_UNIX_TIMESTAMP) {
                $retValue = (int) SharedDateHelper::excelToTimestamp($excelDateValue + 25569) - 3600;
            } else {
                $retValue = new DateTime('1900-01-01 ' . $PHPDateArray['hour'] . ':' . $PHPDateArray['minute'] . ':' . $PHPDateArray['second']);
            }
        }

        return $retValue;
    }
}
