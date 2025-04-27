<?php

namespace PhpOffice\PhpSpreadsheet\Style;

class NumberFormat extends Supervisor
{
        const FORMAT_GENERAL = 'General';

    const FORMAT_TEXT = '@';

    const FORMAT_NUMBER = '0';
    const FORMAT_NUMBER_0 = '0.0';
    const FORMAT_NUMBER_00 = '0.00';
    const FORMAT_NUMBER_COMMA_SEPARATED1 = '#,##0.00';
    const FORMAT_NUMBER_COMMA_SEPARATED2 = '#,##0.00_-';

    const FORMAT_PERCENTAGE = '0%';
    const FORMAT_PERCENTAGE_0 = '0.0%';
    const FORMAT_PERCENTAGE_00 = '0.00%';

    const FORMAT_DATE_YYYYMMDD = 'yyyy-mm-dd';
    const FORMAT_DATE_DDMMYYYY = 'dd/mm/yyyy';
    const FORMAT_DATE_DMYSLASH = 'd/m/yy';
    const FORMAT_DATE_DMYMINUS = 'd-m-yy';
    const FORMAT_DATE_DMMINUS = 'd-m';
    const FORMAT_DATE_MYMINUS = 'm-yy';
    const FORMAT_DATE_XLSX14 = 'mm-dd-yy';
    const FORMAT_DATE_XLSX15 = 'd-mmm-yy';
    const FORMAT_DATE_XLSX16 = 'd-mmm';
    const FORMAT_DATE_XLSX17 = 'mmm-yy';
    const FORMAT_DATE_XLSX22 = 'm/d/yy h:mm';
    const FORMAT_DATE_DATETIME = 'd/m/yy h:mm';
    const FORMAT_DATE_TIME1 = 'h:mm AM/PM';
    const FORMAT_DATE_TIME2 = 'h:mm:ss AM/PM';
    const FORMAT_DATE_TIME3 = 'h:mm';
    const FORMAT_DATE_TIME4 = 'h:mm:ss';
    const FORMAT_DATE_TIME5 = 'mm:ss';
    const FORMAT_DATE_TIME6 = 'h:mm:ss';
    const FORMAT_DATE_TIME7 = 'i:s.S';
    const FORMAT_DATE_TIME8 = 'h:mm:ss;@';
    const FORMAT_DATE_YYYYMMDDSLASH = 'yyyy/mm/dd;@';

    const DATE_TIME_OR_DATETIME_ARRAY = [
        self::FORMAT_DATE_YYYYMMDD,
        self::FORMAT_DATE_DDMMYYYY,
        self::FORMAT_DATE_DMYSLASH,
        self::FORMAT_DATE_DMYMINUS,
        self::FORMAT_DATE_DMMINUS,
        self::FORMAT_DATE_MYMINUS,
        self::FORMAT_DATE_XLSX14,
        self::FORMAT_DATE_XLSX15,
        self::FORMAT_DATE_XLSX16,
        self::FORMAT_DATE_XLSX17,
        self::FORMAT_DATE_XLSX22,
        self::FORMAT_DATE_DATETIME,
        self::FORMAT_DATE_TIME1,
        self::FORMAT_DATE_TIME2,
        self::FORMAT_DATE_TIME3,
        self::FORMAT_DATE_TIME4,
        self::FORMAT_DATE_TIME5,
        self::FORMAT_DATE_TIME6,
        self::FORMAT_DATE_TIME7,
        self::FORMAT_DATE_TIME8,
        self::FORMAT_DATE_YYYYMMDDSLASH,
    ];
    const TIME_OR_DATETIME_ARRAY = [
        self::FORMAT_DATE_XLSX22,
        self::FORMAT_DATE_DATETIME,
        self::FORMAT_DATE_TIME1,
        self::FORMAT_DATE_TIME2,
        self::FORMAT_DATE_TIME3,
        self::FORMAT_DATE_TIME4,
        self::FORMAT_DATE_TIME5,
        self::FORMAT_DATE_TIME6,
        self::FORMAT_DATE_TIME7,
        self::FORMAT_DATE_TIME8,
    ];

    const FORMAT_CURRENCY_USD_INTEGER = '$#,##0_-';
    const FORMAT_CURRENCY_USD = '$#,##0.00_-';
    const FORMAT_CURRENCY_EUR_INTEGER = '#,##0_-[$€]';
    const FORMAT_CURRENCY_EUR = '#,##0.00_-[$€]';
    const FORMAT_ACCOUNTING_USD = '_("$"* #,##0.00_);_("$"* \(#,##0.00\);_("$"* "-"??_);_(@_)';
    const FORMAT_ACCOUNTING_EUR = '_("€"* #,##0.00_);_("€"* \(#,##0.00\);_("€"* "-"??_);_(@_)';

        protected static array $builtInFormats;

        protected static array $flippedBuiltInFormats;

        protected ?string $formatCode = self::FORMAT_GENERAL;

        protected $builtInFormatCode = 0;

        public function __construct(bool $isSupervisor = false, bool $isConditional = false)
    {
                parent::__construct($isSupervisor);

        if ($isConditional) {
            $this->formatCode = null;
            $this->builtInFormatCode = false;
        }
    }

        public function getSharedComponent(): self
    {
                $parent = $this->parent;

        return $parent->getSharedComponent()->getNumberFormat();
    }

        public function getStyleArray(array $array): array
    {
        return ['numberFormat' => $array];
    }

        public function applyFromArray(array $styleArray): static
    {
        if ($this->isSupervisor) {
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($this->getStyleArray($styleArray));
        } else {
            if (isset($styleArray['formatCode'])) {
                $this->setFormatCode($styleArray['formatCode']);
            }
        }

        return $this;
    }

        public function getFormatCode(): ?string
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getFormatCode();
        }
        if (is_int($this->builtInFormatCode)) {
            return self::builtInFormatCode($this->builtInFormatCode);
        }

        return $this->formatCode;
    }

        public function setFormatCode(string $formatCode): static
    {
        if ($formatCode == '') {
            $formatCode = self::FORMAT_GENERAL;
        }
        if ($this->isSupervisor) {
            $styleArray = $this->getStyleArray(['formatCode' => $formatCode]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
        } else {
            $this->formatCode = $formatCode;
            $this->builtInFormatCode = self::builtInFormatCodeIndex($formatCode);
        }

        return $this;
    }

        public function getBuiltInFormatCode()
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getBuiltInFormatCode();
        }

        return $this->builtInFormatCode;
    }

        public function setBuiltInFormatCode(int $formatCodeIndex): static
    {
        if ($this->isSupervisor) {
            $styleArray = $this->getStyleArray(['formatCode' => self::builtInFormatCode($formatCodeIndex)]);
            $this->getActiveSheet()->getStyle($this->getSelectedCells())->applyFromArray($styleArray);
        } else {
            $this->builtInFormatCode = $formatCodeIndex;
            $this->formatCode = self::builtInFormatCode($formatCodeIndex);
        }

        return $this;
    }

        private static function fillBuiltInFormatCodes(): void
    {
                                                                                                                                                                        
                if (empty(self::$builtInFormats)) {
            self::$builtInFormats = [];

                        self::$builtInFormats[0] = self::FORMAT_GENERAL;
            self::$builtInFormats[1] = '0';
            self::$builtInFormats[2] = '0.00';
            self::$builtInFormats[3] = '#,##0';
            self::$builtInFormats[4] = '#,##0.00';

            self::$builtInFormats[9] = '0%';
            self::$builtInFormats[10] = '0.00%';
            self::$builtInFormats[11] = '0.00E+00';
            self::$builtInFormats[12] = '# ?/?';
            self::$builtInFormats[13] = '# ??/??';
            self::$builtInFormats[14] = 'm/d/yyyy';             self::$builtInFormats[15] = 'd-mmm-yy';
            self::$builtInFormats[16] = 'd-mmm';
            self::$builtInFormats[17] = 'mmm-yy';
            self::$builtInFormats[18] = 'h:mm AM/PM';
            self::$builtInFormats[19] = 'h:mm:ss AM/PM';
            self::$builtInFormats[20] = 'h:mm';
            self::$builtInFormats[21] = 'h:mm:ss';
            self::$builtInFormats[22] = 'm/d/yyyy h:mm'; 
            self::$builtInFormats[37] = '#,##0_);(#,##0)';             self::$builtInFormats[38] = '#,##0_);[Red](#,##0)';             self::$builtInFormats[39] = '#,##0.00_);(#,##0.00)';             self::$builtInFormats[40] = '#,##0.00_);[Red](#,##0.00)'; 
            self::$builtInFormats[44] = '_("$"* #,##0.00_);_("$"* \(#,##0.00\);_("$"* "-"??_);_(@_)';
            self::$builtInFormats[45] = 'mm:ss';
            self::$builtInFormats[46] = '[h]:mm:ss';
            self::$builtInFormats[47] = 'mm:ss.0';             self::$builtInFormats[48] = '##0.0E+0';
            self::$builtInFormats[49] = '@';

                        self::$builtInFormats[27] = '[$-404]e/m/d';
            self::$builtInFormats[30] = 'm/d/yy';
            self::$builtInFormats[36] = '[$-404]e/m/d';
            self::$builtInFormats[50] = '[$-404]e/m/d';
            self::$builtInFormats[57] = '[$-404]e/m/d';

                        self::$builtInFormats[59] = 't0';
            self::$builtInFormats[60] = 't0.00';
            self::$builtInFormats[61] = 't#,##0';
            self::$builtInFormats[62] = 't#,##0.00';
            self::$builtInFormats[67] = 't0%';
            self::$builtInFormats[68] = 't0.00%';
            self::$builtInFormats[69] = 't# ?/?';
            self::$builtInFormats[70] = 't# ??/??';

                        self::$builtInFormats[28] = '[$-411]ggge"年"m"月"d"日"';
            self::$builtInFormats[29] = '[$-411]ggge"年"m"月"d"日"';
            self::$builtInFormats[31] = 'yyyy"年"m"月"d"日"';
            self::$builtInFormats[32] = 'h"時"mm"分"';
            self::$builtInFormats[33] = 'h"時"mm"分"ss"秒"';
            self::$builtInFormats[34] = 'yyyy"年"m"月"';
            self::$builtInFormats[35] = 'm"月"d"日"';
            self::$builtInFormats[51] = '[$-411]ggge"年"m"月"d"日"';
            self::$builtInFormats[52] = 'yyyy"年"m"月"';
            self::$builtInFormats[53] = 'm"月"d"日"';
            self::$builtInFormats[54] = '[$-411]ggge"年"m"月"d"日"';
            self::$builtInFormats[55] = 'yyyy"年"m"月"';
            self::$builtInFormats[56] = 'm"月"d"日"';
            self::$builtInFormats[58] = '[$-411]ggge"年"m"月"d"日"';

                        self::$flippedBuiltInFormats = array_flip(self::$builtInFormats);
        }
    }

        public static function builtInFormatCode(int $index): string
    {
                $index = (int) $index;

                self::fillBuiltInFormatCodes();

                if (isset(self::$builtInFormats[$index])) {
            return self::$builtInFormats[$index];
        }

        return '';
    }

        public static function builtInFormatCodeIndex(string $formatCodeIndex)
    {
                self::fillBuiltInFormatCodes();

                if (array_key_exists($formatCodeIndex, self::$flippedBuiltInFormats)) {
            return self::$flippedBuiltInFormats[$formatCodeIndex];
        }

        return false;
    }

        public function getHashCode(): string
    {
        if ($this->isSupervisor) {
            return $this->getSharedComponent()->getHashCode();
        }

        return md5(
            $this->formatCode
            . $this->builtInFormatCode
            . __CLASS__
        );
    }

        public static function toFormattedString(mixed $value, string $format, ?array $callBack = null): string
    {
        return NumberFormat\Formatter::toFormattedString($value, $format, $callBack);
    }

    protected function exportArray1(): array
    {
        $exportedArray = [];
        $this->exportArray2($exportedArray, 'formatCode', $this->getFormatCode());

        return $exportedArray;
    }
}
