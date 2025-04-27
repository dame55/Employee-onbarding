<?php

namespace PhpOffice\PhpSpreadsheet\Worksheet;

use ArrayObject;
use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\Cell\AddressRange;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\CellAddress;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Cell\Hyperlink;
use PhpOffice\PhpSpreadsheet\Cell\IValueBinder;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Collection\Cells;
use PhpOffice\PhpSpreadsheet\Collection\CellsFactory;
use PhpOffice\PhpSpreadsheet\Comment;
use PhpOffice\PhpSpreadsheet\DefinedName;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\IComparable;
use PhpOffice\PhpSpreadsheet\ReferenceHelper;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Protection as StyleProtection;
use PhpOffice\PhpSpreadsheet\Style\Style;

class Worksheet implements IComparable
{
        public const BREAK_NONE = 0;
    public const BREAK_ROW = 1;
    public const BREAK_COLUMN = 2;
        public const BREAK_ROW_MAX_COLUMN = 16383;

        public const SHEETSTATE_VISIBLE = 'visible';
    public const SHEETSTATE_HIDDEN = 'hidden';
    public const SHEETSTATE_VERYHIDDEN = 'veryHidden';

    public const MERGE_CELL_CONTENT_EMPTY = 'empty';
    public const MERGE_CELL_CONTENT_HIDE = 'hide';
    public const MERGE_CELL_CONTENT_MERGE = 'merge';

    protected const SHEET_NAME_REQUIRES_NO_QUOTES = '/^[_\p{L}][_\p{L}\p{N}]*$/mui';

        const SHEET_TITLE_MAXIMUM_LENGTH = 31;

        private static array $invalidCharacters = ['*', ':', '/', '\\', '?', '[', ']'];

        private ?Spreadsheet $parent;

        private Cells $cellCollection;

    private bool $cellCollectionInitialized = true;

        private array $rowDimensions = [];

        private RowDimension $defaultRowDimension;

        private array $columnDimensions = [];

        private ColumnDimension $defaultColumnDimension;

        private ArrayObject $drawingCollection;

        private ArrayObject $chartCollection;

        private ArrayObject $tableCollection;

        private string $title = '';

        private string $sheetState;

        private PageSetup $pageSetup;

        private PageMargins $pageMargins;

        private HeaderFooter $headerFooter;

        private SheetView $sheetView;

        private Protection $protection;

        private array $styles = [];

        private array $conditionalStylesCollection = [];

        private array $rowBreaks = [];

        private array $columnBreaks = [];

        private array $mergeCells = [];

        private array $protectedCells = [];

        private AutoFilter $autoFilter;

        private ?string $freezePane = null;

        private ?string $topLeftCell = null;

    private string $paneTopLeftCell = '';

    private string $activePane = '';

    private int $xSplit = 0;

    private int $ySplit = 0;

    private string $paneState = '';

        private array $panes = [
        'bottomRight' => null,
        'bottomLeft' => null,
        'topRight' => null,
        'topLeft' => null,
    ];

        private bool $showGridlines = true;

        private bool $printGridlines = false;

        private bool $showRowColHeaders = true;

        private bool $showSummaryBelow = true;

        private bool $showSummaryRight = true;

        private array $comments = [];

        private string $activeCell = 'A1';

        private string $selectedCells = 'A1';

        private int $cachedHighestColumn = 1;

        private int $cachedHighestRow = 1;

        private bool $rightToLeft = false;

        private array $hyperlinkCollection = [];

        private array $dataValidationCollection = [];

        private ?Color $tabColor = null;

        private bool $dirty = true;

        private string $hash;

        private ?string $codeName = null;

        public function __construct(?Spreadsheet $parent = null, string $title = 'Worksheet')
    {
                $this->parent = $parent;
        $this->setTitle($title, false);
                $this->setCodeName($this->getTitle());
        $this->setSheetState(self::SHEETSTATE_VISIBLE);

        $this->cellCollection = CellsFactory::getInstance($this);
                $this->pageSetup = new PageSetup();
                $this->pageMargins = new PageMargins();
                $this->headerFooter = new HeaderFooter();
                $this->sheetView = new SheetView();
                $this->drawingCollection = new ArrayObject();
                $this->chartCollection = new ArrayObject();
                $this->protection = new Protection();
                $this->defaultRowDimension = new RowDimension(null);
                $this->defaultColumnDimension = new ColumnDimension(null);
                $this->autoFilter = new AutoFilter('', $this);
                $this->tableCollection = new ArrayObject();
    }

        public function disconnectCells(): void
    {
        if ($this->cellCollectionInitialized) {
            $this->cellCollection->unsetWorksheetCells();
            unset($this->cellCollection);
            $this->cellCollectionInitialized = false;
        }
                $this->parent = null;
    }

        public function __destruct()
    {
        Calculation::getInstance($this->parent)->clearCalculationCacheForWorksheet($this->title);

        $this->disconnectCells();
        unset($this->rowDimensions, $this->columnDimensions, $this->tableCollection, $this->drawingCollection, $this->chartCollection, $this->autoFilter);
    }

        public function getCellCollection(): Cells
    {
        return $this->cellCollection;
    }

        public static function getInvalidCharacters(): array
    {
        return self::$invalidCharacters;
    }

        private static function checkSheetCodeName(string $sheetCodeName): string
    {
        $charCount = Shared\StringHelper::countCharacters($sheetCodeName);
        if ($charCount == 0) {
            throw new Exception('Sheet code name cannot be empty.');
        }
                if (
            (str_replace(self::$invalidCharacters, '', $sheetCodeName) !== $sheetCodeName)
            || (Shared\StringHelper::substring($sheetCodeName, -1, 1) == '\'')
            || (Shared\StringHelper::substring($sheetCodeName, 0, 1) == '\'')
        ) {
            throw new Exception('Invalid character found in sheet code name');
        }

                if ($charCount > self::SHEET_TITLE_MAXIMUM_LENGTH) {
            throw new Exception('Maximum ' . self::SHEET_TITLE_MAXIMUM_LENGTH . ' characters allowed in sheet code name.');
        }

        return $sheetCodeName;
    }

        private static function checkSheetTitle(string $sheetTitle): string
    {
                if (str_replace(self::$invalidCharacters, '', $sheetTitle) !== $sheetTitle) {
            throw new Exception('Invalid character found in sheet title');
        }

                if (Shared\StringHelper::countCharacters($sheetTitle) > self::SHEET_TITLE_MAXIMUM_LENGTH) {
            throw new Exception('Maximum ' . self::SHEET_TITLE_MAXIMUM_LENGTH . ' characters allowed in sheet title.');
        }

        return $sheetTitle;
    }

        public function getCoordinates(bool $sorted = true): array
    {
        if ($this->cellCollectionInitialized === false) {
            return [];
        }

        if ($sorted) {
            return $this->cellCollection->getSortedCoordinates();
        }

        return $this->cellCollection->getCoordinates();
    }

        public function getRowDimensions(): array
    {
        return $this->rowDimensions;
    }

        public function getDefaultRowDimension(): RowDimension
    {
        return $this->defaultRowDimension;
    }

        public function getColumnDimensions(): array
    {
                $callable = [self::class, 'columnDimensionCompare'];
        uasort($this->columnDimensions, $callable);

        return $this->columnDimensions;
    }

    private static function columnDimensionCompare(ColumnDimension $a, ColumnDimension $b): int
    {
        return $a->getColumnNumeric() - $b->getColumnNumeric();
    }

        public function getDefaultColumnDimension(): ColumnDimension
    {
        return $this->defaultColumnDimension;
    }

        public function getDrawingCollection(): ArrayObject
    {
        return $this->drawingCollection;
    }

        public function getChartCollection(): ArrayObject
    {
        return $this->chartCollection;
    }

    public function addChart(Chart $chart): Chart
    {
        $chart->setWorksheet($this);
        $this->chartCollection[] = $chart;

        return $chart;
    }

        public function getChartCount(): int
    {
        return count($this->chartCollection);
    }

        public function getChartByIndex(?string $index)
    {
        $chartCount = count($this->chartCollection);
        if ($chartCount == 0) {
            return false;
        }
        if ($index === null) {
            $index = --$chartCount;
        }
        if (!isset($this->chartCollection[$index])) {
            return false;
        }

        return $this->chartCollection[$index];
    }

        public function getChartNames(): array
    {
        $chartNames = [];
        foreach ($this->chartCollection as $chart) {
            $chartNames[] = $chart->getName();
        }

        return $chartNames;
    }

        public function getChartByName(string $chartName)
    {
        foreach ($this->chartCollection as $index => $chart) {
            if ($chart->getName() == $chartName) {
                return $chart;
            }
        }

        return false;
    }

    public function getChartByNameOrThrow(string $chartName): Chart
    {
        $chart = $this->getChartByName($chartName);
        if ($chart !== false) {
            return $chart;
        }

        throw new Exception("Sheet does not have a chart named $chartName.");
    }

        public function refreshColumnDimensions(): static
    {
        $newColumnDimensions = [];
        foreach ($this->getColumnDimensions() as $objColumnDimension) {
            $newColumnDimensions[$objColumnDimension->getColumnIndex()] = $objColumnDimension;
        }

        $this->columnDimensions = $newColumnDimensions;

        return $this;
    }

        public function refreshRowDimensions(): static
    {
        $newRowDimensions = [];
        foreach ($this->getRowDimensions() as $objRowDimension) {
            $newRowDimensions[$objRowDimension->getRowIndex()] = $objRowDimension;
        }

        $this->rowDimensions = $newRowDimensions;

        return $this;
    }

        public function calculateWorksheetDimension(): string
    {
                return 'A1:' . $this->getHighestColumn() . $this->getHighestRow();
    }

        public function calculateWorksheetDataDimension(): string
    {
                return 'A1:' . $this->getHighestDataColumn() . $this->getHighestDataRow();
    }

        public function calculateColumnWidths(): static
    {
                $autoSizes = [];
        foreach ($this->getColumnDimensions() as $colDimension) {
            if ($colDimension->getAutoSize()) {
                $autoSizes[$colDimension->getColumnIndex()] = -1;
            }
        }

                if (!empty($autoSizes)) {
            $holdActivePane = $this->activePane;
                        $isMergeCell = [];
            foreach ($this->getMergeCells() as $cells) {
                foreach (Coordinate::extractAllCellReferencesInRange($cells) as $cellReference) {
                    $isMergeCell[$cellReference] = true;
                }
            }

            $autoFilterIndentRanges = (new AutoFit($this))->getAutoFilterIndentRanges();

                        foreach ($this->getCoordinates(false) as $coordinate) {
                $cell = $this->getCellOrNull($coordinate);

                if ($cell !== null && isset($autoSizes[$this->cellCollection->getCurrentColumn()])) {
                                        $isMerged = isset($isMergeCell[$this->cellCollection->getCurrentCoordinate()]);

                                        $isMergedButProceed = false;

                                        if ($isMerged && $cell->isMergeRangeValueCell()) {
                        $range = (string) $cell->getMergeRange();
                        $rangeBoundaries = Coordinate::rangeDimension($range);
                        if ($rangeBoundaries[0] === 1) {
                            $isMergedButProceed = true;
                        }
                    }

                                        if (!$isMerged || $isMergedButProceed) {
                                                                        $filterAdjustment = false;
                        if (!empty($autoFilterIndentRanges)) {
                            foreach ($autoFilterIndentRanges as $autoFilterFirstRowRange) {
                                if ($cell->isInRange($autoFilterFirstRowRange)) {
                                    $filterAdjustment = true;

                                    break;
                                }
                            }
                        }

                        $indentAdjustment = $cell->getStyle()->getAlignment()->getIndent();
                        $indentAdjustment += (int) ($cell->getStyle()->getAlignment()->getHorizontal() === Alignment::HORIZONTAL_CENTER);

                                                                        $cellValue = NumberFormat::toFormattedString(
                            $cell->getCalculatedValue(),
                            (string) $this->getParentOrThrow()->getCellXfByIndex($cell->getXfIndex())
                                ->getNumberFormat()->getFormatCode()
                        );

                        if ($cellValue !== null && $cellValue !== '') {
                            $autoSizes[$this->cellCollection->getCurrentColumn()] = max(
                                $autoSizes[$this->cellCollection->getCurrentColumn()],
                                round(
                                    Shared\Font::calculateColumnWidth(
                                        $this->getParentOrThrow()->getCellXfByIndex($cell->getXfIndex())->getFont(),
                                        $cellValue,
                                        (int) $this->getParentOrThrow()->getCellXfByIndex($cell->getXfIndex())
                                            ->getAlignment()->getTextRotation(),
                                        $this->getParentOrThrow()->getDefaultStyle()->getFont(),
                                        $filterAdjustment,
                                        $indentAdjustment
                                    ),
                                    3
                                )
                            );
                        }
                    }
                }
            }

                        foreach ($autoSizes as $columnIndex => $width) {
                if ($width == -1) {
                    $width = $this->getDefaultColumnDimension()->getWidth();
                }
                $this->getColumnDimension($columnIndex)->setWidth($width);
            }
            $this->activePane = $holdActivePane;
        }

        return $this;
    }

        public function getParent(): ?Spreadsheet
    {
        return $this->parent;
    }

        public function getParentOrThrow(): Spreadsheet
    {
        if ($this->parent !== null) {
            return $this->parent;
        }

        throw new Exception('Sheet does not have a parent.');
    }

        public function rebindParent(Spreadsheet $parent): static
    {
        if ($this->parent !== null) {
            $definedNames = $this->parent->getDefinedNames();
            foreach ($definedNames as $definedName) {
                $parent->addDefinedName($definedName);
            }

            $this->parent->removeSheetByIndex(
                $this->parent->getIndex($this)
            );
        }
        $this->parent = $parent;

        return $this;
    }

        public function getTitle(): string
    {
        return $this->title;
    }

        public function setTitle(string $title, bool $updateFormulaCellReferences = true, bool $validate = true): static
    {
                if ($this->getTitle() == $title) {
            return $this;
        }

                $oldTitle = $this->getTitle();

        if ($validate) {
                        self::checkSheetTitle($title);

            if ($this->parent) {
                                if ($this->parent->sheetNameExists($title)) {
                    
                    if (Shared\StringHelper::countCharacters($title) > 29) {
                        $title = Shared\StringHelper::substring($title, 0, 29);
                    }
                    $i = 1;
                    while ($this->parent->sheetNameExists($title . ' ' . $i)) {
                        ++$i;
                        if ($i == 10) {
                            if (Shared\StringHelper::countCharacters($title) > 28) {
                                $title = Shared\StringHelper::substring($title, 0, 28);
                            }
                        } elseif ($i == 100) {
                            if (Shared\StringHelper::countCharacters($title) > 27) {
                                $title = Shared\StringHelper::substring($title, 0, 27);
                            }
                        }
                    }

                    $title .= " $i";
                }
            }
        }

                $this->title = $title;
        $this->dirty = true;

        if ($this->parent && $this->parent->getCalculationEngine()) {
                        $newTitle = $this->getTitle();
            $this->parent->getCalculationEngine()
                ->renameCalculationCacheForWorksheet($oldTitle, $newTitle);
            if ($updateFormulaCellReferences) {
                ReferenceHelper::getInstance()->updateNamedFormulae($this->parent, $oldTitle, $newTitle);
            }
        }

        return $this;
    }

        public function getSheetState(): string
    {
        return $this->sheetState;
    }

        public function setSheetState(string $value): static
    {
        $this->sheetState = $value;

        return $this;
    }

        public function getPageSetup(): PageSetup
    {
        return $this->pageSetup;
    }

        public function setPageSetup(PageSetup $pageSetup): static
    {
        $this->pageSetup = $pageSetup;

        return $this;
    }

        public function getPageMargins(): PageMargins
    {
        return $this->pageMargins;
    }

        public function setPageMargins(PageMargins $pageMargins): static
    {
        $this->pageMargins = $pageMargins;

        return $this;
    }

        public function getHeaderFooter(): HeaderFooter
    {
        return $this->headerFooter;
    }

        public function setHeaderFooter(HeaderFooter $headerFooter): static
    {
        $this->headerFooter = $headerFooter;

        return $this;
    }

        public function getSheetView(): SheetView
    {
        return $this->sheetView;
    }

        public function setSheetView(SheetView $sheetView): static
    {
        $this->sheetView = $sheetView;

        return $this;
    }

        public function getProtection(): Protection
    {
        return $this->protection;
    }

        public function setProtection(Protection $protection): static
    {
        $this->protection = $protection;
        $this->dirty = true;

        return $this;
    }

        public function getHighestColumn($row = null): string
    {
        if ($row === null) {
            return Coordinate::stringFromColumnIndex($this->cachedHighestColumn);
        }

        return $this->getHighestDataColumn($row);
    }

        public function getHighestDataColumn($row = null): string
    {
        return $this->cellCollection->getHighestColumn($row);
    }

        public function getHighestRow(?string $column = null): int
    {
        if ($column === null) {
            return $this->cachedHighestRow;
        }

        return $this->getHighestDataRow($column);
    }

        public function getHighestDataRow(?string $column = null): int
    {
        return $this->cellCollection->getHighestRow($column);
    }

        public function getHighestRowAndColumn(): array
    {
        return $this->cellCollection->getHighestRowAndColumn();
    }

        public function setCellValue($coordinate, mixed $value, ?IValueBinder $binder = null): static
    {
        $cellAddress = Functions::trimSheetFromCellReference(Validations::validateCellAddress($coordinate));
        $this->getCell($cellAddress)->setValue($value, $binder);

        return $this;
    }

        public function setCellValueExplicit($coordinate, mixed $value, string $dataType): static
    {
        $cellAddress = Functions::trimSheetFromCellReference(Validations::validateCellAddress($coordinate));
        $this->getCell($cellAddress)->setValueExplicit($value, $dataType);

        return $this;
    }

        public function getCell($coordinate): Cell
    {
        $cellAddress = Functions::trimSheetFromCellReference(Validations::validateCellAddress($coordinate));

                if ($this->cellCollection->has($cellAddress)) {
                        $cell = $this->cellCollection->get($cellAddress);

            return $cell;
        }

                [$sheet, $finalCoordinate] = $this->getWorksheetAndCoordinate($cellAddress);
        $cell = $sheet->getCellCollection()->get($finalCoordinate);

        return $cell ?? $sheet->createNewCell($finalCoordinate);
    }

        private function getWorksheetAndCoordinate(string $coordinate): array
    {
        $sheet = null;
        $finalCoordinate = null;

                if (str_contains($coordinate, '!')) {
            $worksheetReference = self::extractSheetTitle($coordinate, true);

            $sheet = $this->getParentOrThrow()->getSheetByName($worksheetReference[0]);
            $finalCoordinate = strtoupper($worksheetReference[1]);

            if ($sheet === null) {
                throw new Exception('Sheet not found for name: ' . $worksheetReference[0]);
            }
        } elseif (
            !preg_match('/^' . Calculation::CALCULATION_REGEXP_CELLREF . '$/i', $coordinate)
            && preg_match('/^' . Calculation::CALCULATION_REGEXP_DEFINEDNAME . '$/iu', $coordinate)
        ) {
                        $namedRange = $this->validateNamedRange($coordinate, true);
            if ($namedRange !== null) {
                $sheet = $namedRange->getWorksheet();
                if ($sheet === null) {
                    throw new Exception('Sheet not found for named range: ' . $namedRange->getName());
                }

                                $cellCoordinate = ltrim(substr($namedRange->getValue(), strrpos($namedRange->getValue(), '!')), '!');
                $finalCoordinate = str_replace('$', '', $cellCoordinate);
            }
        }

        if ($sheet === null || $finalCoordinate === null) {
            $sheet = $this;
            $finalCoordinate = strtoupper($coordinate);
        }

        if (Coordinate::coordinateIsRange($finalCoordinate)) {
            throw new Exception('Cell coordinate string can not be a range of cells.');
        } elseif (str_contains($finalCoordinate, '$')) {
            throw new Exception('Cell coordinate must not be absolute.');
        }

        return [$sheet, $finalCoordinate];
    }

        private function getCellOrNull(string $coordinate): ?Cell
    {
                if ($this->cellCollection->has($coordinate)) {
            return $this->cellCollection->get($coordinate);
        }

        return null;
    }

        public function createNewCell(string $coordinate): Cell
    {
        [$column, $row, $columnString] = Coordinate::indexesFromString($coordinate);
        $cell = new Cell(null, DataType::TYPE_NULL, $this);
        $this->cellCollection->add($coordinate, $cell);

                if ($column > $this->cachedHighestColumn) {
            $this->cachedHighestColumn = $column;
        }
        if ($row > $this->cachedHighestRow) {
            $this->cachedHighestRow = $row;
        }

                        $rowDimension = $this->rowDimensions[$row] ?? null;
        $columnDimension = $this->columnDimensions[$columnString] ?? null;

        $xfSet = false;
        if ($rowDimension !== null) {
            $rowXf = (int) $rowDimension->getXfIndex();
            if ($rowXf > 0) {
                                $cell->setXfIndex($rowXf);
                $xfSet = true;
            }
        }
        if (!$xfSet && $columnDimension !== null) {
            $colXf = (int) $columnDimension->getXfIndex();
            if ($colXf > 0) {
                                $cell->setXfIndex($colXf);
            }
        }

        return $cell;
    }

        public function cellExists($coordinate): bool
    {
        $cellAddress = Validations::validateCellAddress($coordinate);
        [$sheet, $finalCoordinate] = $this->getWorksheetAndCoordinate($cellAddress);

        return $sheet->getCellCollection()->has($finalCoordinate);
    }

        public function getRowDimension(int $row): RowDimension
    {
                if (!isset($this->rowDimensions[$row])) {
            $this->rowDimensions[$row] = new RowDimension($row);

            $this->cachedHighestRow = max($this->cachedHighestRow, $row);
        }

        return $this->rowDimensions[$row];
    }

    public function rowDimensionExists(int $row): bool
    {
        return isset($this->rowDimensions[$row]);
    }

    public function columnDimensionExists(string $column): bool
    {
        return isset($this->columnDimensions[$column]);
    }

        public function getColumnDimension(string $column): ColumnDimension
    {
                $column = strtoupper($column);

                if (!isset($this->columnDimensions[$column])) {
            $this->columnDimensions[$column] = new ColumnDimension($column);

            $columnIndex = Coordinate::columnIndexFromString($column);
            if ($this->cachedHighestColumn < $columnIndex) {
                $this->cachedHighestColumn = $columnIndex;
            }
        }

        return $this->columnDimensions[$column];
    }

        public function getColumnDimensionByColumn(int $columnIndex): ColumnDimension
    {
        return $this->getColumnDimension(Coordinate::stringFromColumnIndex($columnIndex));
    }

        public function getStyles(): array
    {
        return $this->styles;
    }

        public function getStyle($cellCoordinate): Style
    {
        $cellCoordinate = Validations::validateCellOrCellRange($cellCoordinate);

                $this->getParentOrThrow()->setActiveSheetIndex($this->getParentOrThrow()->getIndex($this));

                $this->setSelectedCells($cellCoordinate);

        return $this->getParentOrThrow()->getCellXfSupervisor();
    }

        public function getConditionalStyles(string $coordinate): array
    {
        $coordinate = strtoupper($coordinate);
        if (str_contains($coordinate, ':')) {
            return $this->conditionalStylesCollection[$coordinate] ?? [];
        }

        $cell = $this->getCell($coordinate);
        foreach (array_keys($this->conditionalStylesCollection) as $conditionalRange) {
            if ($cell->isInRange($conditionalRange)) {
                return $this->conditionalStylesCollection[$conditionalRange];
            }
        }

        return [];
    }

    public function getConditionalRange(string $coordinate): ?string
    {
        $coordinate = strtoupper($coordinate);
        $cell = $this->getCell($coordinate);
        foreach (array_keys($this->conditionalStylesCollection) as $conditionalRange) {
            if ($cell->isInRange($conditionalRange)) {
                return $conditionalRange;
            }
        }

        return null;
    }

        public function conditionalStylesExists(string $coordinate): bool
    {
        $coordinate = strtoupper($coordinate);
        if (str_contains($coordinate, ':')) {
            return isset($this->conditionalStylesCollection[$coordinate]);
        }

        $cell = $this->getCell($coordinate);
        foreach (array_keys($this->conditionalStylesCollection) as $conditionalRange) {
            if ($cell->isInRange($conditionalRange)) {
                return true;
            }
        }

        return false;
    }

        public function removeConditionalStyles(string $coordinate): static
    {
        unset($this->conditionalStylesCollection[strtoupper($coordinate)]);

        return $this;
    }

        public function getConditionalStylesCollection(): array
    {
        return $this->conditionalStylesCollection;
    }

        public function setConditionalStyles(string $coordinate, array $styles): static
    {
        $this->conditionalStylesCollection[strtoupper($coordinate)] = $styles;

        return $this;
    }

        public function duplicateStyle(Style $style, string $range): static
    {
                $workbook = $this->getParentOrThrow();
        if ($existingStyle = $workbook->getCellXfByHashCode($style->getHashCode())) {
                        $xfIndex = $existingStyle->getIndex();
        } else {
                        $workbook->addCellXf($style);
            $xfIndex = $style->getIndex();
        }

                [$rangeStart, $rangeEnd] = Coordinate::rangeBoundaries($range . ':' . $range);

                if ($rangeStart[0] > $rangeEnd[0] && $rangeStart[1] > $rangeEnd[1]) {
            $tmp = $rangeStart;
            $rangeStart = $rangeEnd;
            $rangeEnd = $tmp;
        }

                for ($col = $rangeStart[0]; $col <= $rangeEnd[0]; ++$col) {
            for ($row = $rangeStart[1]; $row <= $rangeEnd[1]; ++$row) {
                $this->getCell(Coordinate::stringFromColumnIndex($col) . $row)->setXfIndex($xfIndex);
            }
        }

        return $this;
    }

        public function duplicateConditionalStyle(array $styles, string $range = ''): static
    {
        foreach ($styles as $cellStyle) {
            if (!($cellStyle instanceof Conditional)) {
                throw new Exception('Style is not a conditional style');
            }
        }

                [$rangeStart, $rangeEnd] = Coordinate::rangeBoundaries($range . ':' . $range);

                if ($rangeStart[0] > $rangeEnd[0] && $rangeStart[1] > $rangeEnd[1]) {
            $tmp = $rangeStart;
            $rangeStart = $rangeEnd;
            $rangeEnd = $tmp;
        }

                for ($col = $rangeStart[0]; $col <= $rangeEnd[0]; ++$col) {
            for ($row = $rangeStart[1]; $row <= $rangeEnd[1]; ++$row) {
                $this->setConditionalStyles(Coordinate::stringFromColumnIndex($col) . $row, $styles);
            }
        }

        return $this;
    }

        public function setBreak($coordinate, int $break, int $max = -1): static
    {
        $cellAddress = Functions::trimSheetFromCellReference(Validations::validateCellAddress($coordinate));

        if ($break === self::BREAK_NONE) {
            unset($this->rowBreaks[$cellAddress], $this->columnBreaks[$cellAddress]);
        } elseif ($break === self::BREAK_ROW) {
            $this->rowBreaks[$cellAddress] = new PageBreak($break, $cellAddress, $max);
        } elseif ($break === self::BREAK_COLUMN) {
            $this->columnBreaks[$cellAddress] = new PageBreak($break, $cellAddress, $max);
        }

        return $this;
    }

        public function getBreaks(): array
    {
        $breaks = [];
                $compareFunction = [self::class, 'compareRowBreaks'];
        uksort($this->rowBreaks, $compareFunction);
        foreach ($this->rowBreaks as $break) {
            $breaks[$break->getCoordinate()] = self::BREAK_ROW;
        }
                $compareFunction = [self::class, 'compareColumnBreaks'];
        uksort($this->columnBreaks, $compareFunction);
        foreach ($this->columnBreaks as $break) {
            $breaks[$break->getCoordinate()] = self::BREAK_COLUMN;
        }

        return $breaks;
    }

        public function getRowBreaks(): array
    {
                $compareFunction = [self::class, 'compareRowBreaks'];
        uksort($this->rowBreaks, $compareFunction);

        return $this->rowBreaks;
    }

    protected static function compareRowBreaks(string $coordinate1, string $coordinate2): int
    {
        $row1 = Coordinate::indexesFromString($coordinate1)[1];
        $row2 = Coordinate::indexesFromString($coordinate2)[1];

        return $row1 - $row2;
    }

    protected static function compareColumnBreaks(string $coordinate1, string $coordinate2): int
    {
        $column1 = Coordinate::indexesFromString($coordinate1)[0];
        $column2 = Coordinate::indexesFromString($coordinate2)[0];

        return $column1 - $column2;
    }

        public function getColumnBreaks(): array
    {
                $compareFunction = [self::class, 'compareColumnBreaks'];
        uksort($this->columnBreaks, $compareFunction);

        return $this->columnBreaks;
    }

        public function mergeCells($range, string $behaviour = self::MERGE_CELL_CONTENT_EMPTY): static
    {
        $range = Functions::trimSheetFromCellReference(Validations::validateCellRange($range));

        if (!str_contains($range, ':')) {
            $range .= ":{$range}";
        }

        if (preg_match('/^([A-Z]+)(\\d+):([A-Z]+)(\\d+)$/', $range, $matches) !== 1) {
            throw new Exception('Merge must be on a valid range of cells.');
        }

        $this->mergeCells[$range] = $range;
        $firstRow = (int) $matches[2];
        $lastRow = (int) $matches[4];
        $firstColumn = $matches[1];
        $lastColumn = $matches[3];
        $firstColumnIndex = Coordinate::columnIndexFromString($firstColumn);
        $lastColumnIndex = Coordinate::columnIndexFromString($lastColumn);
        $numberRows = $lastRow - $firstRow;
        $numberColumns = $lastColumnIndex - $firstColumnIndex;

        if ($numberRows === 1 && $numberColumns === 1) {
            return $this;
        }

                $upperLeft = "{$firstColumn}{$firstRow}";
        if (!$this->cellExists($upperLeft)) {
            $this->getCell($upperLeft)->setValueExplicit(null, DataType::TYPE_NULL);
        }

        if ($behaviour !== self::MERGE_CELL_CONTENT_HIDE) {
                        if ($numberRows > $numberColumns) {
                $this->clearMergeCellsByColumn($firstColumn, $lastColumn, $firstRow, $lastRow, $upperLeft, $behaviour);
            } else {
                $this->clearMergeCellsByRow($firstColumn, $lastColumnIndex, $firstRow, $lastRow, $upperLeft, $behaviour);
            }
        }

        return $this;
    }

    private function clearMergeCellsByColumn(string $firstColumn, string $lastColumn, int $firstRow, int $lastRow, string $upperLeft, string $behaviour): void
    {
        $leftCellValue = ($behaviour === self::MERGE_CELL_CONTENT_MERGE)
            ? [$this->getCell($upperLeft)->getFormattedValue()]
            : [];

        foreach ($this->getColumnIterator($firstColumn, $lastColumn) as $column) {
            $iterator = $column->getCellIterator($firstRow);
            $iterator->setIterateOnlyExistingCells(true);
            foreach ($iterator as $cell) {
                if ($cell !== null) {
                    $row = $cell->getRow();
                    if ($row > $lastRow) {
                        break;
                    }
                    $leftCellValue = $this->mergeCellBehaviour($cell, $upperLeft, $behaviour, $leftCellValue);
                }
            }
        }

        if ($behaviour === self::MERGE_CELL_CONTENT_MERGE) {
            $this->getCell($upperLeft)->setValueExplicit(implode(' ', $leftCellValue), DataType::TYPE_STRING);
        }
    }

    private function clearMergeCellsByRow(string $firstColumn, int $lastColumnIndex, int $firstRow, int $lastRow, string $upperLeft, string $behaviour): void
    {
        $leftCellValue = ($behaviour === self::MERGE_CELL_CONTENT_MERGE)
            ? [$this->getCell($upperLeft)->getFormattedValue()]
            : [];

        foreach ($this->getRowIterator($firstRow, $lastRow) as $row) {
            $iterator = $row->getCellIterator($firstColumn);
            $iterator->setIterateOnlyExistingCells(true);
            foreach ($iterator as $cell) {
                if ($cell !== null) {
                    $column = $cell->getColumn();
                    $columnIndex = Coordinate::columnIndexFromString($column);
                    if ($columnIndex > $lastColumnIndex) {
                        break;
                    }
                    $leftCellValue = $this->mergeCellBehaviour($cell, $upperLeft, $behaviour, $leftCellValue);
                }
            }
        }

        if ($behaviour === self::MERGE_CELL_CONTENT_MERGE) {
            $this->getCell($upperLeft)->setValueExplicit(implode(' ', $leftCellValue), DataType::TYPE_STRING);
        }
    }

    public function mergeCellBehaviour(Cell $cell, string $upperLeft, string $behaviour, array $leftCellValue): array
    {
        if ($cell->getCoordinate() !== $upperLeft) {
            Calculation::getInstance($cell->getWorksheet()->getParentOrThrow())->flushInstance();
            if ($behaviour === self::MERGE_CELL_CONTENT_MERGE) {
                $cellValue = $cell->getFormattedValue();
                if ($cellValue !== '') {
                    $leftCellValue[] = $cellValue;
                }
            }
            $cell->setValueExplicit(null, DataType::TYPE_NULL);
        }

        return $leftCellValue;
    }

        public function unmergeCells($range): static
    {
        $range = Functions::trimSheetFromCellReference(Validations::validateCellRange($range));

        if (str_contains($range, ':')) {
            if (isset($this->mergeCells[$range])) {
                unset($this->mergeCells[$range]);
            } else {
                throw new Exception('Cell range ' . $range . ' not known as merged.');
            }
        } else {
            throw new Exception('Merge can only be removed from a range of cells.');
        }

        return $this;
    }

        public function getMergeCells(): array
    {
        return $this->mergeCells;
    }

        public function setMergeCells(array $mergeCells): static
    {
        $this->mergeCells = $mergeCells;

        return $this;
    }

        public function protectCells($range, string $password, bool $alreadyHashed = false): static
    {
        $range = Functions::trimSheetFromCellReference(Validations::validateCellOrCellRange($range));

        if (!$alreadyHashed) {
            $password = Shared\PasswordHasher::hashPassword($password);
        }
        $this->protectedCells[$range] = $password;

        return $this;
    }

        public function unprotectCells($range): static
    {
        $range = Functions::trimSheetFromCellReference(Validations::validateCellOrCellRange($range));

        if (isset($this->protectedCells[$range])) {
            unset($this->protectedCells[$range]);
        } else {
            throw new Exception('Cell range ' . $range . ' not known as protected.');
        }

        return $this;
    }

        public function getProtectedCells(): array
    {
        return $this->protectedCells;
    }

        public function getAutoFilter(): AutoFilter
    {
        return $this->autoFilter;
    }

        public function setAutoFilter($autoFilterOrRange): static
    {
        if (is_object($autoFilterOrRange) && ($autoFilterOrRange instanceof AutoFilter)) {
            $this->autoFilter = $autoFilterOrRange;
        } else {
            $cellRange = Functions::trimSheetFromCellReference(Validations::validateCellRange($autoFilterOrRange));

            $this->autoFilter->setRange($cellRange);
        }

        return $this;
    }

        public function removeAutoFilter(): self
    {
        $this->autoFilter->setRange('');

        return $this;
    }

        public function getTableCollection(): ArrayObject
    {
        return $this->tableCollection;
    }

        public function addTable(Table $table): self
    {
        $table->setWorksheet($this);
        $this->tableCollection[] = $table;

        return $this;
    }

        public function getTableNames(): array
    {
        $tableNames = [];

        foreach ($this->tableCollection as $table) {
                        $tableNames[] = $table->getName();
        }

        return $tableNames;
    }

        public function getTableByName(string $name): ?Table
    {
        $tableIndex = $this->getTableIndexByName($name);

        return ($tableIndex === null) ? null : $this->tableCollection[$tableIndex];
    }

        protected function getTableIndexByName(string $name): ?int
    {
        $name = Shared\StringHelper::strToUpper($name);
        foreach ($this->tableCollection as $index => $table) {
                        if (Shared\StringHelper::strToUpper($table->getName()) === $name) {
                return $index;
            }
        }

        return null;
    }

        public function removeTableByName(string $name): self
    {
        $tableIndex = $this->getTableIndexByName($name);

        if ($tableIndex !== null) {
            unset($this->tableCollection[$tableIndex]);
        }

        return $this;
    }

        public function removeTableCollection(): self
    {
        $this->tableCollection = new ArrayObject();

        return $this;
    }

        public function getFreezePane(): ?string
    {
        return $this->freezePane;
    }

        public function freezePane($coordinate, $topLeftCell = null, bool $frozenSplit = false): static
    {
        $this->panes = [
            'bottomRight' => null,
            'bottomLeft' => null,
            'topRight' => null,
            'topLeft' => null,
        ];
        $cellAddress = ($coordinate !== null)
            ? Functions::trimSheetFromCellReference(Validations::validateCellAddress($coordinate))
            : null;
        if ($cellAddress !== null && Coordinate::coordinateIsRange($cellAddress)) {
            throw new Exception('Freeze pane can not be set on a range of cells.');
        }
        $topLeftCell = ($topLeftCell !== null)
            ? Functions::trimSheetFromCellReference(Validations::validateCellAddress($topLeftCell))
            : null;

        if ($cellAddress !== null && $topLeftCell === null) {
            $coordinate = Coordinate::coordinateFromString($cellAddress);
            $topLeftCell = $coordinate[0] . $coordinate[1];
        }

        $topLeftCell = "$topLeftCell";
        $this->paneTopLeftCell = $topLeftCell;

        $this->freezePane = $cellAddress;
        $this->topLeftCell = $topLeftCell;
        if ($cellAddress === null) {
            $this->paneState = '';
            $this->xSplit = $this->ySplit = 0;
            $this->activePane = '';
        } else {
            $coordinates = Coordinate::indexesFromString($cellAddress);
            $this->xSplit = $coordinates[0] - 1;
            $this->ySplit = $coordinates[1] - 1;
            if ($this->xSplit > 0 || $this->ySplit > 0) {
                $this->paneState = $frozenSplit ? self::PANE_FROZENSPLIT : self::PANE_FROZEN;
                $this->setSelectedCellsActivePane();
            } else {
                $this->paneState = '';
                $this->freezePane = null;
                $this->activePane = '';
            }
        }

        return $this;
    }

    public function setTopLeftCell(string $topLeftCell): self
    {
        $this->topLeftCell = $topLeftCell;

        return $this;
    }

        public function unfreezePane(): static
    {
        return $this->freezePane(null);
    }

        public function getTopLeftCell(): ?string
    {
        return $this->topLeftCell;
    }

    public function getPaneTopLeftCell(): string
    {
        return $this->paneTopLeftCell;
    }

    public function setPaneTopLeftCell(string $paneTopLeftCell): self
    {
        $this->paneTopLeftCell = $paneTopLeftCell;

        return $this;
    }

    public function usesPanes(): bool
    {
        return $this->xSplit > 0 || $this->ySplit > 0;
    }

    public function getPane(string $position): ?Pane
    {
        return $this->panes[$position] ?? null;
    }

    public function setPane(string $position, ?Pane $pane): self
    {
        if (array_key_exists($position, $this->panes)) {
            $this->panes[$position] = $pane;
        }

        return $this;
    }

        public function getPanes(): array
    {
        return $this->panes;
    }

    public function getActivePane(): string
    {
        return $this->activePane;
    }

    public function setActivePane(string $activePane): self
    {
        $this->activePane = array_key_exists($activePane, $this->panes) ? $activePane : '';

        return $this;
    }

    public function getXSplit(): int
    {
        return $this->xSplit;
    }

    public function setXSplit(int $xSplit): self
    {
        $this->xSplit = $xSplit;
        if (in_array($this->paneState, self::VALIDFROZENSTATE, true)) {
            $this->freezePane([$this->xSplit + 1, $this->ySplit + 1], $this->topLeftCell, $this->paneState === self::PANE_FROZENSPLIT);
        }

        return $this;
    }

    public function getYSplit(): int
    {
        return $this->ySplit;
    }

    public function setYSplit(int $ySplit): self
    {
        $this->ySplit = $ySplit;
        if (in_array($this->paneState, self::VALIDFROZENSTATE, true)) {
            $this->freezePane([$this->xSplit + 1, $this->ySplit + 1], $this->topLeftCell, $this->paneState === self::PANE_FROZENSPLIT);
        }

        return $this;
    }

    public function getPaneState(): string
    {
        return $this->paneState;
    }

    public const PANE_FROZEN = 'frozen';
    public const PANE_FROZENSPLIT = 'frozenSplit';
    public const PANE_SPLIT = 'split';
    private const VALIDPANESTATE = [self::PANE_FROZEN, self::PANE_SPLIT, self::PANE_FROZENSPLIT];
    private const VALIDFROZENSTATE = [self::PANE_FROZEN, self::PANE_FROZENSPLIT];

    public function setPaneState(string $paneState): self
    {
        $this->paneState = in_array($paneState, self::VALIDPANESTATE, true) ? $paneState : '';
        if (in_array($this->paneState, self::VALIDFROZENSTATE, true)) {
            $this->freezePane([$this->xSplit + 1, $this->ySplit + 1], $this->topLeftCell, $this->paneState === self::PANE_FROZENSPLIT);
        } else {
            $this->freezePane = null;
        }

        return $this;
    }

        public function insertNewRowBefore(int $before, int $numberOfRows = 1): static
    {
        if ($before >= 1) {
            $objReferenceHelper = ReferenceHelper::getInstance();
            $objReferenceHelper->insertNewBefore('A' . $before, 0, $numberOfRows, $this);
        } else {
            throw new Exception('Rows can only be inserted before at least row 1.');
        }

        return $this;
    }

        public function insertNewColumnBefore(string $before, int $numberOfColumns = 1): static
    {
        if (!is_numeric($before)) {
            $objReferenceHelper = ReferenceHelper::getInstance();
            $objReferenceHelper->insertNewBefore($before . '1', $numberOfColumns, 0, $this);
        } else {
            throw new Exception('Column references should not be numeric.');
        }

        return $this;
    }

        public function insertNewColumnBeforeByIndex(int $beforeColumnIndex, int $numberOfColumns = 1): static
    {
        if ($beforeColumnIndex >= 1) {
            return $this->insertNewColumnBefore(Coordinate::stringFromColumnIndex($beforeColumnIndex), $numberOfColumns);
        }

        throw new Exception('Columns can only be inserted before at least column A (1).');
    }

        public function removeRow(int $row, int $numberOfRows = 1): static
    {
        if ($row < 1) {
            throw new Exception('Rows to be deleted should at least start from row 1.');
        }

        $holdRowDimensions = $this->removeRowDimensions($row, $numberOfRows);
        $highestRow = $this->getHighestDataRow();
        $removedRowsCounter = 0;

        for ($r = 0; $r < $numberOfRows; ++$r) {
            if ($row + $r <= $highestRow) {
                $this->cellCollection->removeRow($row + $r);
                ++$removedRowsCounter;
            }
        }

        $objReferenceHelper = ReferenceHelper::getInstance();
        $objReferenceHelper->insertNewBefore('A' . ($row + $numberOfRows), 0, -$numberOfRows, $this);
        for ($r = 0; $r < $removedRowsCounter; ++$r) {
            $this->cellCollection->removeRow($highestRow);
            --$highestRow;
        }

        $this->rowDimensions = $holdRowDimensions;

        return $this;
    }

    private function removeRowDimensions(int $row, int $numberOfRows): array
    {
        $highRow = $row + $numberOfRows - 1;
        $holdRowDimensions = [];
        foreach ($this->rowDimensions as $rowDimension) {
            $num = $rowDimension->getRowIndex();
            if ($num < $row) {
                $holdRowDimensions[$num] = $rowDimension;
            } elseif ($num > $highRow) {
                $num -= $numberOfRows;
                $cloneDimension = clone $rowDimension;
                $cloneDimension->setRowIndex($num);
                $holdRowDimensions[$num] = $cloneDimension;
            }
        }

        return $holdRowDimensions;
    }

        public function removeColumn(string $column, int $numberOfColumns = 1): static
    {
        if (is_numeric($column)) {
            throw new Exception('Column references should not be numeric.');
        }

        $highestColumn = $this->getHighestDataColumn();
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);
        $pColumnIndex = Coordinate::columnIndexFromString($column);

        $holdColumnDimensions = $this->removeColumnDimensions($pColumnIndex, $numberOfColumns);

        $column = Coordinate::stringFromColumnIndex($pColumnIndex + $numberOfColumns);
        $objReferenceHelper = ReferenceHelper::getInstance();
        $objReferenceHelper->insertNewBefore($column . '1', -$numberOfColumns, 0, $this);

        $this->columnDimensions = $holdColumnDimensions;

        if ($pColumnIndex > $highestColumnIndex) {
            return $this;
        }

        $maxPossibleColumnsToBeRemoved = $highestColumnIndex - $pColumnIndex + 1;

        for ($c = 0, $n = min($maxPossibleColumnsToBeRemoved, $numberOfColumns); $c < $n; ++$c) {
            $this->cellCollection->removeColumn($highestColumn);
            $highestColumn = Coordinate::stringFromColumnIndex(Coordinate::columnIndexFromString($highestColumn) - 1);
        }

        $this->garbageCollect();

        return $this;
    }

    private function removeColumnDimensions(int $pColumnIndex, int $numberOfColumns): array
    {
        $highCol = $pColumnIndex + $numberOfColumns - 1;
        $holdColumnDimensions = [];
        foreach ($this->columnDimensions as $columnDimension) {
            $num = $columnDimension->getColumnNumeric();
            if ($num < $pColumnIndex) {
                $str = $columnDimension->getColumnIndex();
                $holdColumnDimensions[$str] = $columnDimension;
            } elseif ($num > $highCol) {
                $cloneDimension = clone $columnDimension;
                $cloneDimension->setColumnNumeric($num - $numberOfColumns);
                $str = $cloneDimension->getColumnIndex();
                $holdColumnDimensions[$str] = $cloneDimension;
            }
        }

        return $holdColumnDimensions;
    }

        public function removeColumnByIndex(int $columnIndex, int $numColumns = 1): static
    {
        if ($columnIndex >= 1) {
            return $this->removeColumn(Coordinate::stringFromColumnIndex($columnIndex), $numColumns);
        }

        throw new Exception('Columns to be deleted should at least start from column A (1)');
    }

        public function getShowGridlines(): bool
    {
        return $this->showGridlines;
    }

        public function setShowGridlines(bool $showGridLines): self
    {
        $this->showGridlines = $showGridLines;

        return $this;
    }

        public function getPrintGridlines(): bool
    {
        return $this->printGridlines;
    }

        public function setPrintGridlines(bool $printGridLines): self
    {
        $this->printGridlines = $printGridLines;

        return $this;
    }

        public function getShowRowColHeaders(): bool
    {
        return $this->showRowColHeaders;
    }

        public function setShowRowColHeaders(bool $showRowColHeaders): self
    {
        $this->showRowColHeaders = $showRowColHeaders;

        return $this;
    }

        public function getShowSummaryBelow(): bool
    {
        return $this->showSummaryBelow;
    }

        public function setShowSummaryBelow(bool $showSummaryBelow): self
    {
        $this->showSummaryBelow = $showSummaryBelow;

        return $this;
    }

        public function getShowSummaryRight(): bool
    {
        return $this->showSummaryRight;
    }

        public function setShowSummaryRight(bool $showSummaryRight): self
    {
        $this->showSummaryRight = $showSummaryRight;

        return $this;
    }

        public function getComments(): array
    {
        return $this->comments;
    }

        public function setComments(array $comments): self
    {
        $this->comments = $comments;

        return $this;
    }

        public function removeComment($cellCoordinate): self
    {
        $cellAddress = Functions::trimSheetFromCellReference(Validations::validateCellAddress($cellCoordinate));

        if (Coordinate::coordinateIsRange($cellAddress)) {
            throw new Exception('Cell coordinate string can not be a range of cells.');
        } elseif (str_contains($cellAddress, '$')) {
            throw new Exception('Cell coordinate string must not be absolute.');
        } elseif ($cellAddress == '') {
            throw new Exception('Cell coordinate can not be zero-length string.');
        }
                if (isset($this->comments[$cellAddress])) {
            unset($this->comments[$cellAddress]);
        }

        return $this;
    }

        public function getComment($cellCoordinate): Comment
    {
        $cellAddress = Functions::trimSheetFromCellReference(Validations::validateCellAddress($cellCoordinate));

        if (Coordinate::coordinateIsRange($cellAddress)) {
            throw new Exception('Cell coordinate string can not be a range of cells.');
        } elseif (str_contains($cellAddress, '$')) {
            throw new Exception('Cell coordinate string must not be absolute.');
        } elseif ($cellAddress == '') {
            throw new Exception('Cell coordinate can not be zero-length string.');
        }

                if (isset($this->comments[$cellAddress])) {
            return $this->comments[$cellAddress];
        }

                $newComment = new Comment();
        $this->comments[$cellAddress] = $newComment;

        return $newComment;
    }

        public function getActiveCell(): string
    {
        return $this->activeCell;
    }

        public function getSelectedCells(): string
    {
        return $this->selectedCells;
    }

        public function setSelectedCell(string $coordinate): static
    {
        return $this->setSelectedCells($coordinate);
    }

        public function setSelectedCells($coordinate): static
    {
        if (is_string($coordinate)) {
            $coordinate = Validations::definedNameToCoordinate($coordinate, $this);
        }
        $coordinate = Validations::validateCellOrCellRange($coordinate);

        if (Coordinate::coordinateIsRange($coordinate)) {
            [$first] = Coordinate::splitRange($coordinate);
            $this->activeCell = $first[0];
        } else {
            $this->activeCell = $coordinate;
        }
        $this->selectedCells = $coordinate;
        $this->setSelectedCellsActivePane();

        return $this;
    }

    private function setSelectedCellsActivePane(): void
    {
        if (!empty($this->freezePane)) {
            $coordinateC = Coordinate::indexesFromString($this->freezePane);
            $coordinateT = Coordinate::indexesFromString($this->activeCell);
            if ($coordinateC[0] === 1) {
                $activePane = ($coordinateT[1] <= $coordinateC[1]) ? 'topLeft' : 'bottomLeft';
            } elseif ($coordinateC[1] === 1) {
                $activePane = ($coordinateT[0] <= $coordinateC[0]) ? 'topLeft' : 'topRight';
            } elseif ($coordinateT[1] <= $coordinateC[1]) {
                $activePane = ($coordinateT[0] <= $coordinateC[0]) ? 'topLeft' : 'topRight';
            } else {
                $activePane = ($coordinateT[0] <= $coordinateC[0]) ? 'bottomLeft' : 'bottomRight';
            }
            $this->setActivePane($activePane);
            $this->panes[$activePane] = new Pane($activePane, $this->selectedCells, $this->activeCell);
        }
    }

        public function getRightToLeft(): bool
    {
        return $this->rightToLeft;
    }

        public function setRightToLeft(bool $value): static
    {
        $this->rightToLeft = $value;

        return $this;
    }

        public function fromArray(array $source, mixed $nullValue = null, string $startCell = 'A1', bool $strictNullComparison = false): static
    {
                if (!is_array(end($source))) {
            $source = [$source];
        }

                [$startColumn, $startRow] = Coordinate::coordinateFromString($startCell);

                foreach ($source as $rowData) {
            $currentColumn = $startColumn;
            foreach ($rowData as $cellValue) {
                if ($strictNullComparison) {
                    if ($cellValue !== $nullValue) {
                                                $this->getCell($currentColumn . $startRow)->setValue($cellValue);
                    }
                } else {
                    if ($cellValue != $nullValue) {
                                                $this->getCell($currentColumn . $startRow)->setValue($cellValue);
                    }
                }
                ++$currentColumn;
            }
            ++$startRow;
        }

        return $this;
    }

        protected function cellToArray(Cell $cell, bool $calculateFormulas, bool $formatData, mixed $nullValue): mixed
    {
        $returnValue = $nullValue;

        if ($cell->getValue() !== null) {
            if ($cell->getValue() instanceof RichText) {
                $returnValue = $cell->getValue()->getPlainText();
            } else {
                $returnValue = ($calculateFormulas) ? $cell->getCalculatedValue() : $cell->getValue();
            }

            if ($formatData) {
                $style = $this->getParentOrThrow()->getCellXfByIndex($cell->getXfIndex());
                $returnValue = NumberFormat::toFormattedString(
                    $returnValue,
                    $style->getNumberFormat()->getFormatCode() ?? NumberFormat::FORMAT_GENERAL
                );
            }
        }

        return $returnValue;
    }

        private array $hiddenColumns;

        public function rangeToArray(
        string $range,
        mixed $nullValue = null,
        bool $calculateFormulas = true,
        bool $formatData = true,
        bool $returnCellRef = false,
        bool $ignoreHidden = false
    ): array {
        $range = Validations::validateCellOrCellRange($range);

        $returnValue = [];
                [$rangeStart, $rangeEnd] = Coordinate::rangeBoundaries($range);
        $minCol = Coordinate::stringFromColumnIndex($rangeStart[0]);
        $minRow = $rangeStart[1];
        $maxCol = Coordinate::stringFromColumnIndex($rangeEnd[0]);
        $maxRow = $rangeEnd[1];
        $minColInt = $rangeStart[0];
        $maxColInt = $rangeEnd[0];

        ++$maxCol;
        $nullRow = $this->buildNullRow($nullValue, $minCol, $maxCol, $returnCellRef, $ignoreHidden);
        $hideColumns = !empty($this->hiddenColumns);

        $keys = $this->cellCollection->getSortedCoordinatesInt();
        $keyIndex = 0;
        $keysCount = count($keys);
                for ($row = $minRow; $row <= $maxRow; ++$row) {
            if (($ignoreHidden === true) && ($this->isRowVisible($row) === false)) {
                continue;
            }
            $rowRef = $returnCellRef ? $row : ($row - $minRow);
            $returnValue[$rowRef] = $nullRow;

            $index = ($row - 1) * AddressRange::MAX_COLUMN_INT + 1;
            $indexPlus = $index + AddressRange::MAX_COLUMN_INT - 1;
            while ($keyIndex < $keysCount && $keys[$keyIndex] < $index) {
                ++$keyIndex;
            }
            while ($keyIndex < $keysCount && $keys[$keyIndex] <= $indexPlus) {
                $key = $keys[$keyIndex];
                $thisRow = intdiv($key - 1, AddressRange::MAX_COLUMN_INT) + 1;
                $thisCol = ($key % AddressRange::MAX_COLUMN_INT) ?: AddressRange::MAX_COLUMN_INT;
                if ($thisCol >= $minColInt && $thisCol <= $maxColInt) {
                    $col = Coordinate::stringFromColumnIndex($thisCol);
                    if ($hideColumns === false || !isset($this->hiddenColumns[$col])) {
                        $columnRef = $returnCellRef ? $col : ($thisCol - $minColInt);
                        $cell = $this->cellCollection->get("{$col}{$thisRow}");
                        if ($cell !== null) {
                            $value = $this->cellToArray($cell, $calculateFormulas, $formatData, $nullValue);
                            if ($value !== $nullValue) {
                                $returnValue[$rowRef][$columnRef] = $value;
                            }
                        }
                    }
                }
                ++$keyIndex;
            }
        }
        unset($this->hiddenColumns);

                return $returnValue;
    }

        private function buildNullRow(
        mixed $nullValue,
        string $minCol,
        string $maxCol,
        bool $returnCellRef,
        bool $ignoreHidden,
    ): array {
        $this->hiddenColumns = [];
        $nullRow = [];
        $c = -1;
        for ($col = $minCol; $col !== $maxCol; ++$col) {
            if ($ignoreHidden === true && $this->columnDimensionExists($col) && $this->getColumnDimension($col)->getVisible() === false) {
                $this->hiddenColumns[$col] = true;
            } else {
                $columnRef = $returnCellRef ? $col : ++$c;
                $nullRow[$columnRef] = $nullValue;
            }
        }

        return $nullRow;
    }

    private function validateNamedRange(string $definedName, bool $returnNullIfInvalid = false): ?DefinedName
    {
        $namedRange = DefinedName::resolveName($definedName, $this);
        if ($namedRange === null) {
            if ($returnNullIfInvalid) {
                return null;
            }

            throw new Exception('Named Range ' . $definedName . ' does not exist.');
        }

        if ($namedRange->isFormula()) {
            if ($returnNullIfInvalid) {
                return null;
            }

            throw new Exception('Defined Named ' . $definedName . ' is a formula, not a range or cell.');
        }

        if ($namedRange->getLocalOnly()) {
            $worksheet = $namedRange->getWorksheet();
            if ($worksheet === null || $this->getHashCode() !== $worksheet->getHashCode()) {
                if ($returnNullIfInvalid) {
                    return null;
                }

                throw new Exception(
                    'Named range ' . $definedName . ' is not accessible from within sheet ' . $this->getTitle()
                );
            }
        }

        return $namedRange;
    }

        public function namedRangeToArray(
        string $definedName,
        mixed $nullValue = null,
        bool $calculateFormulas = true,
        bool $formatData = true,
        bool $returnCellRef = false,
        bool $ignoreHidden = false
    ): array {
        $retVal = [];
        $namedRange = $this->validateNamedRange($definedName);
        if ($namedRange !== null) {
            $cellRange = ltrim(substr($namedRange->getValue(), (int) strrpos($namedRange->getValue(), '!')), '!');
            $cellRange = str_replace('$', '', $cellRange);
            $workSheet = $namedRange->getWorksheet();
            if ($workSheet !== null) {
                $retVal = $workSheet->rangeToArray($cellRange, $nullValue, $calculateFormulas, $formatData, $returnCellRef, $ignoreHidden);
            }
        }

        return $retVal;
    }

        public function toArray(
        mixed $nullValue = null,
        bool $calculateFormulas = true,
        bool $formatData = true,
        bool $returnCellRef = false,
        bool $ignoreHidden = false
    ): array {
                $this->garbageCollect();

                $maxCol = $this->getHighestColumn();
        $maxRow = $this->getHighestRow();

                return $this->rangeToArray("A1:{$maxCol}{$maxRow}", $nullValue, $calculateFormulas, $formatData, $returnCellRef, $ignoreHidden);
    }

        public function getRowIterator(int $startRow = 1, ?int $endRow = null): RowIterator
    {
        return new RowIterator($this, $startRow, $endRow);
    }

        public function getColumnIterator(string $startColumn = 'A', ?string $endColumn = null): ColumnIterator
    {
        return new ColumnIterator($this, $startColumn, $endColumn);
    }

        public function garbageCollect(): static
    {
                $this->cellCollection->get('A1');

                $colRow = $this->cellCollection->getHighestRowAndColumn();
        $highestRow = $colRow['row'];
        $highestColumn = Coordinate::columnIndexFromString($colRow['column']);

                foreach ($this->columnDimensions as $dimension) {
            $highestColumn = max($highestColumn, Coordinate::columnIndexFromString($dimension->getColumnIndex()));
        }

                foreach ($this->rowDimensions as $dimension) {
            $highestRow = max($highestRow, $dimension->getRowIndex());
        }

                if ($highestColumn < 1) {
            $this->cachedHighestColumn = 1;
        } else {
            $this->cachedHighestColumn = $highestColumn;
        }
        $this->cachedHighestRow = $highestRow;

                return $this;
    }

        public function getHashCode(): string
    {
        if ($this->dirty) {
            $this->hash = md5($this->title . $this->autoFilter . ($this->protection->isProtectionEnabled() ? 't' : 'f') . __CLASS__);
            $this->dirty = false;
        }

        return $this->hash;
    }

        public static function extractSheetTitle(?string $range, bool $returnRange = false): array|null|string
    {
        if (empty($range)) {
            return $returnRange ? [null, null] : null;
        }

                if (($sep = strrpos($range, '!')) === false) {
            return $returnRange ? ['', $range] : '';
        }

        if ($returnRange) {
            return [substr($range, 0, $sep), substr($range, $sep + 1)];
        }

        return substr($range, $sep + 1);
    }

        public function getHyperlink(string $cellCoordinate): Hyperlink
    {
                if (isset($this->hyperlinkCollection[$cellCoordinate])) {
            return $this->hyperlinkCollection[$cellCoordinate];
        }

                $this->hyperlinkCollection[$cellCoordinate] = new Hyperlink();

        return $this->hyperlinkCollection[$cellCoordinate];
    }

        public function setHyperlink(string $cellCoordinate, ?Hyperlink $hyperlink = null): static
    {
        if ($hyperlink === null) {
            unset($this->hyperlinkCollection[$cellCoordinate]);
        } else {
            $this->hyperlinkCollection[$cellCoordinate] = $hyperlink;
        }

        return $this;
    }

        public function hyperlinkExists(string $coordinate): bool
    {
        return isset($this->hyperlinkCollection[$coordinate]);
    }

        public function getHyperlinkCollection(): array
    {
        return $this->hyperlinkCollection;
    }

        public function getDataValidation(string $cellCoordinate): DataValidation
    {
                if (isset($this->dataValidationCollection[$cellCoordinate])) {
            return $this->dataValidationCollection[$cellCoordinate];
        }

                $this->dataValidationCollection[$cellCoordinate] = new DataValidation();

        return $this->dataValidationCollection[$cellCoordinate];
    }

        public function setDataValidation(string $cellCoordinate, ?DataValidation $dataValidation = null): static
    {
        if ($dataValidation === null) {
            unset($this->dataValidationCollection[$cellCoordinate]);
        } else {
            $this->dataValidationCollection[$cellCoordinate] = $dataValidation;
        }

        return $this;
    }

        public function dataValidationExists(string $coordinate): bool
    {
        return isset($this->dataValidationCollection[$coordinate]);
    }

        public function getDataValidationCollection(): array
    {
        return $this->dataValidationCollection;
    }

        public function shrinkRangeToFit(string $range): string
    {
        $maxCol = $this->getHighestColumn();
        $maxRow = $this->getHighestRow();
        $maxCol = Coordinate::columnIndexFromString($maxCol);

        $rangeBlocks = explode(' ', $range);
        foreach ($rangeBlocks as &$rangeSet) {
            $rangeBoundaries = Coordinate::getRangeBoundaries($rangeSet);

            if (Coordinate::columnIndexFromString($rangeBoundaries[0][0]) > $maxCol) {
                $rangeBoundaries[0][0] = Coordinate::stringFromColumnIndex($maxCol);
            }
            if ($rangeBoundaries[0][1] > $maxRow) {
                $rangeBoundaries[0][1] = $maxRow;
            }
            if (Coordinate::columnIndexFromString($rangeBoundaries[1][0]) > $maxCol) {
                $rangeBoundaries[1][0] = Coordinate::stringFromColumnIndex($maxCol);
            }
            if ($rangeBoundaries[1][1] > $maxRow) {
                $rangeBoundaries[1][1] = $maxRow;
            }
            $rangeSet = $rangeBoundaries[0][0] . $rangeBoundaries[0][1] . ':' . $rangeBoundaries[1][0] . $rangeBoundaries[1][1];
        }
        unset($rangeSet);

        return implode(' ', $rangeBlocks);
    }

        public function getTabColor(): Color
    {
        if ($this->tabColor === null) {
            $this->tabColor = new Color();
        }

        return $this->tabColor;
    }

        public function resetTabColor(): static
    {
        $this->tabColor = null;

        return $this;
    }

        public function isTabColorSet(): bool
    {
        return $this->tabColor !== null;
    }

        public function copy(): static
    {
        return clone $this;
    }

        public function isEmptyRow(int $rowId, int $definitionOfEmptyFlags = 0): bool
    {
        try {
            $iterator = new RowIterator($this, $rowId, $rowId);
            $iterator->seek($rowId);
            $row = $iterator->current();
        } catch (Exception) {
            return true;
        }

        return $row->isEmpty($definitionOfEmptyFlags);
    }

        public function isEmptyColumn(string $columnId, int $definitionOfEmptyFlags = 0): bool
    {
        try {
            $iterator = new ColumnIterator($this, $columnId, $columnId);
            $iterator->seek($columnId);
            $column = $iterator->current();
        } catch (Exception) {
            return true;
        }

        return $column->isEmpty($definitionOfEmptyFlags);
    }

        public function __clone()
    {
                foreach ($this as $key => $val) {
            if ($key == 'parent') {
                continue;
            }

            if (is_object($val) || (is_array($val))) {
                if ($key == 'cellCollection') {
                    $newCollection = $this->cellCollection->cloneCellCollection($this);
                    $this->cellCollection = $newCollection;
                } elseif ($key == 'drawingCollection') {
                    $currentCollection = $this->drawingCollection;
                    $this->drawingCollection = new ArrayObject();
                    foreach ($currentCollection as $item) {
                        $newDrawing = clone $item;
                        $newDrawing->setWorksheet($this);
                    }
                } elseif ($key == 'tableCollection') {
                    $currentCollection = $this->tableCollection;
                    $this->tableCollection = new ArrayObject();
                    foreach ($currentCollection as $item) {
                        $newTable = clone $item;
                        $newTable->setName($item->getName() . 'clone');
                        $this->addTable($newTable);
                    }
                } elseif ($key == 'chartCollection') {
                    $currentCollection = $this->chartCollection;
                    $this->chartCollection = new ArrayObject();
                    foreach ($currentCollection as $item) {
                        $newChart = clone $item;
                        $this->addChart($newChart);
                    }
                } elseif (($key == 'autoFilter') && ($this->autoFilter instanceof AutoFilter)) {
                    $newAutoFilter = clone $this->autoFilter;
                    $this->autoFilter = $newAutoFilter;
                    $this->autoFilter->setParent($this);
                } else {
                    $this->{$key} = unserialize(serialize($val));
                }
            }
        }
    }

        public function setCodeName(string $codeName, bool $validate = true): static
    {
                if ($this->getCodeName() == $codeName) {
            return $this;
        }

        if ($validate) {
            $codeName = str_replace(' ', '_', $codeName); 
                                    self::checkSheetCodeName($codeName);

            
            if ($this->parent !== null) {
                                if ($this->parent->sheetCodeNameExists($codeName)) {
                    
                    if (Shared\StringHelper::countCharacters($codeName) > 29) {
                        $codeName = Shared\StringHelper::substring($codeName, 0, 29);
                    }
                    $i = 1;
                    while ($this->getParentOrThrow()->sheetCodeNameExists($codeName . '_' . $i)) {
                        ++$i;
                        if ($i == 10) {
                            if (Shared\StringHelper::countCharacters($codeName) > 28) {
                                $codeName = Shared\StringHelper::substring($codeName, 0, 28);
                            }
                        } elseif ($i == 100) {
                            if (Shared\StringHelper::countCharacters($codeName) > 27) {
                                $codeName = Shared\StringHelper::substring($codeName, 0, 27);
                            }
                        }
                    }

                    $codeName .= '_' . $i;                 }
            }
        }

        $this->codeName = $codeName;

        return $this;
    }

        public function getCodeName(): ?string
    {
        return $this->codeName;
    }

        public function hasCodeName(): bool
    {
        return $this->codeName !== null;
    }

    public static function nameRequiresQuotes(string $sheetName): bool
    {
        return preg_match(self::SHEET_NAME_REQUIRES_NO_QUOTES, $sheetName) !== 1;
    }

    public function isRowVisible(int $row): bool
    {
        return !$this->rowDimensionExists($row) || $this->getRowDimension($row)->getVisible();
    }

        public function isCellLocked(string $coordinate): bool
    {
        if ($this->getProtection()->getsheet() !== true) {
            return false;
        }
        if ($this->cellExists($coordinate)) {
            return $this->getCell($coordinate)->isLocked();
        }
        $spreadsheet = $this->parent;
        $xfIndex = $this->getXfIndex($coordinate);
        if ($spreadsheet === null || $xfIndex === null) {
            return true;
        }

        return $spreadsheet->getCellXfByIndex($xfIndex)->getProtection()->getLocked() !== StyleProtection::PROTECTION_UNPROTECTED;
    }

        public function isCellHiddenOnFormulaBar(string $coordinate): bool
    {
        if ($this->cellExists($coordinate)) {
            return $this->getCell($coordinate)->isHiddenOnFormulaBar();
        }

                        return false;
    }

    private function getXfIndex(string $coordinate): ?int
    {
        [$column, $row] = Coordinate::coordinateFromString($coordinate);
        $row = (int) $row;
        $xfIndex = null;
        if ($this->rowDimensionExists($row)) {
            $xfIndex = $this->getRowDimension($row)->getXfIndex();
        }
        if ($xfIndex === null && $this->ColumnDimensionExists($column)) {
            $xfIndex = $this->getColumnDimension($column)->getXfIndex();
        }

        return $xfIndex;
    }

    private string $backgroundImage = '';

    private string $backgroundMime = '';

    private string $backgroundExtension = '';

    public function getBackgroundImage(): string
    {
        return $this->backgroundImage;
    }

    public function getBackgroundMime(): string
    {
        return $this->backgroundMime;
    }

    public function getBackgroundExtension(): string
    {
        return $this->backgroundExtension;
    }

        public function setBackgroundImage(string $backgroundImage): self
    {
        $imageArray = getimagesizefromstring($backgroundImage) ?: ['mime' => ''];
        $mime = $imageArray['mime'];
        if ($mime !== '') {
            $extension = explode('/', $mime);
            $extension = $extension[1];
            $this->backgroundImage = $backgroundImage;
            $this->backgroundMime = $mime;
            $this->backgroundExtension = $extension;
        }

        return $this;
    }

        public function copyCells(string $fromCell, string $toCells, bool $copyStyle = true): void
    {
        $toArray = Coordinate::extractAllCellReferencesInRange($toCells);
        $value = $this->getCell($fromCell)->getValue();
        $style = $this->getStyle($fromCell)->exportArray();
        $fromIndexes = Coordinate::indexesFromString($fromCell);
        $referenceHelper = ReferenceHelper::getInstance();
        foreach ($toArray as $destination) {
            if ($destination !== $fromCell) {
                $toIndexes = Coordinate::indexesFromString($destination);
                $this->getCell($destination)->setValue($referenceHelper->updateFormulaReferences($value, 'A1', $toIndexes[0] - $fromIndexes[0], $toIndexes[1] - $fromIndexes[1]));
                if ($copyStyle) {
                    $this->getCell($destination)->getStyle()->applyFromArray($style);
                }
            }
        }
    }
}
