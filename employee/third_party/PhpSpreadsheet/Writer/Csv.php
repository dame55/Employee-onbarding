<?php

namespace PhpOffice\PhpSpreadsheet\Writer;

use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Csv extends BaseWriter
{
        private Spreadsheet $spreadsheet;

        private string $delimiter = ',';

        private string $enclosure = '"';

        private string $lineEnding = PHP_EOL;

        private int $sheetIndex = 0;

        private bool $useBOM = false;

        private bool $includeSeparatorLine = false;

        private bool $excelCompatibility = false;

        private string $outputEncoding = '';

        public function __construct(Spreadsheet $spreadsheet)
    {
        $this->spreadsheet = $spreadsheet;
    }

        public function save($filename, int $flags = 0): void
    {
        $this->processFlags($flags);

                $sheet = $this->spreadsheet->getSheet($this->sheetIndex);

        $saveDebugLog = Calculation::getInstance($this->spreadsheet)->getDebugLog()->getWriteDebugLog();
        Calculation::getInstance($this->spreadsheet)->getDebugLog()->setWriteDebugLog(false);
        $saveArrayReturnType = Calculation::getArrayReturnType();
        Calculation::setArrayReturnType(Calculation::RETURN_ARRAY_AS_VALUE);

                $this->openFileHandle($filename);

        if ($this->excelCompatibility) {
            $this->setUseBOM(true);             $this->setIncludeSeparatorLine(true);             $this->setEnclosure('"');             $this->setDelimiter(';');             $this->setLineEnding("\r\n");
        }

        if ($this->useBOM) {
                        fwrite($this->fileHandle, "\xEF\xBB\xBF");
        }

        if ($this->includeSeparatorLine) {
                        fwrite($this->fileHandle, 'sep=' . $this->getDelimiter() . $this->lineEnding);
        }

                $maxCol = $sheet->getHighestDataColumn();
        $maxRow = $sheet->getHighestDataRow();

                for ($row = 1; $row <= $maxRow; ++$row) {
                        $cellsArray = $sheet->rangeToArray('A' . $row . ':' . $maxCol . $row, '', $this->preCalculateFormulas);
                        $this->writeLine($this->fileHandle, $cellsArray[0]);
        }

        $this->maybeCloseFileHandle();
        Calculation::setArrayReturnType($saveArrayReturnType);
        Calculation::getInstance($this->spreadsheet)->getDebugLog()->setWriteDebugLog($saveDebugLog);
    }

    public function getDelimiter(): string
    {
        return $this->delimiter;
    }

    public function setDelimiter(string $delimiter): self
    {
        $this->delimiter = $delimiter;

        return $this;
    }

    public function getEnclosure(): string
    {
        return $this->enclosure;
    }

    public function setEnclosure(string $enclosure = '"'): self
    {
        $this->enclosure = $enclosure;

        return $this;
    }

    public function getLineEnding(): string
    {
        return $this->lineEnding;
    }

    public function setLineEnding(string $lineEnding): self
    {
        $this->lineEnding = $lineEnding;

        return $this;
    }

        public function getUseBOM(): bool
    {
        return $this->useBOM;
    }

        public function setUseBOM(bool $useBOM): self
    {
        $this->useBOM = $useBOM;

        return $this;
    }

        public function getIncludeSeparatorLine(): bool
    {
        return $this->includeSeparatorLine;
    }

        public function setIncludeSeparatorLine(bool $includeSeparatorLine): self
    {
        $this->includeSeparatorLine = $includeSeparatorLine;

        return $this;
    }

        public function getExcelCompatibility(): bool
    {
        return $this->excelCompatibility;
    }

        public function setExcelCompatibility(bool $excelCompatibility): self
    {
        $this->excelCompatibility = $excelCompatibility;

        return $this;
    }

    public function getSheetIndex(): int
    {
        return $this->sheetIndex;
    }

    public function setSheetIndex(int $sheetIndex): self
    {
        $this->sheetIndex = $sheetIndex;

        return $this;
    }

    public function getOutputEncoding(): string
    {
        return $this->outputEncoding;
    }

    public function setOutputEncoding(string $outputEnconding): self
    {
        $this->outputEncoding = $outputEnconding;

        return $this;
    }

    private bool $enclosureRequired = true;

    public function setEnclosureRequired(bool $value): self
    {
        $this->enclosureRequired = $value;

        return $this;
    }

    public function getEnclosureRequired(): bool
    {
        return $this->enclosureRequired;
    }

        private static function elementToString(mixed $element): string
    {
        if (is_bool($element)) {
            return $element ? 'TRUE' : 'FALSE';
        }

        return (string) $element;
    }

        private function writeLine($fileHandle, array $values): void
    {
                $delimiter = '';

                $line = '';

        foreach ($values as $element) {
            $element = self::elementToString($element);
                        $line .= $delimiter;
            $delimiter = $this->delimiter;
                        $enclosure = $this->enclosure;
            if ($enclosure) {
                                                if (!$this->enclosureRequired && strpbrk($element, "$delimiter$enclosure\n") === false) {
                    $enclosure = '';
                } else {
                    $element = str_replace($enclosure, $enclosure . $enclosure, $element);
                }
            }
                        $line .= $enclosure . $element . $enclosure;
        }

                $line .= $this->lineEnding;

                if ($this->outputEncoding != '') {
            $line = mb_convert_encoding($line, $this->outputEncoding);
        }
        fwrite($fileHandle, $line);
    }
}
