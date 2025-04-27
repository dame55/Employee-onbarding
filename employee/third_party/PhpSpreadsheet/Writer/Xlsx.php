<?php

namespace PhpOffice\PhpSpreadsheet\Writer;

use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Calculation\Functions;
use PhpOffice\PhpSpreadsheet\HashTable;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Borders;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\BaseDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing as WorksheetDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Writer\Exception as WriterException;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\Chart;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\Comments;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\ContentTypes;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\DocProps;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\Rels;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\RelsRibbon;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\RelsVBA;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\StringTable;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\Style;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\Table;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\Theme;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\Workbook;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\Worksheet;
use ZipArchive;
use ZipStream\Exception\OverflowException;
use ZipStream\ZipStream;

class Xlsx extends BaseWriter
{
        private bool $office2003compatibility = false;

        private Spreadsheet $spreadSheet;

        private array $stringTable = [];

        private HashTable $stylesConditionalHashTable;

        private HashTable $styleHashTable;

        private HashTable $fillHashTable;

        private HashTable $fontHashTable;

        private HashTable $bordersHashTable;

        private HashTable $numFmtHashTable;

        private HashTable $drawingHashTable;

        private ZipStream $zip;

    private Chart $writerPartChart;

    private Comments $writerPartComments;

    private ContentTypes $writerPartContentTypes;

    private DocProps $writerPartDocProps;

    private Drawing $writerPartDrawing;

    private Rels $writerPartRels;

    private RelsRibbon $writerPartRelsRibbon;

    private RelsVBA $writerPartRelsVBA;

    private StringTable $writerPartStringTable;

    private Style $writerPartStyle;

    private Theme $writerPartTheme;

    private Table $writerPartTable;

    private Workbook $writerPartWorkbook;

    private Worksheet $writerPartWorksheet;

        public function __construct(Spreadsheet $spreadsheet)
    {
                $this->setSpreadsheet($spreadsheet);

        $this->writerPartChart = new Chart($this);
        $this->writerPartComments = new Comments($this);
        $this->writerPartContentTypes = new ContentTypes($this);
        $this->writerPartDocProps = new DocProps($this);
        $this->writerPartDrawing = new Drawing($this);
        $this->writerPartRels = new Rels($this);
        $this->writerPartRelsRibbon = new RelsRibbon($this);
        $this->writerPartRelsVBA = new RelsVBA($this);
        $this->writerPartStringTable = new StringTable($this);
        $this->writerPartStyle = new Style($this);
        $this->writerPartTheme = new Theme($this);
        $this->writerPartTable = new Table($this);
        $this->writerPartWorkbook = new Workbook($this);
        $this->writerPartWorksheet = new Worksheet($this);

                        $this->bordersHashTable = new HashTable();
                $this->drawingHashTable = new HashTable();
                $this->fillHashTable = new HashTable();
                $this->fontHashTable = new HashTable();
                $this->numFmtHashTable = new HashTable();
                $this->styleHashTable = new HashTable();
                $this->stylesConditionalHashTable = new HashTable();
    }

    public function getWriterPartChart(): Chart
    {
        return $this->writerPartChart;
    }

    public function getWriterPartComments(): Comments
    {
        return $this->writerPartComments;
    }

    public function getWriterPartContentTypes(): ContentTypes
    {
        return $this->writerPartContentTypes;
    }

    public function getWriterPartDocProps(): DocProps
    {
        return $this->writerPartDocProps;
    }

    public function getWriterPartDrawing(): Drawing
    {
        return $this->writerPartDrawing;
    }

    public function getWriterPartRels(): Rels
    {
        return $this->writerPartRels;
    }

    public function getWriterPartRelsRibbon(): RelsRibbon
    {
        return $this->writerPartRelsRibbon;
    }

    public function getWriterPartRelsVBA(): RelsVBA
    {
        return $this->writerPartRelsVBA;
    }

    public function getWriterPartStringTable(): StringTable
    {
        return $this->writerPartStringTable;
    }

    public function getWriterPartStyle(): Style
    {
        return $this->writerPartStyle;
    }

    public function getWriterPartTheme(): Theme
    {
        return $this->writerPartTheme;
    }

    public function getWriterPartTable(): Table
    {
        return $this->writerPartTable;
    }

    public function getWriterPartWorkbook(): Workbook
    {
        return $this->writerPartWorkbook;
    }

    public function getWriterPartWorksheet(): Worksheet
    {
        return $this->writerPartWorksheet;
    }

        public function save($filename, int $flags = 0): void
    {
        $this->processFlags($flags);

                $this->pathNames = [];
        $this->spreadSheet->garbageCollect();

        $saveDebugLog = Calculation::getInstance($this->spreadSheet)->getDebugLog()->getWriteDebugLog();
        Calculation::getInstance($this->spreadSheet)->getDebugLog()->setWriteDebugLog(false);
        $saveDateReturnType = Functions::getReturnDateType();
        Functions::setReturnDateType(Functions::RETURNDATE_EXCEL);

                $this->stringTable = [];
        for ($i = 0; $i < $this->spreadSheet->getSheetCount(); ++$i) {
            $this->stringTable = $this->getWriterPartStringTable()->createStringTable($this->spreadSheet->getSheet($i), $this->stringTable);
        }

                $this->styleHashTable->addFromSource($this->getWriterPartStyle()->allStyles($this->spreadSheet));
        $this->stylesConditionalHashTable->addFromSource($this->getWriterPartStyle()->allConditionalStyles($this->spreadSheet));
        $this->fillHashTable->addFromSource($this->getWriterPartStyle()->allFills($this->spreadSheet));
        $this->fontHashTable->addFromSource($this->getWriterPartStyle()->allFonts($this->spreadSheet));
        $this->bordersHashTable->addFromSource($this->getWriterPartStyle()->allBorders($this->spreadSheet));
        $this->numFmtHashTable->addFromSource($this->getWriterPartStyle()->allNumberFormats($this->spreadSheet));

                $this->drawingHashTable->addFromSource($this->getWriterPartDrawing()->allDrawings($this->spreadSheet));

        $zipContent = [];
                $zipContent['[Content_Types].xml'] = $this->getWriterPartContentTypes()->writeContentTypes($this->spreadSheet, $this->includeCharts);

                if ($this->spreadSheet->hasMacros()) {
            $macrosCode = $this->spreadSheet->getMacrosCode();
            if ($macrosCode !== null) {
                                $zipContent['xl/vbaProject.bin'] = $macrosCode;                 if ($this->spreadSheet->hasMacrosCertificate()) {
                                                            $zipContent['xl/vbaProjectSignature.bin'] = $this->spreadSheet->getMacrosCertificate();
                    $zipContent['xl/_rels/vbaProject.bin.rels'] = $this->getWriterPartRelsVBA()->writeVBARelationships();
                }
            }
        }
                if ($this->spreadSheet->hasRibbon()) {
            $tmpRibbonTarget = $this->spreadSheet->getRibbonXMLData('target');
            $tmpRibbonTarget = is_string($tmpRibbonTarget) ? $tmpRibbonTarget : '';
            $zipContent[$tmpRibbonTarget] = $this->spreadSheet->getRibbonXMLData('data');
            if ($this->spreadSheet->hasRibbonBinObjects()) {
                $tmpRootPath = dirname($tmpRibbonTarget) . '/';
                $ribbonBinObjects = $this->spreadSheet->getRibbonBinObjects('data');                 if (is_array($ribbonBinObjects)) {
                    foreach ($ribbonBinObjects as $aPath => $aContent) {
                        $zipContent[$tmpRootPath . $aPath] = $aContent;
                    }
                }
                                $zipContent[$tmpRootPath . '_rels/' . basename($tmpRibbonTarget) . '.rels'] = $this->getWriterPartRelsRibbon()->writeRibbonRelationships($this->spreadSheet);
            }
        }

                $zipContent['_rels/.rels'] = $this->getWriterPartRels()->writeRelationships($this->spreadSheet);
        $zipContent['xl/_rels/workbook.xml.rels'] = $this->getWriterPartRels()->writeWorkbookRelationships($this->spreadSheet);

                $zipContent['docProps/app.xml'] = $this->getWriterPartDocProps()->writeDocPropsApp($this->spreadSheet);
        $zipContent['docProps/core.xml'] = $this->getWriterPartDocProps()->writeDocPropsCore($this->spreadSheet);
        $customPropertiesPart = $this->getWriterPartDocProps()->writeDocPropsCustom($this->spreadSheet);
        if ($customPropertiesPart !== null) {
            $zipContent['docProps/custom.xml'] = $customPropertiesPart;
        }

                $zipContent['xl/theme/theme1.xml'] = $this->getWriterPartTheme()->writeTheme($this->spreadSheet);

                $zipContent['xl/sharedStrings.xml'] = $this->getWriterPartStringTable()->writeStringTable($this->stringTable);

                $zipContent['xl/styles.xml'] = $this->getWriterPartStyle()->writeStyles($this->spreadSheet);

                $zipContent['xl/workbook.xml'] = $this->getWriterPartWorkbook()->writeWorkbook($this->spreadSheet, $this->preCalculateFormulas);

        $chartCount = 0;
                for ($i = 0; $i < $this->spreadSheet->getSheetCount(); ++$i) {
            $zipContent['xl/worksheets/sheet' . ($i + 1) . '.xml'] = $this->getWriterPartWorksheet()->writeWorksheet($this->spreadSheet->getSheet($i), $this->stringTable, $this->includeCharts);
            if ($this->includeCharts) {
                $charts = $this->spreadSheet->getSheet($i)->getChartCollection();
                if (count($charts) > 0) {
                    foreach ($charts as $chart) {
                        $zipContent['xl/charts/chart' . ($chartCount + 1) . '.xml'] = $this->getWriterPartChart()->writeChart($chart, $this->preCalculateFormulas);
                        ++$chartCount;
                    }
                }
            }
        }

        $chartRef1 = 0;
        $tableRef1 = 1;
                for ($i = 0; $i < $this->spreadSheet->getSheetCount(); ++$i) {
                        $zipContent['xl/worksheets/_rels/sheet' . ($i + 1) . '.xml.rels'] = $this->getWriterPartRels()->writeWorksheetRelationships($this->spreadSheet->getSheet($i), ($i + 1), $this->includeCharts, $tableRef1, $zipContent);

                        $sheetCodeName = $this->spreadSheet->getSheet($i)->getCodeName();
            $unparsedLoadedData = $this->spreadSheet->getUnparsedLoadedData();
            if (isset($unparsedLoadedData['sheets'][$sheetCodeName]['ctrlProps'])) {
                foreach ($unparsedLoadedData['sheets'][$sheetCodeName]['ctrlProps'] as $ctrlProp) {
                    $zipContent[$ctrlProp['filePath']] = $ctrlProp['content'];
                }
            }
            if (isset($unparsedLoadedData['sheets'][$sheetCodeName]['printerSettings'])) {
                foreach ($unparsedLoadedData['sheets'][$sheetCodeName]['printerSettings'] as $ctrlProp) {
                    $zipContent[$ctrlProp['filePath']] = $ctrlProp['content'];
                }
            }

            $drawings = $this->spreadSheet->getSheet($i)->getDrawingCollection();
            $drawingCount = count($drawings);
            if ($this->includeCharts) {
                $chartCount = $this->spreadSheet->getSheet($i)->getChartCount();
            }

                        if (($drawingCount > 0) || ($chartCount > 0)) {
                                $zipContent['xl/drawings/_rels/drawing' . ($i + 1) . '.xml.rels'] = $this->getWriterPartRels()->writeDrawingRelationships($this->spreadSheet->getSheet($i), $chartRef1, $this->includeCharts);

                                $zipContent['xl/drawings/drawing' . ($i + 1) . '.xml'] = $this->getWriterPartDrawing()->writeDrawings($this->spreadSheet->getSheet($i), $this->includeCharts);
            } elseif (isset($unparsedLoadedData['sheets'][$sheetCodeName]['drawingAlternateContents'])) {
                                $zipContent['xl/drawings/drawing' . ($i + 1) . '.xml'] = $this->getWriterPartDrawing()->writeDrawings($this->spreadSheet->getSheet($i), $this->includeCharts);
            }

                        if (isset($unparsedLoadedData['sheets'][$sheetCodeName]['Drawings']) && !isset($zipContent['xl/drawings/drawing' . ($i + 1) . '.xml'])) {
                foreach ($unparsedLoadedData['sheets'][$sheetCodeName]['Drawings'] as $relId => $drawingXml) {
                    $drawingFile = array_search($relId, $unparsedLoadedData['sheets'][$sheetCodeName]['drawingOriginalIds']);
                    if ($drawingFile !== false) {
                                                                        $zipContent['xl/drawings/drawing' . ($i + 1) . '.xml'] = $drawingXml;
                    }
                }
            }
            if (isset($unparsedLoadedData['sheets'][$sheetCodeName]['drawingOriginalIds']) && !isset($zipContent['xl/drawings/drawing' . ($i + 1) . '.xml'])) {
                $zipContent['xl/drawings/drawing' . ($i + 1) . '.xml'] = '<xml></xml>';
            }

                        $legacy = $unparsedLoadedData['sheets'][$this->spreadSheet->getSheet($i)->getCodeName()]['legacyDrawing'] ?? null;
            if (count($this->spreadSheet->getSheet($i)->getComments()) > 0 || $legacy !== null) {
                                $zipContent['xl/drawings/_rels/vmlDrawing' . ($i + 1) . '.vml.rels'] = $this->getWriterPartRels()->writeVMLDrawingRelationships($this->spreadSheet->getSheet($i));

                                $zipContent['xl/drawings/vmlDrawing' . ($i + 1) . '.vml'] = $legacy ?? $this->getWriterPartComments()->writeVMLComments($this->spreadSheet->getSheet($i));
            }

                        if (count($this->spreadSheet->getSheet($i)->getComments()) > 0) {
                $zipContent['xl/comments' . ($i + 1) . '.xml'] = $this->getWriterPartComments()->writeComments($this->spreadSheet->getSheet($i));

                                foreach ($this->spreadSheet->getSheet($i)->getComments() as $comment) {
                    if ($comment->hasBackgroundImage()) {
                        $image = $comment->getBackgroundImage();
                        $zipContent['xl/media/' . $image->getMediaFilename()] = $this->processDrawing($image);
                    }
                }
            }

                        if (isset($unparsedLoadedData['sheets'][$sheetCodeName]['vmlDrawings'])) {
                foreach ($unparsedLoadedData['sheets'][$sheetCodeName]['vmlDrawings'] as $vmlDrawing) {
                    if (!isset($zipContent[$vmlDrawing['filePath']])) {
                        $zipContent[$vmlDrawing['filePath']] = $vmlDrawing['content'];
                    }
                }
            }

                        if (count($this->spreadSheet->getSheet($i)->getHeaderFooter()->getImages()) > 0) {
                                $zipContent['xl/drawings/vmlDrawingHF' . ($i + 1) . '.vml'] = $this->getWriterPartDrawing()->writeVMLHeaderFooterImages($this->spreadSheet->getSheet($i));

                                $zipContent['xl/drawings/_rels/vmlDrawingHF' . ($i + 1) . '.vml.rels'] = $this->getWriterPartRels()->writeHeaderFooterDrawingRelationships($this->spreadSheet->getSheet($i));

                                foreach ($this->spreadSheet->getSheet($i)->getHeaderFooter()->getImages() as $image) {
                    $zipContent['xl/media/' . $image->getIndexedFilename()] = file_get_contents($image->getPath());
                }
            }

                        $tables = $this->spreadSheet->getSheet($i)->getTableCollection();
            foreach ($tables as $table) {
                $zipContent['xl/tables/table' . $tableRef1 . '.xml'] = $this->getWriterPartTable()->writeTable($table, $tableRef1++);
            }
        }

                for ($i = 0; $i < $this->getDrawingHashTable()->count(); ++$i) {
            if ($this->getDrawingHashTable()->getByIndex($i) instanceof WorksheetDrawing) {
                $imageContents = null;
                $imagePath = $this->getDrawingHashTable()->getByIndex($i)->getPath();
                if (str_contains($imagePath, 'zip:                    $imagePath = substr($imagePath, 6);
                    $imagePathSplitted = explode('#', $imagePath);

                    $imageZip = new ZipArchive();
                    $imageZip->open($imagePathSplitted[0]);
                    $imageContents = $imageZip->getFromName($imagePathSplitted[1]);
                    $imageZip->close();
                    unset($imageZip);
                } else {
                    $imageContents = file_get_contents($imagePath);
                }

                $zipContent['xl/media/' . $this->getDrawingHashTable()->getByIndex($i)->getIndexedFilename()] = $imageContents;
            } elseif ($this->getDrawingHashTable()->getByIndex($i) instanceof MemoryDrawing) {
                ob_start();
                                $callable = $this->getDrawingHashTable()->getByIndex($i)->getRenderingFunction();
                call_user_func(
                    $callable,
                    $this->getDrawingHashTable()->getByIndex($i)->getImageResource()
                );
                $imageContents = ob_get_contents();
                ob_end_clean();

                $zipContent['xl/media/' . $this->getDrawingHashTable()->getByIndex($i)->getIndexedFilename()] = $imageContents;
            }
        }

        Functions::setReturnDateType($saveDateReturnType);
        Calculation::getInstance($this->spreadSheet)->getDebugLog()->setWriteDebugLog($saveDebugLog);

        $this->openFileHandle($filename);

        $this->zip = ZipStream0::newZipStream($this->fileHandle);

        $this->addZipFiles($zipContent);

                try {
            $this->zip->finish();
        } catch (OverflowException) {
            throw new WriterException('Could not close resource.');
        }

        $this->maybeCloseFileHandle();
    }

        public function getSpreadsheet(): Spreadsheet
    {
        return $this->spreadSheet;
    }

        public function setSpreadsheet(Spreadsheet $spreadsheet): static
    {
        $this->spreadSheet = $spreadsheet;

        return $this;
    }

        public function getStringTable(): array
    {
        return $this->stringTable;
    }

        public function getStyleHashTable(): HashTable
    {
        return $this->styleHashTable;
    }

        public function getStylesConditionalHashTable(): HashTable
    {
        return $this->stylesConditionalHashTable;
    }

        public function getFillHashTable(): HashTable
    {
        return $this->fillHashTable;
    }

        public function getFontHashTable(): HashTable
    {
        return $this->fontHashTable;
    }

        public function getBordersHashTable(): HashTable
    {
        return $this->bordersHashTable;
    }

        public function getNumFmtHashTable(): HashTable
    {
        return $this->numFmtHashTable;
    }

        public function getDrawingHashTable(): HashTable
    {
        return $this->drawingHashTable;
    }

        public function getOffice2003Compatibility(): bool
    {
        return $this->office2003compatibility;
    }

        public function setOffice2003Compatibility(bool $office2003compatibility): static
    {
        $this->office2003compatibility = $office2003compatibility;

        return $this;
    }

    private array $pathNames = [];

    private function addZipFile(string $path, string $content): void
    {
        if (!in_array($path, $this->pathNames)) {
            $this->pathNames[] = $path;
            $this->zip->addFile($path, $content);
        }
    }

    private function addZipFiles(array $zipContent): void
    {
        foreach ($zipContent as $path => $content) {
            $this->addZipFile($path, $content);
        }
    }

    private function processDrawing(WorksheetDrawing $drawing): string|null|false
    {
        $data = null;
        $filename = $drawing->getPath();
        $imageData = getimagesize($filename);

        if (!empty($imageData)) {
            switch ($imageData[2]) {
                case 1:                     $image = imagecreatefromgif($filename);
                    if ($image !== false) {
                        ob_start();
                        imagepng($image);
                        $data = ob_get_contents();
                        ob_end_clean();
                    }

                    break;

                case 2:                     $data = file_get_contents($filename);

                    break;

                case 3:                     $data = file_get_contents($filename);

                    break;

                case 6:                     $image = imagecreatefrombmp($filename);
                    if ($image !== false) {
                        ob_start();
                        imagepng($image);
                        $data = ob_get_contents();
                        ob_end_clean();
                    }

                    break;
            }
        }

        return $data;
    }
}
