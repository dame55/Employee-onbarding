<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\LookupRef;

use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Cell\Cell;

class Formula
{
        public static function text(mixed $cellReference = '', ?Cell $cell = null): string
    {
        if ($cell === null) {
            return ExcelError::REF();
        }

        preg_match('/^' . Calculation::CALCULATION_REGEXP_CELLREF . '$/i', $cellReference, $matches);

        $cellReference = $matches[6] . $matches[7];
        $worksheetName = trim($matches[3], "'");
        $worksheet = (!empty($worksheetName))
            ? $cell->getWorksheet()->getParentOrThrow()->getSheetByName($worksheetName)
            : $cell->getWorksheet();

        if (
            $worksheet === null
            || !$worksheet->cellExists($cellReference)
            || !$worksheet->getCell($cellReference)->isFormula()
        ) {
            return ExcelError::NA();
        }

        return $worksheet->getCell($cellReference)->getValue();
    }
}
