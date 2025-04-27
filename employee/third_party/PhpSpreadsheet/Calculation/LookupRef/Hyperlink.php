<?php

namespace PhpOffice\PhpSpreadsheet\Calculation\LookupRef;

use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Cell\Cell;

class Hyperlink
{
        public static function set(mixed $linkURL = '', mixed $displayName = null, ?Cell $cell = null): mixed
    {
        $linkURL = ($linkURL === null) ? '' : Functions::flattenSingleValue($linkURL);
        $displayName = ($displayName === null) ? '' : Functions::flattenSingleValue($displayName);

        if ((!is_object($cell)) || (trim($linkURL) == '')) {
            return ExcelError::REF();
        }

        if ((is_object($displayName)) || trim($displayName) == '') {
            $displayName = $linkURL;
        }

        $cell->getHyperlink()->setUrl($linkURL);
        $cell->getHyperlink()->setTooltip($displayName);

        return $displayName;
    }
}
