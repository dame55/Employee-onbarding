<?php

namespace PhpOffice\PhpSpreadsheet\Cell;

use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Calculation\Exception as CalculationException;
use PhpOffice\PhpSpreadsheet\Calculation\Information\ExcelError;
use PhpOffice\PhpSpreadsheet\Collection\Cells;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\Date as SharedDate;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use PhpOffice\PhpSpreadsheet\Style\ConditionalFormatting\CellStyleAssessor;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Stringable;

class Cell implements Stringable
{
        private static ?IValueBinder $valueBinder = null;

        private mixed $value;

        private $calculatedValue;

        private string $dataType;

        private ?Cells $parent;

        private int $xfIndex = 0;

        private mixed $formulaAttributes = null;

    private IgnoredErrors $ignoredErrors;

        public function updateInCollection(): self
    {
        $parent = $this->parent;
        if ($parent === null) {
            throw new SpreadsheetException('Cannot update when cell is not bound to a worksheet');
        }
        $parent->update($this);

        return $this;
    }

    public function detach(): void
    {
        $this->parent = null;
    }

    public function attach(Cells $parent): void
    {
        $this->parent = $parent;
    }

        public function __construct(mixed $value, ?string $dataType, Worksheet $worksheet)
    {
                $this->value = $value;

                $this->parent = $worksheet->getCellCollection();

                if ($dataType !== null) {
            if ($dataType == DataType::TYPE_STRING2) {
                $dataType = DataType::TYPE_STRING;
            }
            $this->dataType = $dataType;
        } elseif (self::getValueBinder()->bindValue($this, $value) === false) {
            throw new SpreadsheetException('Value could not be bound to cell.');
        }
        $this->ignoredErrors = new IgnoredErrors();
    }

        public function getColumn(): string
    {
        $parent = $this->parent;
        if ($parent === null) {
            throw new SpreadsheetException('Cannot get column when cell is not bound to a worksheet');
        }

        return $parent->getCurrentColumn();
    }

        public function getRow(): int
    {
        $parent = $this->parent;
        if ($parent === null) {
            throw new SpreadsheetException('Cannot get row when cell is not bound to a worksheet');
        }

        return $parent->getCurrentRow();
    }

        public function getCoordinate(): string
    {
        $parent = $this->parent;
        if ($parent !== null) {
            $coordinate = $parent->getCurrentCoordinate();
        } else {
            $coordinate = null;
        }
        if ($coordinate === null) {
            throw new SpreadsheetException('Coordinate no longer exists');
        }

        return $coordinate;
    }

        public function getValue(): mixed
    {
        return $this->value;
    }

        public function getFormattedValue(): string
    {
        return (string) NumberFormat::toFormattedString(
            $this->getCalculatedValue(),
            (string) $this->getStyle()->getNumberFormat()->getFormatCode()
        );
    }

    protected static function updateIfCellIsTableHeader(?Worksheet $workSheet, self $cell, mixed $oldValue, mixed $newValue): void
    {
        if (StringHelper::strToLower($oldValue ?? '') === StringHelper::strToLower($newValue ?? '') || $workSheet === null) {
            return;
        }

        foreach ($workSheet->getTableCollection() as $table) {
                        if ($cell->isInRange($table->getRange())) {
                $rangeRowsColumns = Coordinate::getRangeBoundaries($table->getRange());
                if ($cell->getRow() === (int) $rangeRowsColumns[0][1]) {
                    Table\Column::updateStructuredReferences($workSheet, $oldValue, $newValue);
                }

                return;
            }
        }
    }

        public function setValue(mixed $value, ?IValueBinder $binder = null): self
    {
        $binder ??= self::getValueBinder();
        if (!$binder->bindValue($this, $value)) {
            throw new SpreadsheetException('Value could not be bound to cell.');
        }

        return $this;
    }

        public function setValueExplicit(mixed $value, string $dataType = DataType::TYPE_STRING): self
    {
        $oldValue = $this->value;

                switch ($dataType) {
            case DataType::TYPE_NULL:
                $this->value = null;

                break;
            case DataType::TYPE_STRING2:
                $dataType = DataType::TYPE_STRING;
                            case DataType::TYPE_STRING:
                            case DataType::TYPE_INLINE:
                                $this->value = DataType::checkString($value);

                break;
            case DataType::TYPE_NUMERIC:
                if (is_string($value) && !is_numeric($value)) {
                    throw new SpreadsheetException('Invalid numeric value for datatype Numeric');
                }
                $this->value = 0 + $value;

                break;
            case DataType::TYPE_FORMULA:
                $this->value = (string) $value;

                break;
            case DataType::TYPE_BOOL:
                $this->value = (bool) $value;

                break;
            case DataType::TYPE_ISO_DATE:
                $this->value = SharedDate::convertIsoDate($value);
                $dataType = DataType::TYPE_NUMERIC;

                break;
            case DataType::TYPE_ERROR:
                $this->value = DataType::checkErrorCode($value);

                break;
            default:
                throw new SpreadsheetException('Invalid datatype: ' . $dataType);
        }

                $this->dataType = $dataType;

        $this->updateInCollection();
        $cellCoordinate = $this->getCoordinate();
        self::updateIfCellIsTableHeader($this->getParent()?->getParent(), $this, $oldValue, $value);

        return $this->getParent()?->get($cellCoordinate) ?? $this;
    }

    public const CALCULATE_DATE_TIME_ASIS = 0;
    public const CALCULATE_DATE_TIME_FLOAT = 1;
    public const CALCULATE_TIME_FLOAT = 2;

    private static int $calculateDateTimeType = self::CALCULATE_DATE_TIME_ASIS;

    public static function getCalculateDateTimeType(): int
    {
        return self::$calculateDateTimeType;
    }

        public static function setCalculateDateTimeType(int $calculateDateTimeType): void
    {
        self::$calculateDateTimeType = match ($calculateDateTimeType) {
            self::CALCULATE_DATE_TIME_ASIS, self::CALCULATE_DATE_TIME_FLOAT, self::CALCULATE_TIME_FLOAT => $calculateDateTimeType,
            default => throw new CalculationException("Invalid value $calculateDateTimeType for calculated date time type"),
        };
    }

        private function convertDateTimeInt(mixed $result): mixed
    {
        if (is_int($result)) {
            if (self::$calculateDateTimeType === self::CALCULATE_TIME_FLOAT) {
                if (SharedDate::isDateTime($this, $result, false)) {
                    $result = (float) $result;
                }
            } elseif (self::$calculateDateTimeType === self::CALCULATE_DATE_TIME_FLOAT) {
                if (SharedDate::isDateTime($this, $result, true)) {
                    $result = (float) $result;
                }
            }
        }

        return $result;
    }

        public function getCalculatedValue(bool $resetLog = true): mixed
    {
        if ($this->dataType === DataType::TYPE_FORMULA) {
            try {
                $index = $this->getWorksheet()->getParentOrThrow()->getActiveSheetIndex();
                $selected = $this->getWorksheet()->getSelectedCells();
                $result = Calculation::getInstance(
                    $this->getWorksheet()->getParent()
                )->calculateCellValue($this, $resetLog);
                $result = $this->convertDateTimeInt($result);
                $this->getWorksheet()->setSelectedCells($selected);
                $this->getWorksheet()->getParentOrThrow()->setActiveSheetIndex($index);
                                if (is_array($result)) {
                    while (is_array($result)) {
                        $result = array_shift($result);
                    }
                }
            } catch (SpreadsheetException $ex) {
                if (($ex->getMessage() === 'Unable to access External Workbook') && ($this->calculatedValue !== null)) {
                    return $this->calculatedValue;                 } elseif (preg_match('/[Uu]ndefined (name|offset: 2|array key 2)/', $ex->getMessage()) === 1) {
                    return ExcelError::NAME();
                }

                throw new CalculationException(
                    $this->getWorksheet()->getTitle() . '!' . $this->getCoordinate() . ' -> ' . $ex->getMessage(),
                    $ex->getCode(),
                    $ex
                );
            }

            if ($result === '#Not Yet Implemented') {
                return $this->calculatedValue;             }

            return $result;
        } elseif ($this->value instanceof RichText) {
            return $this->value->getPlainText();
        }

        return $this->convertDateTimeInt($this->value);
    }

        public function setCalculatedValue(mixed $originalValue, bool $tryNumeric = true): self
    {
        if ($originalValue !== null) {
            $this->calculatedValue = ($tryNumeric && is_numeric($originalValue)) ? (0 + $originalValue) : $originalValue;
        }

        return $this->updateInCollection();
    }

        public function getOldCalculatedValue(): mixed
    {
        return $this->calculatedValue;
    }

        public function getDataType(): string
    {
        return $this->dataType;
    }

        public function setDataType(string $dataType): self
    {
        $this->setValueExplicit($this->value, $dataType);

        return $this;
    }

        public function isFormula(): bool
    {
        return $this->dataType === DataType::TYPE_FORMULA && $this->getStyle()->getQuotePrefix() === false;
    }

        public function hasDataValidation(): bool
    {
        if (!isset($this->parent)) {
            throw new SpreadsheetException('Cannot check for data validation when cell is not bound to a worksheet');
        }

        return $this->getWorksheet()->dataValidationExists($this->getCoordinate());
    }

        public function getDataValidation(): DataValidation
    {
        if (!isset($this->parent)) {
            throw new SpreadsheetException('Cannot get data validation for cell that is not bound to a worksheet');
        }

        return $this->getWorksheet()->getDataValidation($this->getCoordinate());
    }

        public function setDataValidation(?DataValidation $dataValidation = null): self
    {
        if (!isset($this->parent)) {
            throw new SpreadsheetException('Cannot set data validation for cell that is not bound to a worksheet');
        }

        $this->getWorksheet()->setDataValidation($this->getCoordinate(), $dataValidation);

        return $this->updateInCollection();
    }

        public function hasValidValue(): bool
    {
        $validator = new DataValidator();

        return $validator->isValid($this);
    }

        public function hasHyperlink(): bool
    {
        if (!isset($this->parent)) {
            throw new SpreadsheetException('Cannot check for hyperlink when cell is not bound to a worksheet');
        }

        return $this->getWorksheet()->hyperlinkExists($this->getCoordinate());
    }

        public function getHyperlink(): Hyperlink
    {
        if (!isset($this->parent)) {
            throw new SpreadsheetException('Cannot get hyperlink for cell that is not bound to a worksheet');
        }

        return $this->getWorksheet()->getHyperlink($this->getCoordinate());
    }

        public function setHyperlink(?Hyperlink $hyperlink = null): self
    {
        if (!isset($this->parent)) {
            throw new SpreadsheetException('Cannot set hyperlink for cell that is not bound to a worksheet');
        }

        $this->getWorksheet()->setHyperlink($this->getCoordinate(), $hyperlink);

        return $this->updateInCollection();
    }

        public function getParent(): ?Cells
    {
        return $this->parent;
    }

        public function getWorksheet(): Worksheet
    {
        $parent = $this->parent;
        if ($parent !== null) {
            $worksheet = $parent->getParent();
        } else {
            $worksheet = null;
        }

        if ($worksheet === null) {
            throw new SpreadsheetException('Worksheet no longer exists');
        }

        return $worksheet;
    }

    public function getWorksheetOrNull(): ?Worksheet
    {
        $parent = $this->parent;
        if ($parent !== null) {
            $worksheet = $parent->getParent();
        } else {
            $worksheet = null;
        }

        return $worksheet;
    }

        public function isInMergeRange(): bool
    {
        return (bool) $this->getMergeRange();
    }

        public function isMergeRangeValueCell(): bool
    {
        if ($mergeRange = $this->getMergeRange()) {
            $mergeRange = Coordinate::splitRange($mergeRange);
            [$startCell] = $mergeRange[0];

            return $this->getCoordinate() === $startCell;
        }

        return false;
    }

        public function getMergeRange()
    {
        foreach ($this->getWorksheet()->getMergeCells() as $mergeRange) {
            if ($this->isInRange($mergeRange)) {
                return $mergeRange;
            }
        }

        return false;
    }

        public function getStyle(): Style
    {
        return $this->getWorksheet()->getStyle($this->getCoordinate());
    }

        public function getAppliedStyle(): Style
    {
        if ($this->getWorksheet()->conditionalStylesExists($this->getCoordinate()) === false) {
            return $this->getStyle();
        }
        $range = $this->getWorksheet()->getConditionalRange($this->getCoordinate());
        if ($range === null) {
            return $this->getStyle();
        }

        $matcher = new CellStyleAssessor($this, $range);

        return $matcher->matchConditions($this->getWorksheet()->getConditionalStyles($this->getCoordinate()));
    }

        public function rebindParent(Worksheet $parent): self
    {
        $this->parent = $parent->getCellCollection();

        return $this->updateInCollection();
    }

        public function isInRange(string $range): bool
    {
        [$rangeStart, $rangeEnd] = Coordinate::rangeBoundaries($range);

                $myColumn = Coordinate::columnIndexFromString($this->getColumn());
        $myRow = $this->getRow();

                return ($rangeStart[0] <= $myColumn) && ($rangeEnd[0] >= $myColumn)
            && ($rangeStart[1] <= $myRow) && ($rangeEnd[1] >= $myRow);
    }

        public static function compareCells(self $a, self $b): int
    {
        if ($a->getRow() < $b->getRow()) {
            return -1;
        } elseif ($a->getRow() > $b->getRow()) {
            return 1;
        } elseif (Coordinate::columnIndexFromString($a->getColumn()) < Coordinate::columnIndexFromString($b->getColumn())) {
            return -1;
        }

        return 1;
    }

        public static function getValueBinder(): IValueBinder
    {
        if (self::$valueBinder === null) {
            self::$valueBinder = new DefaultValueBinder();
        }

        return self::$valueBinder;
    }

        public static function setValueBinder(IValueBinder $binder): void
    {
        self::$valueBinder = $binder;
    }

        public function __clone()
    {
        $vars = get_object_vars($this);
        foreach ($vars as $propertyName => $propertyValue) {
            if ((is_object($propertyValue)) && ($propertyName !== 'parent')) {
                $this->$propertyName = clone $propertyValue;
            } else {
                $this->$propertyName = $propertyValue;
            }
        }
    }

        public function getXfIndex(): int
    {
        return $this->xfIndex;
    }

        public function setXfIndex(int $indexValue): self
    {
        $this->xfIndex = $indexValue;

        return $this->updateInCollection();
    }

        public function setFormulaAttributes(mixed $attributes): self
    {
        $this->formulaAttributes = $attributes;

        return $this;
    }

        public function getFormulaAttributes(): mixed
    {
        return $this->formulaAttributes;
    }

        public function __toString(): string
    {
        return (string) $this->getValue();
    }

    public function getIgnoredErrors(): IgnoredErrors
    {
        return $this->ignoredErrors;
    }

    public function isLocked(): bool
    {
        $protected = $this->parent?->getParent()?->getProtection()?->getSheet();
        if ($protected !== true) {
            return false;
        }
        $locked = $this->getStyle()->getProtection()->getLocked();

        return $locked !== Protection::PROTECTION_UNPROTECTED;
    }

    public function isHiddenOnFormulaBar(): bool
    {
        if ($this->getDataType() !== DataType::TYPE_FORMULA) {
            return false;
        }
        $protected = $this->parent?->getParent()?->getProtection()?->getSheet();
        if ($protected !== true) {
            return false;
        }
        $hidden = $this->getStyle()->getProtection()->getHidden();

        return $hidden !== Protection::PROTECTION_UNPROTECTED;
    }
}
