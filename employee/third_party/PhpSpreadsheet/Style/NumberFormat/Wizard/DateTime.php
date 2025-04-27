<?php

namespace PhpOffice\PhpSpreadsheet\Style\NumberFormat\Wizard;

class DateTime extends DateTimeWizard
{
        protected array $separators;

        protected array $formatBlocks;

        public function __construct($separators, ...$formatBlocks)
    {
        $this->separators = $this->padSeparatorArray(
            is_array($separators) ? $separators : [$separators],
            count($formatBlocks) - 1
        );
        $this->formatBlocks = array_map([$this, 'mapFormatBlocks'], $formatBlocks);
    }

    private function mapFormatBlocks(DateTimeWizard|string $value): string
    {
                if (is_object($value)) {
                        return $value;
        }

                return $this->wrapLiteral($value);
    }

    public function format(): string
    {
        return implode('', array_map([$this, 'intersperse'], $this->formatBlocks, $this->separators));
    }
}
