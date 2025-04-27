<?php

namespace PhpOffice\PhpSpreadsheet\Worksheet;

use PhpOffice\PhpSpreadsheet\Cell\AddressRange;
use PhpOffice\PhpSpreadsheet\Cell\CellAddress;
use PhpOffice\PhpSpreadsheet\Cell\CellRange;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;

class Validations
{
        public static function validateCellAddress($cellAddress): string
    {
        if (is_string($cellAddress)) {
            [$worksheet, $address] = Worksheet::extractSheetTitle($cellAddress, true);

            return empty($worksheet) ? strtoupper("$address") : $worksheet . '!' . strtoupper("$address");
        }

        if (is_array($cellAddress)) {
            $cellAddress = CellAddress::fromColumnRowArray($cellAddress);
        }

        return (string) $cellAddress;
    }

        public static function validateCellOrCellRange($cellRange): string
    {
        if (is_string($cellRange) || is_numeric($cellRange)) {
                                    $cellRange = (string) preg_replace('/^([A-Z]+|\d+)$/', '${1}:${1}', (string) $cellRange);
        } elseif (is_object($cellRange) && $cellRange instanceof CellAddress) {
            $cellRange = new CellRange($cellRange, $cellRange);
        }

        return self::validateCellRange($cellRange);
    }

    private const SETMAXROW = '${1}1:${2}' . AddressRange::MAX_ROW;
    private const SETMAXCOL = 'A${1}:' . AddressRange::MAX_COLUMN . '${2}';

        public static function validateCellRange(AddressRange|array|string $cellRange): string
    {
        if (is_string($cellRange)) {
            [$worksheet, $addressRange] = Worksheet::extractSheetTitle($cellRange, true);

                                    $addressRange = (string) preg_replace(
                ['/^([A-Z]+):([A-Z]+)$/i', '/^(\\d+):(\\d+)$/'],
                [self::SETMAXROW, self::SETMAXCOL],
                $addressRange ?? ''
            );

            return empty($worksheet) ? strtoupper($addressRange) : $worksheet . '!' . strtoupper($addressRange);
        }

        if (is_array($cellRange)) {
            switch (count($cellRange)) {
                case 4:
                    $from = [$cellRange[0], $cellRange[1]];
                    $to = [$cellRange[2], $cellRange[3]];

                    break;
                case 2:
                    $from = [$cellRange[0], $cellRange[1]];
                    $to = [$cellRange[0], $cellRange[1]];

                    break;
                default:
                    throw new SpreadsheetException('CellRange array length must be 2 or 4');
            }
            $cellRange = new CellRange(CellAddress::fromColumnRowArray($from), CellAddress::fromColumnRowArray($to));
        }

        return (string) $cellRange;
    }

    public static function definedNameToCoordinate(string $coordinate, Worksheet $worksheet): string
    {
                $coordinate = strtoupper($coordinate);
                $testCoordinate = (string) preg_replace('/^=/', '', $coordinate);
        $defined = $worksheet->getParentOrThrow()->getDefinedName($testCoordinate, $worksheet);
        if ($defined !== null) {
            if ($defined->getWorksheet() === $worksheet && !$defined->isFormula()) {
                $coordinate = (string) preg_replace('/^=/', '', $defined->getValue());
            }
        }

        return $coordinate;
    }
}
