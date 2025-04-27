<?php

namespace PhpOffice\PhpSpreadsheet\Writer;

use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Document\Properties;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\RichText\Run;
use PhpOffice\PhpSpreadsheet\Settings;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Shared\Drawing as SharedDrawing;
use PhpOffice\PhpSpreadsheet\Shared\File;
use PhpOffice\PhpSpreadsheet\Shared\Font as SharedFont;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Borders;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\BaseDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use voku\helper\AntiXSS;

class Html extends BaseWriter
{
    private const DEFAULT_CELL_WIDTH_POINTS = 42;

    private const DEFAULT_CELL_WIDTH_PIXELS = 56;

        protected Spreadsheet $spreadsheet;

        private ?int $sheetIndex = 0;

        private string $imagesRoot = '';

        protected bool $embedImages = false;

        private bool $useInlineCss = false;

        private ?array $cssStyles = null;

        private array $columnWidths;

        private Font $defaultFont;

        private bool $spansAreCalculated = false;

        private array $isSpannedCell = [];

        private array $isBaseCell = [];

        private array $isSpannedRow = [];

        protected bool $isPdf = false;

        protected bool $isMPdf = false;

        private bool $generateSheetNavigationBlock = true;

        private $editHtmlCallback;

        private $sheetDrawings;

        private $sheetCharts;

        public function __construct(Spreadsheet $spreadsheet)
    {
        $this->spreadsheet = $spreadsheet;
        $this->defaultFont = $this->spreadsheet->getDefaultStyle()->getFont();
    }

        public function save($filename, int $flags = 0): void
    {
        $this->processFlags($flags);

                $this->openFileHandle($filename);

                fwrite($this->fileHandle, $this->generateHTMLAll());

                $this->maybeCloseFileHandle();
    }

        public function generateHtmlAll(): string
    {
                $this->spreadsheet->garbageCollect();

        $saveDebugLog = Calculation::getInstance($this->spreadsheet)->getDebugLog()->getWriteDebugLog();
        Calculation::getInstance($this->spreadsheet)->getDebugLog()->setWriteDebugLog(false);
        $saveArrayReturnType = Calculation::getArrayReturnType();
        Calculation::setArrayReturnType(Calculation::RETURN_ARRAY_AS_VALUE);

                $this->buildCSS(!$this->useInlineCss);

        $html = '';

                $html .= $this->generateHTMLHeader(!$this->useInlineCss);

                if ((!$this->isPdf) && ($this->generateSheetNavigationBlock)) {
            $html .= $this->generateNavigation();
        }

                $html .= $this->generateSheetData();

                $html .= $this->generateHTMLFooter();
        $callback = $this->editHtmlCallback;
        if ($callback) {
            $html = $callback($html);
        }

        Calculation::setArrayReturnType($saveArrayReturnType);
        Calculation::getInstance($this->spreadsheet)->getDebugLog()->setWriteDebugLog($saveDebugLog);

        return $html;
    }

        public function setEditHtmlCallback(?callable $callback): void
    {
        $this->editHtmlCallback = $callback;
    }

        private function mapVAlign(string $vAlign): string
    {
        return Alignment::VERTICAL_ALIGNMENT_FOR_HTML[$vAlign] ?? '';
    }

        private function mapHAlign(string $hAlign): string
    {
        return Alignment::HORIZONTAL_ALIGNMENT_FOR_HTML[$hAlign] ?? '';
    }

    const BORDER_ARR = [
        Border::BORDER_NONE => 'none',
        Border::BORDER_DASHDOT => '1px dashed',
        Border::BORDER_DASHDOTDOT => '1px dotted',
        Border::BORDER_DASHED => '1px dashed',
        Border::BORDER_DOTTED => '1px dotted',
        Border::BORDER_DOUBLE => '3px double',
        Border::BORDER_HAIR => '1px solid',
        Border::BORDER_MEDIUM => '2px solid',
        Border::BORDER_MEDIUMDASHDOT => '2px dashed',
        Border::BORDER_MEDIUMDASHDOTDOT => '2px dotted',
        Border::BORDER_SLANTDASHDOT => '2px dashed',
        Border::BORDER_THICK => '3px solid',
    ];

        private function mapBorderStyle($borderStyle): string
    {
        return array_key_exists($borderStyle, self::BORDER_ARR) ? self::BORDER_ARR[$borderStyle] : '1px solid';
    }

        public function getSheetIndex(): ?int
    {
        return $this->sheetIndex;
    }

        public function setSheetIndex(int $sheetIndex): static
    {
        $this->sheetIndex = $sheetIndex;

        return $this;
    }

        public function getGenerateSheetNavigationBlock(): bool
    {
        return $this->generateSheetNavigationBlock;
    }

        public function setGenerateSheetNavigationBlock(bool $generateSheetNavigationBlock): static
    {
        $this->generateSheetNavigationBlock = (bool) $generateSheetNavigationBlock;

        return $this;
    }

        public function writeAllSheets(): static
    {
        $this->sheetIndex = null;

        return $this;
    }

    private static function generateMeta(?string $val, string $desc): string
    {
        return ($val || $val === '0')
            ? ('      <meta name="' . $desc . '" content="' . htmlspecialchars($val, Settings::htmlEntityFlags()) . '" />' . PHP_EOL)
            : '';
    }

    public const BODY_LINE = '  <body>' . PHP_EOL;

    private const CUSTOM_TO_META = [
        Properties::PROPERTY_TYPE_BOOLEAN => 'bool',
        Properties::PROPERTY_TYPE_DATE => 'date',
        Properties::PROPERTY_TYPE_FLOAT => 'float',
        Properties::PROPERTY_TYPE_INTEGER => 'int',
        Properties::PROPERTY_TYPE_STRING => 'string',
    ];

        public function generateHTMLHeader(bool $includeStyles = false): string
    {
                $properties = $this->spreadsheet->getProperties();
        $html = '<!DOCTYPE html PUBLIC "-        $html .= '<html xmlns="http:        $html .= '  <head>' . PHP_EOL;
        $html .= '      <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />' . PHP_EOL;
        $html .= '      <meta name="generator" content="PhpSpreadsheet, https:        $html .= '      <title>' . htmlspecialchars($properties->getTitle(), Settings::htmlEntityFlags()) . '</title>' . PHP_EOL;
        $html .= self::generateMeta($properties->getCreator(), 'author');
        $html .= self::generateMeta($properties->getTitle(), 'title');
        $html .= self::generateMeta($properties->getDescription(), 'description');
        $html .= self::generateMeta($properties->getSubject(), 'subject');
        $html .= self::generateMeta($properties->getKeywords(), 'keywords');
        $html .= self::generateMeta($properties->getCategory(), 'category');
        $html .= self::generateMeta($properties->getCompany(), 'company');
        $html .= self::generateMeta($properties->getManager(), 'manager');
        $html .= self::generateMeta($properties->getLastModifiedBy(), 'lastModifiedBy');
        $html .= self::generateMeta($properties->getViewport(), 'viewport');
        $date = Date::dateTimeFromTimestamp((string) $properties->getCreated());
        $date->setTimeZone(Date::getDefaultOrLocalTimeZone());
        $html .= self::generateMeta($date->format(DATE_W3C), 'created');
        $date = Date::dateTimeFromTimestamp((string) $properties->getModified());
        $date->setTimeZone(Date::getDefaultOrLocalTimeZone());
        $html .= self::generateMeta($date->format(DATE_W3C), 'modified');

        $customProperties = $properties->getCustomProperties();
        foreach ($customProperties as $customProperty) {
            $propertyValue = $properties->getCustomPropertyValue($customProperty);
            $propertyType = $properties->getCustomPropertyType($customProperty);
            $propertyQualifier = self::CUSTOM_TO_META[$propertyType] ?? null;
            if ($propertyQualifier !== null) {
                if ($propertyType === Properties::PROPERTY_TYPE_BOOLEAN) {
                    $propertyValue = $propertyValue ? '1' : '0';
                } elseif ($propertyType === Properties::PROPERTY_TYPE_DATE) {
                    $date = Date::dateTimeFromTimestamp((string) $propertyValue);
                    $date->setTimeZone(Date::getDefaultOrLocalTimeZone());
                    $propertyValue = $date->format(DATE_W3C);
                } else {
                    $propertyValue = (string) $propertyValue;
                }
                $html .= self::generateMeta($propertyValue, "custom.$propertyQualifier.$customProperty");
            }
        }

        if (!empty($properties->getHyperlinkBase())) {
            $html .= '      <base href="' . $properties->getHyperlinkBase() . '" />' . PHP_EOL;
        }

        $html .= $includeStyles ? $this->generateStyles(true) : $this->generatePageDeclarations(true);

        $html .= '  </head>' . PHP_EOL;
        $html .= '' . PHP_EOL;
        $html .= self::BODY_LINE;

        return $html;
    }

    private function generateSheetPrep(): array
    {
                $this->calculateSpans();

                if ($this->sheetIndex === null) {
            $sheets = $this->spreadsheet->getAllSheets();
        } else {
            $sheets = [$this->spreadsheet->getSheet($this->sheetIndex)];
        }

        return $sheets;
    }

    private function generateSheetStarts(Worksheet $sheet, int $rowMin): array
    {
                $tbodyStart = $rowMin;
        $theadStart = $theadEnd = 0;         if ($sheet->getPageSetup()->isRowsToRepeatAtTopSet()) {
            $rowsToRepeatAtTop = $sheet->getPageSetup()->getRowsToRepeatAtTop();

                        if ($rowsToRepeatAtTop[0] == 1) {
                $theadStart = $rowsToRepeatAtTop[0];
                $theadEnd = $rowsToRepeatAtTop[1];
                $tbodyStart = $rowsToRepeatAtTop[1] + 1;
            }
        }

        return [$theadStart, $theadEnd, $tbodyStart];
    }

    private function generateSheetTags(int $row, int $theadStart, int $theadEnd, int $tbodyStart): array
    {
                $startTag = ($row == $theadStart) ? ('        <thead>' . PHP_EOL) : '';
        if (!$startTag) {
            $startTag = ($row == $tbodyStart) ? ('        <tbody>' . PHP_EOL) : '';
        }
        $endTag = ($row == $theadEnd) ? ('        </thead>' . PHP_EOL) : '';
        $cellType = ($row >= $tbodyStart) ? 'td' : 'th';

        return [$cellType, $startTag, $endTag];
    }

        public function generateSheetData(): string
    {
        $sheets = $this->generateSheetPrep();

                $html = '';

                $sheetId = 0;
        foreach ($sheets as $sheet) {
                        $html .= $this->generateTableHeader($sheet);
            $this->sheetCharts = [];
            $this->sheetDrawings = [];

                        [$min, $max] = explode(':', $sheet->calculateWorksheetDataDimension());
            [$minCol, $minRow] = Coordinate::indexesFromString($min);
            [$maxCol, $maxRow] = Coordinate::indexesFromString($max);
            $this->extendRowsAndColumns($sheet, $maxCol, $maxRow);

            [$theadStart, $theadEnd, $tbodyStart] = $this->generateSheetStarts($sheet, $minRow);

                        $row = $minRow - 1;
            while ($row++ < $maxRow) {
                [$cellType, $startTag, $endTag] = $this->generateSheetTags($row, $theadStart, $theadEnd, $tbodyStart);
                $html .= $startTag;

                                $mpdfInvisible = $this->isMPdf && !$sheet->isRowVisible($row);
                if (!$mpdfInvisible && !isset($this->isSpannedRow[$sheet->getParent()->getIndex($sheet)][$row])) {
                                        $rowData = [];
                                        $column = $minCol;
                    while ($column <= $maxCol) {
                                                $cellAddress = Coordinate::stringFromColumnIndex($column) . $row;
                        $rowData[$column++] = ($sheet->getCellCollection()->has($cellAddress)) ? $cellAddress : '';
                    }
                    $html .= $this->generateRow($sheet, $rowData, $row - 1, $cellType);
                }

                $html .= $endTag;
            }

                        $html .= $this->generateTableFooter();
                        if ($this->isPdf && $this->useInlineCss) {
                if ($this->sheetIndex === null && $sheetId + 1 < $this->spreadsheet->getSheetCount()) {
                    $html .= '<div style="page-break-before:always" ></div>';
                }
            }

                        ++$sheetId;
        }

        return $html;
    }

        public function generateNavigation(): string
    {
                $sheets = [];
        if ($this->sheetIndex === null) {
            $sheets = $this->spreadsheet->getAllSheets();
        } else {
            $sheets[] = $this->spreadsheet->getSheet($this->sheetIndex);
        }

                $html = '';

                if (count($sheets) > 1) {
                        $sheetId = 0;

            $html .= '<ul class="navigation">' . PHP_EOL;

            foreach ($sheets as $sheet) {
                $html .= '  <li class="sheet' . $sheetId . '"><a href="#sheet' . $sheetId . '">' . $sheet->getTitle() . '</a></li>' . PHP_EOL;
                ++$sheetId;
            }

            $html .= '</ul>' . PHP_EOL;
        }

        return $html;
    }

    private function extendRowsAndColumns(Worksheet $worksheet, int &$colMax, int &$rowMax): void
    {
        if ($this->includeCharts) {
            foreach ($worksheet->getChartCollection() as $chart) {
                if ($chart instanceof Chart) {
                    $chartCoordinates = $chart->getTopLeftPosition();
                    $this->sheetCharts[$chartCoordinates['cell']] = $chart;
                    $chartTL = Coordinate::indexesFromString($chartCoordinates['cell']);
                    if ($chartTL[1] > $rowMax) {
                        $rowMax = $chartTL[1];
                    }
                    if ($chartTL[0] > $colMax) {
                        $colMax = $chartTL[0];
                    }
                }
            }
        }
        foreach ($worksheet->getDrawingCollection() as $drawing) {
            $imageTL = Coordinate::indexesFromString($drawing->getCoordinates());
            $this->sheetDrawings[$drawing->getCoordinates()] = $drawing;
            if ($imageTL[1] > $rowMax) {
                $rowMax = $imageTL[1];
            }
            if ($imageTL[0] > $colMax) {
                $colMax = $imageTL[0];
            }
        }
    }

        public static function winFileToUrl(string $filename, bool $mpdf = false): string
    {
                if (substr($filename, 1, 2) === ':\\') {
            $protocol = $mpdf ? '' : 'file:            $filename = $protocol . str_replace('\\', '/', $filename);
        }

        return $filename;
    }

        private function writeImageInCell(string $coordinates): string
    {
                $html = '';

                $drawing = $this->sheetDrawings[$coordinates] ?? null;
        if ($drawing !== null) {
            $filedesc = $drawing->getDescription();
            $filedesc = $filedesc ? htmlspecialchars($filedesc, ENT_QUOTES) : 'Embedded image';
            if ($drawing instanceof Drawing) {
                $filename = $drawing->getPath();

                                $filename = (string) preg_replace('/^[.]/', '', $filename);

                                $filename = $this->getImagesRoot() . $filename;

                                $filename = (string) preg_replace('@^[.]([^/])@', '$1', $filename);

                                $filename = htmlspecialchars($filename, Settings::htmlEntityFlags());

                $html .= PHP_EOL;
                $imageData = self::winFileToUrl($filename, $this->isMPdf);

                if ($this->embedImages || str_starts_with($imageData, 'zip:                    $picture = @file_get_contents($filename);
                    if ($picture !== false) {
                        $imageDetails = getimagesize($filename) ?: ['mime' => ''];
                                                $base64 = base64_encode($picture);
                        $imageData = 'data:' . $imageDetails['mime'] . ';base64,' . $base64;
                    }
                }

                $html .= '<img style="position: absolute; z-index: 1; left: '
                    . $drawing->getOffsetX() . 'px; top: ' . $drawing->getOffsetY() . 'px; width: '
                    . $drawing->getWidth() . 'px; height: ' . $drawing->getHeight() . 'px;" src="'
                    . $imageData . '" alt="' . $filedesc . '" />';
            } elseif ($drawing instanceof MemoryDrawing) {
                $imageResource = $drawing->getImageResource();
                if ($imageResource) {
                    ob_start();                     imagepng($imageResource);                     $contents = (string) ob_get_contents();                     ob_end_clean(); 
                    $dataUri = 'data:image/png;base64,' . base64_encode($contents);

                                                                                                                                                                                                        $html .= '<img alt="' . $filedesc . '" src="' . $dataUri . '" style="width:' . $drawing->getWidth() . 'px;left: '
                        . $drawing->getOffsetX() . 'px; top: ' . $drawing->getOffsetY() . 'px;position: absolute; z-index: 1;" />';
                }
            }
        }

        return $html;
    }

        private function writeChartInCell(Worksheet $worksheet, string $coordinates): string
    {
                $html = '';

                $chart = $this->sheetCharts[$coordinates] ?? null;
        if ($chart !== null) {
            $chartCoordinates = $chart->getTopLeftPosition();
            $chartFileName = File::sysGetTempDir() . '/' . uniqid('', true) . '.png';
            $renderedWidth = $chart->getRenderedWidth();
            $renderedHeight = $chart->getRenderedHeight();
            if ($renderedWidth === null || $renderedHeight === null) {
                $this->adjustRendererPositions($chart, $worksheet);
            }
            $title = $chart->getTitle();
            $caption = null;
            $filedesc = '';
            if ($title !== null) {
                $calculatedTitle = $title->getCalculatedTitle($worksheet->getParent());
                if ($calculatedTitle !== null) {
                    $caption = $title->getCaption();
                    $title->setCaption($calculatedTitle);
                }
                $filedesc = $title->getCaptionText($worksheet->getParent());
            }
            $renderSuccessful = $chart->render($chartFileName);
            $chart->setRenderedWidth($renderedWidth);
            $chart->setRenderedHeight($renderedHeight);
            if (isset($title, $caption)) {
                $title->setCaption($caption);
            }
            if (!$renderSuccessful) {
                return '';
            }

            $html .= PHP_EOL;
            $imageDetails = getimagesize($chartFileName) ?: ['', '', 'mime' => ''];

            $filedesc = $filedesc ? htmlspecialchars($filedesc, ENT_QUOTES) : 'Embedded chart';
            $picture = file_get_contents($chartFileName);
            unlink($chartFileName);
            if ($picture !== false) {
                $base64 = base64_encode($picture);
                $imageData = 'data:' . $imageDetails['mime'] . ';base64,' . $base64;

                $html .= '<img style="position: absolute; z-index: 1; left: ' . $chartCoordinates['xOffset'] . 'px; top: ' . $chartCoordinates['yOffset'] . 'px; width: ' . $imageDetails[0] . 'px; height: ' . $imageDetails[1] . 'px;" src="' . $imageData . '" alt="' . $filedesc . '" />' . PHP_EOL;
            }
        }

                return $html;
    }

    private function adjustRendererPositions(Chart $chart, Worksheet $sheet): void
    {
        $topLeft = $chart->getTopLeftPosition();
        $bottomRight = $chart->getBottomRightPosition();
        $tlCell = $topLeft['cell'];
        $brCell = $bottomRight['cell'];
        if ($tlCell !== '' && $brCell !== '') {
            $tlCoordinate = Coordinate::indexesFromString($tlCell);
            $brCoordinate = Coordinate::indexesFromString($brCell);
            $totalHeight = 0.0;
            $totalWidth = 0.0;
            $defaultRowHeight = $sheet->getDefaultRowDimension()->getRowHeight();
            $defaultRowHeight = SharedDrawing::pointsToPixels(($defaultRowHeight >= 0) ? $defaultRowHeight : SharedFont::getDefaultRowHeightByFont($this->defaultFont));
            if ($tlCoordinate[1] <= $brCoordinate[1] && $tlCoordinate[0] <= $brCoordinate[0]) {
                for ($row = $tlCoordinate[1]; $row <= $brCoordinate[1]; ++$row) {
                    $height = $sheet->getRowDimension($row)->getRowHeight('pt');
                    $totalHeight += ($height >= 0) ? $height : $defaultRowHeight;
                }
                $rightEdge = $brCoordinate[2];
                ++$rightEdge;
                for ($column = $tlCoordinate[2]; $column !== $rightEdge; ++$column) {
                    $width = $sheet->getColumnDimension($column)->getWidth();
                    $width = ($width < 0) ? self::DEFAULT_CELL_WIDTH_PIXELS : SharedDrawing::cellDimensionToPixels($sheet->getColumnDimension($column)->getWidth(), $this->defaultFont);
                    $totalWidth += $width;
                }
                $chart->setRenderedWidth($totalWidth);
                $chart->setRenderedHeight($totalHeight);
            }
        }
    }

        public function generateStyles(bool $generateSurroundingHTML = true): string
    {
                $css = $this->buildCSS($generateSurroundingHTML);

                $html = '';

                if ($generateSurroundingHTML) {
            $html .= '    <style type="text/css">' . PHP_EOL;
            $html .= (array_key_exists('html', $css)) ? ('      html { ' . $this->assembleCSS($css['html']) . ' }' . PHP_EOL) : '';
        }

                foreach ($css as $styleName => $styleDefinition) {
            if ($styleName != 'html') {
                $html .= '      ' . $styleName . ' { ' . $this->assembleCSS($styleDefinition) . ' }' . PHP_EOL;
            }
        }
        $html .= $this->generatePageDeclarations(false);

                if ($generateSurroundingHTML) {
            $html .= '    </style>' . PHP_EOL;
        }

                return $html;
    }

    private function buildCssRowHeights(Worksheet $sheet, array &$css, int $sheetIndex): void
    {
                foreach ($sheet->getRowDimensions() as $rowDimension) {
            $row = $rowDimension->getRowIndex() - 1;

                        $css['table.sheet' . $sheetIndex . ' tr.row' . $row] = [];

            if ($rowDimension->getRowHeight() != -1) {
                $pt_height = $rowDimension->getRowHeight();
                $css['table.sheet' . $sheetIndex . ' tr.row' . $row]['height'] = $pt_height . 'pt';
            }
            if ($rowDimension->getVisible() === false) {
                $css['table.sheet' . $sheetIndex . ' tr.row' . $row]['display'] = 'none';
                $css['table.sheet' . $sheetIndex . ' tr.row' . $row]['visibility'] = 'hidden';
            }
        }
    }

    private function buildCssPerSheet(Worksheet $sheet, array &$css): void
    {
                $sheetIndex = $sheet->getParentOrThrow()->getIndex($sheet);
        $setup = $sheet->getPageSetup();
        if ($setup->getFitToPage() && $setup->getFitToHeight() === 1) {
            $css["table.sheet$sheetIndex"]['page-break-inside'] = 'avoid';
            $css["table.sheet$sheetIndex"]['break-inside'] = 'avoid';
        }
        $picture = $sheet->getBackgroundImage();
        if ($picture !== '') {
            $base64 = base64_encode($picture);
            $css["table.sheet$sheetIndex"]['background-image'] = 'url(data:' . $sheet->getBackgroundMime() . ';base64,' . $base64 . ')';
        }

                        $sheet->calculateColumnWidths();

                $highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestColumn()) - 1;
        $column = -1;
        while ($column++ < $highestColumnIndex) {
            $this->columnWidths[$sheetIndex][$column] = self::DEFAULT_CELL_WIDTH_POINTS;             $css['table.sheet' . $sheetIndex . ' col.col' . $column]['width'] = self::DEFAULT_CELL_WIDTH_POINTS . 'pt';
        }

                foreach ($sheet->getColumnDimensions() as $columnDimension) {
            $column = Coordinate::columnIndexFromString($columnDimension->getColumnIndex()) - 1;
            $width = SharedDrawing::cellDimensionToPixels($columnDimension->getWidth(), $this->defaultFont);
            $width = SharedDrawing::pixelsToPoints($width);
            if ($columnDimension->getVisible() === false) {
                $css['table.sheet' . $sheetIndex . ' .column' . $column]['display'] = 'none';
            }
            if ($width >= 0) {
                $this->columnWidths[$sheetIndex][$column] = $width;
                $css['table.sheet' . $sheetIndex . ' col.col' . $column]['width'] = $width . 'pt';
            }
        }

                $rowDimension = $sheet->getDefaultRowDimension();

                $css['table.sheet' . $sheetIndex . ' tr'] = [];

        if ($rowDimension->getRowHeight() == -1) {
            $pt_height = SharedFont::getDefaultRowHeightByFont($this->spreadsheet->getDefaultStyle()->getFont());
        } else {
            $pt_height = $rowDimension->getRowHeight();
        }
        $css['table.sheet' . $sheetIndex . ' tr']['height'] = $pt_height . 'pt';
        if ($rowDimension->getVisible() === false) {
            $css['table.sheet' . $sheetIndex . ' tr']['display'] = 'none';
            $css['table.sheet' . $sheetIndex . ' tr']['visibility'] = 'hidden';
        }

        $this->buildCssRowHeights($sheet, $css, $sheetIndex);
    }

        public function buildCSS(bool $generateSurroundingHTML = true): array
    {
                if ($this->cssStyles !== null) {
            return $this->cssStyles;
        }

                $this->calculateSpans();

                $css = [];

                if ($generateSurroundingHTML) {
                        $css['html']['font-family'] = 'Calibri, Arial, Helvetica, sans-serif';
            $css['html']['font-size'] = '11pt';
            $css['html']['background-color'] = 'white';
        }

                $css['a.comment-indicator:hover + div.comment'] = [
            'background' => '#ffd',
            'position' => 'absolute',
            'display' => 'block',
            'border' => '1px solid black',
            'padding' => '0.5em',
        ];

        $css['a.comment-indicator'] = [
            'background' => 'red',
            'display' => 'inline-block',
            'border' => '1px solid black',
            'width' => '0.5em',
            'height' => '0.5em',
        ];

        $css['div.comment']['display'] = 'none';

                $css['table']['border-collapse'] = 'collapse';

                $css['.b']['text-align'] = 'center'; 
                $css['.e']['text-align'] = 'center'; 
                $css['.f']['text-align'] = 'right'; 
                $css['.inlineStr']['text-align'] = 'left'; 
                $css['.n']['text-align'] = 'right'; 
                $css['.s']['text-align'] = 'left'; 
                foreach ($this->spreadsheet->getCellXfCollection() as $index => $style) {
            $css['td.style' . $index . ', th.style' . $index] = $this->createCSSStyle($style);
                    }

                $sheets = [];
        if ($this->sheetIndex === null) {
            $sheets = $this->spreadsheet->getAllSheets();
        } else {
            $sheets[] = $this->spreadsheet->getSheet($this->sheetIndex);
        }

                foreach ($sheets as $sheet) {
            $this->buildCssPerSheet($sheet, $css);
        }

                if ($this->cssStyles === null) {
            $this->cssStyles = $css;
        }

                return $css;
    }

        private function createCSSStyle(Style $style): array
    {
                return array_merge(
            $this->createCSSStyleAlignment($style->getAlignment()),
            $this->createCSSStyleBorders($style->getBorders()),
            $this->createCSSStyleFont($style->getFont()),
            $this->createCSSStyleFill($style->getFill())
        );
    }

        private function createCSSStyleAlignment(Alignment $alignment): array
    {
                $css = [];

                $verticalAlign = $this->mapVAlign($alignment->getVertical() ?? '');
        if ($verticalAlign) {
            $css['vertical-align'] = $verticalAlign;
        }
        $textAlign = $this->mapHAlign($alignment->getHorizontal() ?? '');
        if ($textAlign) {
            $css['text-align'] = $textAlign;
            if (in_array($textAlign, ['left', 'right'])) {
                $css['padding-' . $textAlign] = (string) ((int) $alignment->getIndent() * 9) . 'px';
            }
        }
        $rotation = $alignment->getTextRotation();
        if ($rotation !== 0 && $rotation !== Alignment::TEXTROTATION_STACK_PHPSPREADSHEET) {
            if ($this->isMPdf) {
                $css['text-rotate'] = "$rotation";
            } else {
                $css['transform'] = "rotate({$rotation}deg)";
            }
        }

        return $css;
    }

        private function createCSSStyleFont(Font $font): array
    {
                $css = [];

                if ($font->getBold()) {
            $css['font-weight'] = 'bold';
        }
        if ($font->getUnderline() != Font::UNDERLINE_NONE && $font->getStrikethrough()) {
            $css['text-decoration'] = 'underline line-through';
        } elseif ($font->getUnderline() != Font::UNDERLINE_NONE) {
            $css['text-decoration'] = 'underline';
        } elseif ($font->getStrikethrough()) {
            $css['text-decoration'] = 'line-through';
        }
        if ($font->getItalic()) {
            $css['font-style'] = 'italic';
        }

        $css['color'] = '#' . $font->getColor()->getRGB();
        $css['font-family'] = '\'' . $font->getName() . '\'';
        $css['font-size'] = $font->getSize() . 'pt';

        return $css;
    }

        private function createCSSStyleBorders(Borders $borders): array
    {
                $css = [];

                $css['border-bottom'] = $this->createCSSStyleBorder($borders->getBottom());
        $css['border-top'] = $this->createCSSStyleBorder($borders->getTop());
        $css['border-left'] = $this->createCSSStyleBorder($borders->getLeft());
        $css['border-right'] = $this->createCSSStyleBorder($borders->getRight());

        return $css;
    }

        private function createCSSStyleBorder(Border $border): string
    {
                $borderStyle = $this->mapBorderStyle($border->getBorderStyle());

        return $borderStyle . ' #' . $border->getColor()->getRGB() . (($borderStyle == 'none') ? '' : ' !important');
    }

        private function createCSSStyleFill(Fill $fill): array
    {
                $css = [];

                if ($fill->getFillType() !== Fill::FILL_NONE) {
            $value = $fill->getFillType() == Fill::FILL_NONE
                ? 'white' : '#' . $fill->getStartColor()->getRGB();
            $css['background-color'] = $value;
        }

        return $css;
    }

        public function generateHTMLFooter(): string
    {
                $html = '';
        $html .= '  </body>' . PHP_EOL;
        $html .= '</html>' . PHP_EOL;

        return $html;
    }

    private function generateTableTagInline(Worksheet $worksheet, string $id): string
    {
        $style = isset($this->cssStyles['table'])
            ? $this->assembleCSS($this->cssStyles['table']) : '';

        $prntgrid = $worksheet->getPrintGridlines();
        $viewgrid = $this->isPdf ? $prntgrid : $worksheet->getShowGridlines();
        if ($viewgrid && $prntgrid) {
            $html = "    <table border='1' cellpadding='1' $id cellspacing='1' style='$style' class='gridlines gridlinesp'>" . PHP_EOL;
        } elseif ($viewgrid) {
            $html = "    <table border='0' cellpadding='0' $id cellspacing='0' style='$style' class='gridlines'>" . PHP_EOL;
        } elseif ($prntgrid) {
            $html = "    <table border='0' cellpadding='0' $id cellspacing='0' style='$style' class='gridlinesp'>" . PHP_EOL;
        } else {
            $html = "    <table border='0' cellpadding='1' $id cellspacing='0' style='$style'>" . PHP_EOL;
        }

        return $html;
    }

    private function generateTableTag(Worksheet $worksheet, string $id, string &$html, int $sheetIndex): void
    {
        if (!$this->useInlineCss) {
            $gridlines = $worksheet->getShowGridlines() ? ' gridlines' : '';
            $gridlinesp = $worksheet->getPrintGridlines() ? ' gridlinesp' : '';
            $html .= "    <table border='0' cellpadding='0' cellspacing='0' $id class='sheet$sheetIndex$gridlines$gridlinesp'>" . PHP_EOL;
        } else {
            $html .= $this->generateTableTagInline($worksheet, $id);
        }
    }

        private function generateTableHeader(Worksheet $worksheet, bool $showid = true): string
    {
        $sheetIndex = $worksheet->getParentOrThrow()->getIndex($worksheet);

                $html = '';
        $id = $showid ? "id='sheet$sheetIndex'" : '';
        if ($showid) {
            $html .= "<div style='page: page$sheetIndex'>" . PHP_EOL;
        } else {
            $html .= "<div style='page: page$sheetIndex' class='scrpgbrk'>" . PHP_EOL;
        }

        $this->generateTableTag($worksheet, $id, $html, $sheetIndex);

                $highestColumnIndex = Coordinate::columnIndexFromString($worksheet->getHighestColumn()) - 1;
        $i = -1;
        while ($i++ < $highestColumnIndex) {
            if (!$this->useInlineCss) {
                $html .= '        <col class="col' . $i . '" />' . PHP_EOL;
            } else {
                $style = isset($this->cssStyles['table.sheet' . $sheetIndex . ' col.col' . $i])
                    ? $this->assembleCSS($this->cssStyles['table.sheet' . $sheetIndex . ' col.col' . $i]) : '';
                $html .= '        <col style="' . $style . '" />' . PHP_EOL;
            }
        }

        return $html;
    }

        private function generateTableFooter(): string
    {
        return '    </tbody></table>' . PHP_EOL . '</div>' . PHP_EOL;
    }

        private function generateRowStart(Worksheet $worksheet, int $sheetIndex, int $row): string
    {
        $html = '';
        if (count($worksheet->getBreaks()) > 0) {
            $breaks = $worksheet->getRowBreaks();

                        if (isset($breaks['A' . $row])) {
                                $html .= $this->generateTableFooter();
                if ($this->isPdf && $this->useInlineCss) {
                    $html .= '<div style="page-break-before:always" />';
                }

                                $html .= $this->generateTableHeader($worksheet, false);
                $html .= '<tbody>' . PHP_EOL;
            }
        }

                if (!$this->useInlineCss) {
            $html .= '          <tr class="row' . $row . '">' . PHP_EOL;
        } else {
            $style = isset($this->cssStyles['table.sheet' . $sheetIndex . ' tr.row' . $row])
                ? $this->assembleCSS($this->cssStyles['table.sheet' . $sheetIndex . ' tr.row' . $row]) : '';

            $html .= '          <tr style="' . $style . '">' . PHP_EOL;
        }

        return $html;
    }

    private function generateRowCellCss(Worksheet $worksheet, string $cellAddress, int $row, int $columnNumber): array
    {
        $cell = ($cellAddress > '') ? $worksheet->getCellCollection()->get($cellAddress) : '';
        $coordinate = Coordinate::stringFromColumnIndex($columnNumber + 1) . ($row + 1);
        if (!$this->useInlineCss) {
            $cssClass = 'column' . $columnNumber;
        } else {
            $cssClass = [];
                                                                                                                                                                    }

        return [$cell, $cssClass, $coordinate];
    }

    private function generateRowCellDataValueRich(Cell $cell, string &$cellData): void
    {
                $elements = $cell->getValue()->getRichTextElements();
        foreach ($elements as $element) {
                        if ($element instanceof Run) {
                $cellEnd = '';
                if ($element->getFont() !== null) {
                    $cellData .= '<span style="' . $this->assembleCSS($this->createCSSStyleFont($element->getFont())) . '">';

                    if ($element->getFont()->getSuperscript()) {
                        $cellData .= '<sup>';
                        $cellEnd = '</sup>';
                    } elseif ($element->getFont()->getSubscript()) {
                        $cellData .= '<sub>';
                        $cellEnd = '</sub>';
                    }
                }

                                $cellText = $element->getText();
                $cellData .= htmlspecialchars($cellText, Settings::htmlEntityFlags());

                $cellData .= $cellEnd;

                $cellData .= '</span>';
            } else {
                                $cellText = $element->getText();
                $cellData .= htmlspecialchars($cellText, Settings::htmlEntityFlags());
            }
        }
    }

    private function generateRowCellDataValue(Worksheet $worksheet, Cell $cell, string &$cellData): void
    {
        if ($cell->getValue() instanceof RichText) {
            $this->generateRowCellDataValueRich($cell, $cellData);
        } else {
            $origData = $this->preCalculateFormulas ? $cell->getCalculatedValue() : $cell->getValue();
            $formatCode = $worksheet->getParentOrThrow()->getCellXfByIndex($cell->getXfIndex())->getNumberFormat()->getFormatCode();

            $cellData = NumberFormat::toFormattedString(
                $origData ?? '',
                $formatCode ?? NumberFormat::FORMAT_GENERAL,
                [$this, 'formatColor']
            );

            if ($cellData === $origData) {
                $cellData = htmlspecialchars($cellData, Settings::htmlEntityFlags());
            }
            if ($worksheet->getParentOrThrow()->getCellXfByIndex($cell->getXfIndex())->getFont()->getSuperscript()) {
                $cellData = '<sup>' . $cellData . '</sup>';
            } elseif ($worksheet->getParentOrThrow()->getCellXfByIndex($cell->getXfIndex())->getFont()->getSubscript()) {
                $cellData = '<sub>' . $cellData . '</sub>';
            }
        }
    }

    private function generateRowCellData(Worksheet $worksheet, null|Cell|string $cell, array|string &$cssClass): string
    {
        $cellData = '&nbsp;';
        if ($cell instanceof Cell) {
            $cellData = '';
                                                                        $this->generateRowCellDataValue($worksheet, $cell, $cellData);

                                    $cellData = (string) preg_replace('/(?m)(?:^|\\G) /', '&nbsp;', $cellData);

                        $cellData = nl2br($cellData);

                        if (!$this->useInlineCss && is_string($cssClass)) {
                $cssClass .= ' style' . $cell->getXfIndex();
                $cssClass .= ' ' . $cell->getDataType();
            } elseif (is_array($cssClass)) {
                $index = $cell->getXfIndex();
                $styleIndex = 'td.style' . $index . ', th.style' . $index;
                if (isset($this->cssStyles[$styleIndex])) {
                    $cssClass = array_merge($cssClass, $this->cssStyles[$styleIndex]);
                }

                                $sharedStyle = $worksheet->getParentOrThrow()->getCellXfByIndex($cell->getXfIndex());
                if (
                    $sharedStyle->getAlignment()->getHorizontal() == Alignment::HORIZONTAL_GENERAL
                    && isset($this->cssStyles['.' . $cell->getDataType()]['text-align'])
                ) {
                    $cssClass['text-align'] = $this->cssStyles['.' . $cell->getDataType()]['text-align'];
                }
            }
        } else {
                        if (is_string($cssClass)) {
                $cssClass .= ' style0';
            }
        }

        return $cellData;
    }

    private function generateRowIncludeCharts(Worksheet $worksheet, string $coordinate): string
    {
        return $this->includeCharts ? $this->writeChartInCell($worksheet, $coordinate) : '';
    }

    private function generateRowSpans(string $html, int $rowSpan, int $colSpan): string
    {
        $html .= ($colSpan > 1) ? (' colspan="' . $colSpan . '"') : '';
        $html .= ($rowSpan > 1) ? (' rowspan="' . $rowSpan . '"') : '';

        return $html;
    }

    private function generateRowWriteCell(
        string &$html,
        Worksheet $worksheet,
        string $coordinate,
        string $cellType,
        string $cellData,
        int $colSpan,
        int $rowSpan,
        array|string $cssClass,
        int $colNum,
        int $sheetIndex,
        int $row
    ): void {
                $htmlx = $this->writeImageInCell($coordinate);
                $htmlx .= $this->generateRowIncludeCharts($worksheet, $coordinate);
                $html .= '            <' . $cellType;
        if (!$this->useInlineCss && !$this->isPdf && is_string($cssClass)) {
            $html .= ' class="' . $cssClass . '"';
            if ($htmlx) {
                $html .= " style='position: relative;'";
            }
        } else {
                                                if ($this->useInlineCss) {
                $xcssClass = is_array($cssClass) ? $cssClass : [];
            } else {
                if (is_string($cssClass)) {
                    $html .= ' class="' . $cssClass . '"';
                }
                $xcssClass = [];
            }
            $width = 0;
            $i = $colNum - 1;
            $e = $colNum + $colSpan - 1;
            while ($i++ < $e) {
                if (isset($this->columnWidths[$sheetIndex][$i])) {
                    $width += $this->columnWidths[$sheetIndex][$i];
                }
            }
            $xcssClass['width'] = (string) $width . 'pt';
                                    if (isset($this->cssStyles['table.sheet' . $sheetIndex . ' tr.row' . $row]['height'])) {
                $height = $this->cssStyles['table.sheet' . $sheetIndex . ' tr.row' . $row]['height'];
                $xcssClass['height'] = $height;
            }
            
            if ($htmlx) {
                $xcssClass['position'] = 'relative';
            }
            $html .= ' style="' . $this->assembleCSS($xcssClass) . '"';
        }
        $html = $this->generateRowSpans($html, $rowSpan, $colSpan);

        $html .= '>';
        $html .= $htmlx;

        $html .= $this->writeComment($worksheet, $coordinate);

                $html .= $cellData;

                $html .= '</' . $cellType . '>' . PHP_EOL;
    }

        private function generateRow(Worksheet $worksheet, array $values, int $row, string $cellType): string
    {
                $sheetIndex = $worksheet->getParentOrThrow()->getIndex($worksheet);
        $html = $this->generateRowStart($worksheet, $sheetIndex, $row);

                $colNum = 0;
        foreach ($values as $cellAddress) {
            [$cell, $cssClass, $coordinate] = $this->generateRowCellCss($worksheet, $cellAddress, $row, $colNum);

                        $cellData = $this->generateRowCellData($worksheet, $cell, $cssClass);

                        if ($worksheet->hyperlinkExists($coordinate) && !$worksheet->getHyperlink($coordinate)->isInternal()) {
                $cellData = '<a href="' . htmlspecialchars($worksheet->getHyperlink($coordinate)->getUrl(), Settings::htmlEntityFlags()) . '" title="' . htmlspecialchars($worksheet->getHyperlink($coordinate)->getTooltip(), Settings::htmlEntityFlags()) . '">' . $cellData . '</a>';
            }

                        $writeCell = !(isset($this->isSpannedCell[$worksheet->getParentOrThrow()->getIndex($worksheet)][$row + 1][$colNum])
                && $this->isSpannedCell[$worksheet->getParentOrThrow()->getIndex($worksheet)][$row + 1][$colNum]);

                        $colSpan = 1;
            $rowSpan = 1;
            if (isset($this->isBaseCell[$worksheet->getParentOrThrow()->getIndex($worksheet)][$row + 1][$colNum])) {
                $spans = $this->isBaseCell[$worksheet->getParentOrThrow()->getIndex($worksheet)][$row + 1][$colNum];
                $rowSpan = $spans['rowspan'];
                $colSpan = $spans['colspan'];

                                                $endCellCoord = Coordinate::stringFromColumnIndex($colNum + $colSpan) . ($row + $rowSpan);
                if (!$this->useInlineCss) {
                    $cssClass .= ' style' . $worksheet->getCell($endCellCoord)->getXfIndex();
                }
            }

                        if ($writeCell) {
                $this->generateRowWriteCell($html, $worksheet, $coordinate, $cellType, $cellData, $colSpan, $rowSpan, $cssClass, $colNum, $sheetIndex, $row);
            }

                        ++$colNum;
        }

                $html .= '          </tr>' . PHP_EOL;

                return $html;
    }

        private function assembleCSS(array $values = []): string
    {
        $pairs = [];
        foreach ($values as $property => $value) {
            $pairs[] = $property . ':' . $value;
        }
        $string = implode('; ', $pairs);

        return $string;
    }

        public function getImagesRoot(): string
    {
        return $this->imagesRoot;
    }

        public function setImagesRoot(string $imagesRoot): static
    {
        $this->imagesRoot = $imagesRoot;

        return $this;
    }

        public function getEmbedImages(): bool
    {
        return $this->embedImages;
    }

        public function setEmbedImages(bool $embedImages): static
    {
        $this->embedImages = $embedImages;

        return $this;
    }

        public function getUseInlineCss(): bool
    {
        return $this->useInlineCss;
    }

        public function setUseInlineCss(bool $useInlineCss): static
    {
        $this->useInlineCss = $useInlineCss;

        return $this;
    }

        public function formatColor(string $value, string $format): string
    {
                $color = null;         $matches = [];

        $color_regex = '/^\\[[a-zA-Z]+\\]/';
        if (preg_match($color_regex, $format, $matches)) {
            $color = str_replace(['[', ']'], '', $matches[0]);
            $color = strtolower($color);
        }

                $result = htmlspecialchars($value, Settings::htmlEntityFlags());

                if ($color !== null) {
            $result = '<span style="color:' . $color . '">' . $result . '</span>';
        }

        return $result;
    }

        private function calculateSpans(): void
    {
        if ($this->spansAreCalculated) {
            return;
        }
                                $sheetIndexes = $this->sheetIndex !== null
            ? [$this->sheetIndex] : range(0, $this->spreadsheet->getSheetCount() - 1);

        foreach ($sheetIndexes as $sheetIndex) {
            $sheet = $this->spreadsheet->getSheet($sheetIndex);

            $candidateSpannedRow = [];

                        foreach ($sheet->getMergeCells() as $cells) {
                [$cells] = Coordinate::splitRange($cells);
                $first = $cells[0];
                $last = $cells[1];

                [$fc, $fr] = Coordinate::indexesFromString($first);
                $fc = $fc - 1;

                [$lc, $lr] = Coordinate::indexesFromString($last);
                $lc = $lc - 1;

                                $r = $fr - 1;
                while ($r++ < $lr) {
                                        $candidateSpannedRow[$r] = $r;

                    $c = $fc - 1;
                    while ($c++ < $lc) {
                        if (!($c == $fc && $r == $fr)) {
                                                        $this->isSpannedCell[$sheetIndex][$r][$c] = [
                                'baseCell' => [$fr, $fc],
                            ];
                        } else {
                                                        $this->isBaseCell[$sheetIndex][$r][$c] = [
                                'xlrowspan' => $lr - $fr + 1,                                 'rowspan' => $lr - $fr + 1,                                 'xlcolspan' => $lc - $fc + 1,                                 'colspan' => $lc - $fc + 1,                             ];
                        }
                    }
                }
            }

            $this->calculateSpansOmitRows($sheet, $sheetIndex, $candidateSpannedRow);

                    }

                $this->spansAreCalculated = true;
    }

    private function calculateSpansOmitRows(Worksheet $sheet, int $sheetIndex, array $candidateSpannedRow): void
    {
                        $countColumns = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        foreach ($candidateSpannedRow as $rowIndex) {
            if (isset($this->isSpannedCell[$sheetIndex][$rowIndex])) {
                if (count($this->isSpannedCell[$sheetIndex][$rowIndex]) == $countColumns) {
                    $this->isSpannedRow[$sheetIndex][$rowIndex] = $rowIndex;
                }
            }
        }

                if (isset($this->isSpannedRow[$sheetIndex])) {
            foreach ($this->isSpannedRow[$sheetIndex] as $rowIndex) {
                $adjustedBaseCells = [];
                $c = -1;
                $e = $countColumns - 1;
                while ($c++ < $e) {
                    $baseCell = $this->isSpannedCell[$sheetIndex][$rowIndex][$c]['baseCell'];

                    if (!in_array($baseCell, $adjustedBaseCells, true)) {
                                                --$this->isBaseCell[$sheetIndex][$baseCell[0]][$baseCell[1]]['rowspan'];
                        $adjustedBaseCells[] = $baseCell;
                    }
                }
            }
        }
    }

        private function writeComment(Worksheet $worksheet, string $coordinate): string
    {
        $result = '';
        if (!$this->isPdf && isset($worksheet->getComments()[$coordinate])) {
            $sanitizer = new AntiXSS();
            $sanitizedString = $sanitizer->xss_clean($worksheet->getComment($coordinate)->getText()->getPlainText());
            if ($sanitizedString !== '') {
                $result .= '<a class="comment-indicator"></a>';
                $result .= '<div class="comment">' . nl2br($sanitizedString) . '</div>';
                $result .= PHP_EOL;
            }
        }

        return $result;
    }

    public function getOrientation(): ?string
    {
                return $this->isPdf ? PageSetup::ORIENTATION_PORTRAIT : null;
    }

        private function generatePageDeclarations(bool $generateSurroundingHTML): string
    {
                $this->calculateSpans();

                $sheets = [];
        if ($this->sheetIndex === null) {
            $sheets = $this->spreadsheet->getAllSheets();
        } else {
            $sheets[] = $this->spreadsheet->getSheet($this->sheetIndex);
        }

                $htmlPage = $generateSurroundingHTML ? ('<style type="text/css">' . PHP_EOL) : '';

                $sheetId = 0;
        foreach ($sheets as $worksheet) {
            $htmlPage .= "@page page$sheetId { ";
            $left = StringHelper::formatNumber($worksheet->getPageMargins()->getLeft()) . 'in; ';
            $htmlPage .= 'margin-left: ' . $left;
            $right = StringHelper::FormatNumber($worksheet->getPageMargins()->getRight()) . 'in; ';
            $htmlPage .= 'margin-right: ' . $right;
            $top = StringHelper::FormatNumber($worksheet->getPageMargins()->getTop()) . 'in; ';
            $htmlPage .= 'margin-top: ' . $top;
            $bottom = StringHelper::FormatNumber($worksheet->getPageMargins()->getBottom()) . 'in; ';
            $htmlPage .= 'margin-bottom: ' . $bottom;
            $orientation = $this->getOrientation() ?? $worksheet->getPageSetup()->getOrientation();
            if ($orientation === PageSetup::ORIENTATION_LANDSCAPE) {
                $htmlPage .= 'size: landscape; ';
            } elseif ($orientation === PageSetup::ORIENTATION_PORTRAIT) {
                $htmlPage .= 'size: portrait; ';
            }
            $htmlPage .= '}' . PHP_EOL;
            ++$sheetId;
        }
        $htmlPage .= implode(PHP_EOL, [
            '.navigation {page-break-after: always;}',
            '.scrpgbrk, div + div {page-break-before: always;}',
            '@media screen {',
            '  .gridlines td {border: 1px solid black;}',
            '  .gridlines th {border: 1px solid black;}',
            '  body>div {margin-top: 5px;}',
            '  body>div:first-child {margin-top: 0;}',
            '  .scrpgbrk {margin-top: 1px;}',
            '}',
            '@media print {',
            '  .gridlinesp td {border: 1px solid black;}',
            '  .gridlinesp th {border: 1px solid black;}',
            '  .navigation {display: none;}',
            '}',
            '',
        ]);
        $htmlPage .= $generateSurroundingHTML ? ('</style>' . PHP_EOL) : '';

        return $htmlPage;
    }
}
