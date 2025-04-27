<?php

namespace PhpOffice\PhpSpreadsheet\Reader;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Reader\Xls\ConditionalFormatting;
use PhpOffice\PhpSpreadsheet\Reader\Xls\Style\CellFont;
use PhpOffice\PhpSpreadsheet\Reader\Xls\Style\FillPattern;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\CodePage;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Shared\Escher;
use PhpOffice\PhpSpreadsheet\Shared\Escher\DgContainer\SpgrContainer\SpContainer;
use PhpOffice\PhpSpreadsheet\Shared\Escher\DggContainer\BstoreContainer\BSE;
use PhpOffice\PhpSpreadsheet\Shared\File;
use PhpOffice\PhpSpreadsheet\Shared\OLE;
use PhpOffice\PhpSpreadsheet\Shared\OLERead;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use PhpOffice\PhpSpreadsheet\Shared\Xls as SharedXls;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Borders;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\SheetView;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


class Xls extends BaseReader
{
        const XLS_BIFF8 = 0x0600;
    const XLS_BIFF7 = 0x0500;
    const XLS_WORKBOOKGLOBALS = 0x0005;
    const XLS_WORKSHEET = 0x0010;

        const XLS_TYPE_FORMULA = 0x0006;
    const XLS_TYPE_EOF = 0x000A;
    const XLS_TYPE_PROTECT = 0x0012;
    const XLS_TYPE_OBJECTPROTECT = 0x0063;
    const XLS_TYPE_SCENPROTECT = 0x00DD;
    const XLS_TYPE_PASSWORD = 0x0013;
    const XLS_TYPE_HEADER = 0x0014;
    const XLS_TYPE_FOOTER = 0x0015;
    const XLS_TYPE_EXTERNSHEET = 0x0017;
    const XLS_TYPE_DEFINEDNAME = 0x0018;
    const XLS_TYPE_VERTICALPAGEBREAKS = 0x001A;
    const XLS_TYPE_HORIZONTALPAGEBREAKS = 0x001B;
    const XLS_TYPE_NOTE = 0x001C;
    const XLS_TYPE_SELECTION = 0x001D;
    const XLS_TYPE_DATEMODE = 0x0022;
    const XLS_TYPE_EXTERNNAME = 0x0023;
    const XLS_TYPE_LEFTMARGIN = 0x0026;
    const XLS_TYPE_RIGHTMARGIN = 0x0027;
    const XLS_TYPE_TOPMARGIN = 0x0028;
    const XLS_TYPE_BOTTOMMARGIN = 0x0029;
    const XLS_TYPE_PRINTGRIDLINES = 0x002B;
    const XLS_TYPE_FILEPASS = 0x002F;
    const XLS_TYPE_FONT = 0x0031;
    const XLS_TYPE_CONTINUE = 0x003C;
    const XLS_TYPE_PANE = 0x0041;
    const XLS_TYPE_CODEPAGE = 0x0042;
    const XLS_TYPE_DEFCOLWIDTH = 0x0055;
    const XLS_TYPE_OBJ = 0x005D;
    const XLS_TYPE_COLINFO = 0x007D;
    const XLS_TYPE_IMDATA = 0x007F;
    const XLS_TYPE_SHEETPR = 0x0081;
    const XLS_TYPE_HCENTER = 0x0083;
    const XLS_TYPE_VCENTER = 0x0084;
    const XLS_TYPE_SHEET = 0x0085;
    const XLS_TYPE_PALETTE = 0x0092;
    const XLS_TYPE_SCL = 0x00A0;
    const XLS_TYPE_PAGESETUP = 0x00A1;
    const XLS_TYPE_MULRK = 0x00BD;
    const XLS_TYPE_MULBLANK = 0x00BE;
    const XLS_TYPE_DBCELL = 0x00D7;
    const XLS_TYPE_XF = 0x00E0;
    const XLS_TYPE_MERGEDCELLS = 0x00E5;
    const XLS_TYPE_MSODRAWINGGROUP = 0x00EB;
    const XLS_TYPE_MSODRAWING = 0x00EC;
    const XLS_TYPE_SST = 0x00FC;
    const XLS_TYPE_LABELSST = 0x00FD;
    const XLS_TYPE_EXTSST = 0x00FF;
    const XLS_TYPE_EXTERNALBOOK = 0x01AE;
    const XLS_TYPE_DATAVALIDATIONS = 0x01B2;
    const XLS_TYPE_TXO = 0x01B6;
    const XLS_TYPE_HYPERLINK = 0x01B8;
    const XLS_TYPE_DATAVALIDATION = 0x01BE;
    const XLS_TYPE_DIMENSION = 0x0200;
    const XLS_TYPE_BLANK = 0x0201;
    const XLS_TYPE_NUMBER = 0x0203;
    const XLS_TYPE_LABEL = 0x0204;
    const XLS_TYPE_BOOLERR = 0x0205;
    const XLS_TYPE_STRING = 0x0207;
    const XLS_TYPE_ROW = 0x0208;
    const XLS_TYPE_INDEX = 0x020B;
    const XLS_TYPE_ARRAY = 0x0221;
    const XLS_TYPE_DEFAULTROWHEIGHT = 0x0225;
    const XLS_TYPE_WINDOW2 = 0x023E;
    const XLS_TYPE_RK = 0x027E;
    const XLS_TYPE_STYLE = 0x0293;
    const XLS_TYPE_FORMAT = 0x041E;
    const XLS_TYPE_SHAREDFMLA = 0x04BC;
    const XLS_TYPE_BOF = 0x0809;
    const XLS_TYPE_SHEETPROTECTION = 0x0867;
    const XLS_TYPE_RANGEPROTECTION = 0x0868;
    const XLS_TYPE_SHEETLAYOUT = 0x0862;
    const XLS_TYPE_XFEXT = 0x087D;
    const XLS_TYPE_PAGELAYOUTVIEW = 0x088B;
    const XLS_TYPE_CFHEADER = 0x01B0;
    const XLS_TYPE_CFRULE = 0x01B1;
    const XLS_TYPE_UNKNOWN = 0xFFFF;

        const MS_BIFF_CRYPTO_NONE = 0;
    const MS_BIFF_CRYPTO_XOR = 1;
    const MS_BIFF_CRYPTO_RC4 = 2;

        const REKEY_BLOCK = 0x400;

        private ?string $summaryInformation = null;

        private ?string $documentSummaryInformation = null;

        private string $data;

        private int $dataSize;

        private int $pos;

        private Spreadsheet $spreadsheet;

        private Worksheet $phpSheet;

        private int $version = 0;

        private string $codepage = '';

        private array $formats;

        private array $objFonts;

        private array $palette;

        private array $sheets;

        private array $externalBooks;

        private array $ref;

        private array $externalNames;

        private array $definedname;

        private array $sst;

        private bool $frozen;

        private bool $isFitToPages;

        private array $objs;

        private array $textObjects;

        private array $cellNotes;

        private string $drawingGroupData;

        private string $drawingData;

        private int $xfIndex;

        private array $mapCellXfIndex;

        private array $mapCellStyleXfIndex;

        private array $sharedFormulas;

        private array $sharedFormulaParts;

        private int $encryption = 0;

        private int $encryptionStartPos = 0;

        private ?Xls\RC4 $rc4Key = null;

        private int $rc4Pos = 0;

        private string $md5Ctxt; 
    private int $textObjRef;

    private string $baseCell;

    private bool $activeSheetSet = false;

        public function __construct()
    {
        parent::__construct();
    }

        public function canRead(string $filename): bool
    {
        if (File::testFileNoThrow($filename) === false) {
            return false;
        }

        try {
                        $ole = new OLERead();

                        $ole->read($filename);
            if ($ole->wrkbook === null) {
                throw new Exception('The filename ' . $filename . ' is not recognised as a Spreadsheet file');
            }

            return true;
        } catch (PhpSpreadsheetException) {
            return false;
        }
    }

    public function setCodepage(string $codepage): void
    {
        if (CodePage::validate($codepage) === false) {
            throw new PhpSpreadsheetException('Unknown codepage: ' . $codepage);
        }

        $this->codepage = $codepage;
    }

    public function getCodepage(): string
    {
        return $this->codepage;
    }

        public function listWorksheetNames(string $filename): array
    {
        File::assertFile($filename);

        $worksheetNames = [];

                $this->loadOLE($filename);

                $this->dataSize = strlen($this->data);

        $this->pos = 0;
        $this->sheets = [];

                while ($this->pos < $this->dataSize) {
            $code = self::getUInt2d($this->data, $this->pos);

            match ($code) {
                self::XLS_TYPE_BOF => $this->readBof(),
                self::XLS_TYPE_SHEET => $this->readSheet(),
                self::XLS_TYPE_EOF => $this->readDefault(),
                self::XLS_TYPE_CODEPAGE => $this->readCodepage(),
                default => $this->readDefault(),
            };

            if ($code === self::XLS_TYPE_EOF) {
                break;
            }
        }

        foreach ($this->sheets as $sheet) {
            if ($sheet['sheetType'] != 0x00) {
                                continue;
            }

            $worksheetNames[] = $sheet['name'];
        }

        return $worksheetNames;
    }

        public function listWorksheetInfo(string $filename): array
    {
        File::assertFile($filename);

        $worksheetInfo = [];

                $this->loadOLE($filename);

                $this->dataSize = strlen($this->data);

                $this->pos = 0;
        $this->sheets = [];

                while ($this->pos < $this->dataSize) {
            $code = self::getUInt2d($this->data, $this->pos);

            match ($code) {
                self::XLS_TYPE_BOF => $this->readBof(),
                self::XLS_TYPE_SHEET => $this->readSheet(),
                self::XLS_TYPE_EOF => $this->readDefault(),
                self::XLS_TYPE_CODEPAGE => $this->readCodepage(),
                default => $this->readDefault(),
            };

            if ($code === self::XLS_TYPE_EOF) {
                break;
            }
        }

                foreach ($this->sheets as $sheet) {
            if ($sheet['sheetType'] != 0x00) {
                                                                continue;
            }

            $tmpInfo = [];
            $tmpInfo['worksheetName'] = $sheet['name'];
            $tmpInfo['lastColumnLetter'] = 'A';
            $tmpInfo['lastColumnIndex'] = 0;
            $tmpInfo['totalRows'] = 0;
            $tmpInfo['totalColumns'] = 0;

            $this->pos = $sheet['offset'];

            while ($this->pos <= $this->dataSize - 4) {
                $code = self::getUInt2d($this->data, $this->pos);

                switch ($code) {
                    case self::XLS_TYPE_RK:
                    case self::XLS_TYPE_LABELSST:
                    case self::XLS_TYPE_NUMBER:
                    case self::XLS_TYPE_FORMULA:
                    case self::XLS_TYPE_BOOLERR:
                    case self::XLS_TYPE_LABEL:
                        $length = self::getUInt2d($this->data, $this->pos + 2);
                        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                                                $this->pos += 4 + $length;

                        $rowIndex = self::getUInt2d($recordData, 0) + 1;
                        $columnIndex = self::getUInt2d($recordData, 2);

                        $tmpInfo['totalRows'] = max($tmpInfo['totalRows'], $rowIndex);
                        $tmpInfo['lastColumnIndex'] = max($tmpInfo['lastColumnIndex'], $columnIndex);

                        break;
                    case self::XLS_TYPE_BOF:
                        $this->readBof();

                        break;
                    case self::XLS_TYPE_EOF:
                        $this->readDefault();

                        break 2;
                    default:
                        $this->readDefault();

                        break;
                }
            }

            $tmpInfo['lastColumnLetter'] = Coordinate::stringFromColumnIndex($tmpInfo['lastColumnIndex'] + 1);
            $tmpInfo['totalColumns'] = $tmpInfo['lastColumnIndex'] + 1;

            $worksheetInfo[] = $tmpInfo;
        }

        return $worksheetInfo;
    }

        protected function loadSpreadsheetFromFile(string $filename): Spreadsheet
    {
                $this->loadOLE($filename);

                $this->spreadsheet = new Spreadsheet();
        $this->spreadsheet->removeSheetByIndex(0);         if (!$this->readDataOnly) {
            $this->spreadsheet->removeCellStyleXfByIndex(0);             $this->spreadsheet->removeCellXfByIndex(0);         }

                $this->readSummaryInformation();

                $this->readDocumentSummaryInformation();

                $this->dataSize = strlen($this->data);

                $this->pos = 0;
        $this->codepage = $this->codepage ?: CodePage::DEFAULT_CODE_PAGE;
        $this->formats = [];
        $this->objFonts = [];
        $this->palette = [];
        $this->sheets = [];
        $this->externalBooks = [];
        $this->ref = [];
        $this->definedname = [];
        $this->sst = [];
        $this->drawingGroupData = '';
        $this->xfIndex = 0;
        $this->mapCellXfIndex = [];
        $this->mapCellStyleXfIndex = [];

                while ($this->pos < $this->dataSize) {
            $code = self::getUInt2d($this->data, $this->pos);

            match ($code) {
                self::XLS_TYPE_BOF => $this->readBof(),
                self::XLS_TYPE_FILEPASS => $this->readFilepass(),
                self::XLS_TYPE_CODEPAGE => $this->readCodepage(),
                self::XLS_TYPE_DATEMODE => $this->readDateMode(),
                self::XLS_TYPE_FONT => $this->readFont(),
                self::XLS_TYPE_FORMAT => $this->readFormat(),
                self::XLS_TYPE_XF => $this->readXf(),
                self::XLS_TYPE_XFEXT => $this->readXfExt(),
                self::XLS_TYPE_STYLE => $this->readStyle(),
                self::XLS_TYPE_PALETTE => $this->readPalette(),
                self::XLS_TYPE_SHEET => $this->readSheet(),
                self::XLS_TYPE_EXTERNALBOOK => $this->readExternalBook(),
                self::XLS_TYPE_EXTERNNAME => $this->readExternName(),
                self::XLS_TYPE_EXTERNSHEET => $this->readExternSheet(),
                self::XLS_TYPE_DEFINEDNAME => $this->readDefinedName(),
                self::XLS_TYPE_MSODRAWINGGROUP => $this->readMsoDrawingGroup(),
                self::XLS_TYPE_SST => $this->readSst(),
                self::XLS_TYPE_EOF => $this->readDefault(),
                default => $this->readDefault(),
            };

            if ($code === self::XLS_TYPE_EOF) {
                break;
            }
        }

                        if (!$this->readDataOnly) {
            foreach ($this->objFonts as $objFont) {
                if (isset($objFont->colorIndex)) {
                    $color = Xls\Color::map($objFont->colorIndex, $this->palette, $this->version);
                    $objFont->getColor()->setRGB($color['rgb']);
                }
            }

            foreach ($this->spreadsheet->getCellXfCollection() as $objStyle) {
                                $fill = $objStyle->getFill();

                if (isset($fill->startcolorIndex)) {
                    $startColor = Xls\Color::map($fill->startcolorIndex, $this->palette, $this->version);
                    $fill->getStartColor()->setRGB($startColor['rgb']);
                }
                if (isset($fill->endcolorIndex)) {
                    $endColor = Xls\Color::map($fill->endcolorIndex, $this->palette, $this->version);
                    $fill->getEndColor()->setRGB($endColor['rgb']);
                }

                                $top = $objStyle->getBorders()->getTop();
                $right = $objStyle->getBorders()->getRight();
                $bottom = $objStyle->getBorders()->getBottom();
                $left = $objStyle->getBorders()->getLeft();
                $diagonal = $objStyle->getBorders()->getDiagonal();

                if (isset($top->colorIndex)) {
                    $borderTopColor = Xls\Color::map($top->colorIndex, $this->palette, $this->version);
                    $top->getColor()->setRGB($borderTopColor['rgb']);
                }
                if (isset($right->colorIndex)) {
                    $borderRightColor = Xls\Color::map($right->colorIndex, $this->palette, $this->version);
                    $right->getColor()->setRGB($borderRightColor['rgb']);
                }
                if (isset($bottom->colorIndex)) {
                    $borderBottomColor = Xls\Color::map($bottom->colorIndex, $this->palette, $this->version);
                    $bottom->getColor()->setRGB($borderBottomColor['rgb']);
                }
                if (isset($left->colorIndex)) {
                    $borderLeftColor = Xls\Color::map($left->colorIndex, $this->palette, $this->version);
                    $left->getColor()->setRGB($borderLeftColor['rgb']);
                }
                if (isset($diagonal->colorIndex)) {
                    $borderDiagonalColor = Xls\Color::map($diagonal->colorIndex, $this->palette, $this->version);
                    $diagonal->getColor()->setRGB($borderDiagonalColor['rgb']);
                }
            }
        }

                $escherWorkbook = null;
        if (!$this->readDataOnly && $this->drawingGroupData) {
            $escher = new Escher();
            $reader = new Xls\Escher($escher);
            $escherWorkbook = $reader->load($this->drawingGroupData);
        }

                $this->activeSheetSet = false;
        foreach ($this->sheets as $sheet) {
            if ($sheet['sheetType'] != 0x00) {
                                continue;
            }

                        if (isset($this->loadSheetsOnly) && !in_array($sheet['name'], $this->loadSheetsOnly)) {
                continue;
            }

                        $this->phpSheet = $this->spreadsheet->createSheet();
                                                $this->phpSheet->setTitle($sheet['name'], false, false);
            $this->phpSheet->setSheetState($sheet['sheetState']);

            $this->pos = $sheet['offset'];

                        $this->isFitToPages = false;

                        $this->drawingData = '';

                        $this->objs = [];

                        $this->sharedFormulaParts = [];

                        $this->sharedFormulas = [];

                        $this->textObjects = [];

                        $this->cellNotes = [];
            $this->textObjRef = -1;

            while ($this->pos <= $this->dataSize - 4) {
                $code = self::getUInt2d($this->data, $this->pos);

                switch ($code) {
                    case self::XLS_TYPE_BOF:
                        $this->readBof();

                        break;
                    case self::XLS_TYPE_PRINTGRIDLINES:
                        $this->readPrintGridlines();

                        break;
                    case self::XLS_TYPE_DEFAULTROWHEIGHT:
                        $this->readDefaultRowHeight();

                        break;
                    case self::XLS_TYPE_SHEETPR:
                        $this->readSheetPr();

                        break;
                    case self::XLS_TYPE_HORIZONTALPAGEBREAKS:
                        $this->readHorizontalPageBreaks();

                        break;
                    case self::XLS_TYPE_VERTICALPAGEBREAKS:
                        $this->readVerticalPageBreaks();

                        break;
                    case self::XLS_TYPE_HEADER:
                        $this->readHeader();

                        break;
                    case self::XLS_TYPE_FOOTER:
                        $this->readFooter();

                        break;
                    case self::XLS_TYPE_HCENTER:
                        $this->readHcenter();

                        break;
                    case self::XLS_TYPE_VCENTER:
                        $this->readVcenter();

                        break;
                    case self::XLS_TYPE_LEFTMARGIN:
                        $this->readLeftMargin();

                        break;
                    case self::XLS_TYPE_RIGHTMARGIN:
                        $this->readRightMargin();

                        break;
                    case self::XLS_TYPE_TOPMARGIN:
                        $this->readTopMargin();

                        break;
                    case self::XLS_TYPE_BOTTOMMARGIN:
                        $this->readBottomMargin();

                        break;
                    case self::XLS_TYPE_PAGESETUP:
                        $this->readPageSetup();

                        break;
                    case self::XLS_TYPE_PROTECT:
                        $this->readProtect();

                        break;
                    case self::XLS_TYPE_SCENPROTECT:
                        $this->readScenProtect();

                        break;
                    case self::XLS_TYPE_OBJECTPROTECT:
                        $this->readObjectProtect();

                        break;
                    case self::XLS_TYPE_PASSWORD:
                        $this->readPassword();

                        break;
                    case self::XLS_TYPE_DEFCOLWIDTH:
                        $this->readDefColWidth();

                        break;
                    case self::XLS_TYPE_COLINFO:
                        $this->readColInfo();

                        break;
                    case self::XLS_TYPE_DIMENSION:
                        $this->readDefault();

                        break;
                    case self::XLS_TYPE_ROW:
                        $this->readRow();

                        break;
                    case self::XLS_TYPE_DBCELL:
                        $this->readDefault();

                        break;
                    case self::XLS_TYPE_RK:
                        $this->readRk();

                        break;
                    case self::XLS_TYPE_LABELSST:
                        $this->readLabelSst();

                        break;
                    case self::XLS_TYPE_MULRK:
                        $this->readMulRk();

                        break;
                    case self::XLS_TYPE_NUMBER:
                        $this->readNumber();

                        break;
                    case self::XLS_TYPE_FORMULA:
                        $this->readFormula();

                        break;
                    case self::XLS_TYPE_SHAREDFMLA:
                        $this->readSharedFmla();

                        break;
                    case self::XLS_TYPE_BOOLERR:
                        $this->readBoolErr();

                        break;
                    case self::XLS_TYPE_MULBLANK:
                        $this->readMulBlank();

                        break;
                    case self::XLS_TYPE_LABEL:
                        $this->readLabel();

                        break;
                    case self::XLS_TYPE_BLANK:
                        $this->readBlank();

                        break;
                    case self::XLS_TYPE_MSODRAWING:
                        $this->readMsoDrawing();

                        break;
                    case self::XLS_TYPE_OBJ:
                        $this->readObj();

                        break;
                    case self::XLS_TYPE_WINDOW2:
                        $this->readWindow2();

                        break;
                    case self::XLS_TYPE_PAGELAYOUTVIEW:
                        $this->readPageLayoutView();

                        break;
                    case self::XLS_TYPE_SCL:
                        $this->readScl();

                        break;
                    case self::XLS_TYPE_PANE:
                        $this->readPane();

                        break;
                    case self::XLS_TYPE_SELECTION:
                        $this->readSelection();

                        break;
                    case self::XLS_TYPE_MERGEDCELLS:
                        $this->readMergedCells();

                        break;
                    case self::XLS_TYPE_HYPERLINK:
                        $this->readHyperLink();

                        break;
                    case self::XLS_TYPE_DATAVALIDATIONS:
                        $this->readDataValidations();

                        break;
                    case self::XLS_TYPE_DATAVALIDATION:
                        $this->readDataValidation();

                        break;
                    case self::XLS_TYPE_CFHEADER:
                        $cellRangeAddresses = $this->readCFHeader();

                        break;
                    case self::XLS_TYPE_CFRULE:
                        $this->readCFRule($cellRangeAddresses ?? []);

                        break;
                    case self::XLS_TYPE_SHEETLAYOUT:
                        $this->readSheetLayout();

                        break;
                    case self::XLS_TYPE_SHEETPROTECTION:
                        $this->readSheetProtection();

                        break;
                    case self::XLS_TYPE_RANGEPROTECTION:
                        $this->readRangeProtection();

                        break;
                    case self::XLS_TYPE_NOTE:
                        $this->readNote();

                        break;
                    case self::XLS_TYPE_TXO:
                        $this->readTextObject();

                        break;
                    case self::XLS_TYPE_CONTINUE:
                        $this->readContinue();

                        break;
                    case self::XLS_TYPE_EOF:
                        $this->readDefault();

                        break 2;
                    default:
                        $this->readDefault();

                        break;
                }
            }

                        if (!$this->readDataOnly && $this->drawingData) {
                $escherWorksheet = new Escher();
                $reader = new Xls\Escher($escherWorksheet);
                $escherWorksheet = $reader->load($this->drawingData);

                                                $allSpContainers = method_exists($escherWorksheet, 'getDgContainer') ? $escherWorksheet->getDgContainer()->getSpgrContainer()->getAllSpContainers() : [];
            }

                        foreach ($this->objs as $n => $obj) {
                                if (isset($allSpContainers[$n + 1])) {
                    $spContainer = $allSpContainers[$n + 1];

                                        if ($spContainer->getNestingLevel() > 1) {
                        continue;
                    }

                                                            [$startColumn, $startRow] = Coordinate::coordinateFromString($spContainer->getStartCoordinates());
                                        [$endColumn, $endRow] = Coordinate::coordinateFromString($spContainer->getEndCoordinates());

                    $startOffsetX = $spContainer->getStartOffsetX();
                    $startOffsetY = $spContainer->getStartOffsetY();
                    $endOffsetX = $spContainer->getEndOffsetX();
                    $endOffsetY = $spContainer->getEndOffsetY();

                    $width = SharedXls::getDistanceX($this->phpSheet, $startColumn, $startOffsetX, $endColumn, $endOffsetX);
                    $height = SharedXls::getDistanceY($this->phpSheet, $startRow, $startOffsetY, $endRow, $endOffsetY);

                                        $offsetX = (int) ($startOffsetX * SharedXls::sizeCol($this->phpSheet, $startColumn) / 1024);
                    $offsetY = (int) ($startOffsetY * SharedXls::sizeRow($this->phpSheet, $startRow) / 256);

                    switch ($obj['otObjType']) {
                        case 0x19:
                                                        if (isset($this->cellNotes[$obj['idObjID']])) {
                                
                                if (isset($this->textObjects[$obj['idObjID']])) {
                                    $textObject = $this->textObjects[$obj['idObjID']];
                                    $this->cellNotes[$obj['idObjID']]['objTextData'] = $textObject;
                                }
                            }

                            break;
                        case 0x08:
                                                                                    $BSEindex = $spContainer->getOPT(0x0104);

                                                                                                                                            if (!$BSEindex) {
                                continue 2;
                            }

                            if ($escherWorkbook) {
                                $BSECollection = method_exists($escherWorkbook, 'getDggContainer') ? $escherWorkbook->getDggContainer()->getBstoreContainer()->getBSECollection() : [];
                                $BSE = $BSECollection[$BSEindex - 1];
                                $blipType = $BSE->getBlipType();

                                                                if ($blip = $BSE->getBlip()) {
                                    $ih = imagecreatefromstring($blip->getData());
                                    if ($ih !== false) {
                                        $drawing = new MemoryDrawing();
                                        $drawing->setImageResource($ih);

                                                                                $drawing->setResizeProportional(false);
                                        $drawing->setWidth($width);
                                        $drawing->setHeight($height);
                                        $drawing->setOffsetX($offsetX);
                                        $drawing->setOffsetY($offsetY);

                                        switch ($blipType) {
                                            case BSE::BLIPTYPE_JPEG:
                                                $drawing->setRenderingFunction(MemoryDrawing::RENDERING_JPEG);
                                                $drawing->setMimeType(MemoryDrawing::MIMETYPE_JPEG);

                                                break;
                                            case BSE::BLIPTYPE_PNG:
                                                imagealphablending($ih, false);
                                                imagesavealpha($ih, true);
                                                $drawing->setRenderingFunction(MemoryDrawing::RENDERING_PNG);
                                                $drawing->setMimeType(MemoryDrawing::MIMETYPE_PNG);

                                                break;
                                        }

                                        $drawing->setWorksheet($this->phpSheet);
                                        $drawing->setCoordinates($spContainer->getStartCoordinates());
                                    }
                                }
                            }

                            break;
                        default:
                                                        break;
                    }
                }
            }

                        if ($this->version == self::XLS_BIFF8) {
                foreach ($this->sharedFormulaParts as $cell => $baseCell) {
                                        [$column, $row] = Coordinate::coordinateFromString($cell);
                    if (($this->getReadFilter() !== null) && $this->getReadFilter()->readCell($column, $row, $this->phpSheet->getTitle())) {
                        $formula = $this->getFormulaFromStructure($this->sharedFormulas[$baseCell], $cell);
                        $this->phpSheet->getCell($cell)->setValueExplicit('=' . $formula, DataType::TYPE_FORMULA);
                    }
                }
            }

            if (!empty($this->cellNotes)) {
                foreach ($this->cellNotes as $note => $noteDetails) {
                    if (!isset($noteDetails['objTextData'])) {
                        if (isset($this->textObjects[$note])) {
                            $textObject = $this->textObjects[$note];
                            $noteDetails['objTextData'] = $textObject;
                        } else {
                            $noteDetails['objTextData']['text'] = '';
                        }
                    }
                    $cellAddress = str_replace('$', '', $noteDetails['cellRef']);
                    $this->phpSheet->getComment($cellAddress)->setAuthor($noteDetails['author'])->setText($this->parseRichText($noteDetails['objTextData']['text']));
                }
            }
        }
        if ($this->activeSheetSet === false) {
            $this->spreadsheet->setActiveSheetIndex(0);
        }

                foreach ($this->definedname as $definedName) {
            if ($definedName['isBuiltInName']) {
                switch ($definedName['name']) {
                    case pack('C', 0x06):
                                                                        $ranges = explode(',', $definedName['formula']); 
                        $extractedRanges = [];
                        $sheetName = '';
                                                foreach ($ranges as $range) {
                                                                                                                $explodes = Worksheet::extractSheetTitle($range, true);
                            $sheetName = trim($explodes[0], "'");
                            if (!str_contains($explodes[1], ':')) {
                                $explodes[1] = $explodes[1] . ':' . $explodes[1];
                            }
                            $extractedRanges[] = str_replace('$', '', $explodes[1]);                         }
                        if ($docSheet = $this->spreadsheet->getSheetByName($sheetName)) {
                            $docSheet->getPageSetup()->setPrintArea(implode(',', $extractedRanges));                         }

                        break;
                    case pack('C', 0x07):
                                                                                                                                                                                                                                                                        $ranges = explode(',', $definedName['formula']);                         foreach ($ranges as $range) {
                                                                                                                if (str_contains($range, '!')) {
                                $explodes = Worksheet::extractSheetTitle($range, true);
                                if ($docSheet = $this->spreadsheet->getSheetByName($explodes[0])) {
                                    $extractedRange = $explodes[1];
                                    $extractedRange = str_replace('$', '', $extractedRange);

                                    $coordinateStrings = explode(':', $extractedRange);
                                    if (count($coordinateStrings) == 2) {
                                        [$firstColumn, $firstRow] = Coordinate::coordinateFromString($coordinateStrings[0]);
                                        [$lastColumn, $lastRow] = Coordinate::coordinateFromString($coordinateStrings[1]);

                                        if ($firstColumn == 'A' && $lastColumn == 'IV') {
                                                                                        $docSheet->getPageSetup()->setRowsToRepeatAtTop([$firstRow, $lastRow]);
                                        } elseif ($firstRow == 1 && $lastRow == 65536) {
                                                                                        $docSheet->getPageSetup()->setColumnsToRepeatAtLeft([$firstColumn, $lastColumn]);
                                        }
                                    }
                                }
                            }
                        }

                        break;
                }
            } else {
                                                $formula = $definedName['formula'];
                if (str_contains($formula, '!')) {
                    $explodes = Worksheet::extractSheetTitle($formula, true);
                    if (
                        ($docSheet = $this->spreadsheet->getSheetByName($explodes[0]))
                        || ($docSheet = $this->spreadsheet->getSheetByName(trim($explodes[0], "'")))
                    ) {
                        $extractedRange = $explodes[1];

                        $localOnly = ($definedName['scope'] === 0) ? false : true;

                        $scope = ($definedName['scope'] === 0) ? null : $this->spreadsheet->getSheetByName($this->sheets[$definedName['scope'] - 1]['name']);

                        $this->spreadsheet->addNamedRange(new NamedRange((string) $definedName['name'], $docSheet, $extractedRange, $localOnly, $scope));
                    }
                }
                                            }
        }
        $this->data = '';

        return $this->spreadsheet;
    }

        private function readRecordData(string $data, int $pos, int $len): string
    {
        $data = substr($data, $pos, $len);

                if ($this->encryption == self::MS_BIFF_CRYPTO_NONE || $pos < $this->encryptionStartPos) {
            return $data;
        }

        $recordData = '';
        if ($this->encryption == self::MS_BIFF_CRYPTO_RC4) {
            $oldBlock = floor($this->rc4Pos / self::REKEY_BLOCK);
            $block = (int) floor($pos / self::REKEY_BLOCK);
            $endBlock = (int) floor(($pos + $len) / self::REKEY_BLOCK);

                                    if ($block != $oldBlock || $pos < $this->rc4Pos || !$this->rc4Key) {
                $this->rc4Key = $this->makeKey($block, $this->md5Ctxt);
                $step = $pos % self::REKEY_BLOCK;
            } else {
                $step = $pos - $this->rc4Pos;
            }
            $this->rc4Key->RC4(str_repeat("\0", $step));

                        while ($block != $endBlock) {
                $step = self::REKEY_BLOCK - ($pos % self::REKEY_BLOCK);
                $recordData .= $this->rc4Key->RC4(substr($data, 0, $step));
                $data = substr($data, $step);
                $pos += $step;
                $len -= $step;
                ++$block;
                $this->rc4Key = $this->makeKey($block, $this->md5Ctxt);
            }
            $recordData .= $this->rc4Key->RC4(substr($data, 0, $len));

                                    $this->rc4Pos = $pos + $len;
        } elseif ($this->encryption == self::MS_BIFF_CRYPTO_XOR) {
            throw new Exception('XOr encryption not supported');
        }

        return $recordData;
    }

        private function loadOLE(string $filename): void
    {
                $ole = new OLERead();
                $ole->read($filename);
                $this->data = $ole->getStream($ole->wrkbook);                 $this->summaryInformation = $ole->getStream($ole->summaryInformation);
                $this->documentSummaryInformation = $ole->getStream($ole->documentSummaryInformation);
    }

        private function readSummaryInformation(): void
    {
        if (!isset($this->summaryInformation)) {
            return;
        }

                                                        
                        $secOffset = self::getInt4d($this->summaryInformation, 44);

                        
                $countProperties = self::getInt4d($this->summaryInformation, $secOffset + 4);

                $codePage = 'CP1252';

                        for ($i = 0; $i < $countProperties; ++$i) {
                        $id = self::getInt4d($this->summaryInformation, ($secOffset + 8) + (8 * $i));

                                    $offset = self::getInt4d($this->summaryInformation, ($secOffset + 12) + (8 * $i));

            $type = self::getInt4d($this->summaryInformation, $secOffset + $offset);

                        $value = null;

                        switch ($type) {
                case 0x02:                     $value = self::getUInt2d($this->summaryInformation, $secOffset + 4 + $offset);

                    break;
                case 0x03:                     $value = self::getInt4d($this->summaryInformation, $secOffset + 4 + $offset);

                    break;
                case 0x13:                                         break;
                case 0x1E:                     $byteLength = self::getInt4d($this->summaryInformation, $secOffset + 4 + $offset);
                    $value = substr($this->summaryInformation, $secOffset + 8 + $offset, $byteLength);
                    $value = StringHelper::convertEncoding($value, 'UTF-8', $codePage);
                    $value = rtrim($value);

                    break;
                case 0x40:                                         $value = OLE::OLE2LocalDate(substr($this->summaryInformation, $secOffset + 4 + $offset, 8));

                    break;
                case 0x47:                                         break;
            }

            switch ($id) {
                case 0x01:                        $codePage = CodePage::numberToName((int) $value);

                    break;
                case 0x02:                        $this->spreadsheet->getProperties()->setTitle("$value");

                    break;
                case 0x03:                        $this->spreadsheet->getProperties()->setSubject("$value");

                    break;
                case 0x04:                        $this->spreadsheet->getProperties()->setCreator("$value");

                    break;
                case 0x05:                        $this->spreadsheet->getProperties()->setKeywords("$value");

                    break;
                case 0x06:                        $this->spreadsheet->getProperties()->setDescription("$value");

                    break;
                case 0x07:                                            break;
                case 0x08:                        $this->spreadsheet->getProperties()->setLastModifiedBy("$value");

                    break;
                case 0x09:                                            break;
                case 0x0A:                                            break;
                case 0x0B:                                            break;
                case 0x0C:                        $this->spreadsheet->getProperties()->setCreated($value);

                    break;
                case 0x0D:                        $this->spreadsheet->getProperties()->setModified($value);

                    break;
                case 0x0E:                                            break;
                case 0x0F:                                            break;
                case 0x10:                                            break;
                case 0x11:                                            break;
                case 0x12:                                            break;
                case 0x13:                                            break;
            }
        }
    }

        private function readDocumentSummaryInformation(): void
    {
        if (!isset($this->documentSummaryInformation)) {
            return;
        }

                                                        
                        $secOffset = self::getInt4d($this->documentSummaryInformation, 44);

                        
                $countProperties = self::getInt4d($this->documentSummaryInformation, $secOffset + 4);

                $codePage = 'CP1252';

                        for ($i = 0; $i < $countProperties; ++$i) {
                        $id = self::getInt4d($this->documentSummaryInformation, ($secOffset + 8) + (8 * $i));

                                    $offset = self::getInt4d($this->documentSummaryInformation, ($secOffset + 12) + (8 * $i));

            $type = self::getInt4d($this->documentSummaryInformation, $secOffset + $offset);

                        $value = null;

                        switch ($type) {
                case 0x02:                        $value = self::getUInt2d($this->documentSummaryInformation, $secOffset + 4 + $offset);

                    break;
                case 0x03:                        $value = self::getInt4d($this->documentSummaryInformation, $secOffset + 4 + $offset);

                    break;
                case 0x0B:                      $value = self::getUInt2d($this->documentSummaryInformation, $secOffset + 4 + $offset);
                    $value = ($value == 0 ? false : true);

                    break;
                case 0x13:                                            break;
                case 0x1E:                        $byteLength = self::getInt4d($this->documentSummaryInformation, $secOffset + 4 + $offset);
                    $value = substr($this->documentSummaryInformation, $secOffset + 8 + $offset, $byteLength);
                    $value = StringHelper::convertEncoding($value, 'UTF-8', $codePage);
                    $value = rtrim($value);

                    break;
                case 0x40:                                            $value = OLE::OLE2LocalDate(substr($this->documentSummaryInformation, $secOffset + 4 + $offset, 8));

                    break;
                case 0x47:                                            break;
            }

            switch ($id) {
                case 0x01:                        $codePage = CodePage::numberToName((int) $value);

                    break;
                case 0x02:                        $this->spreadsheet->getProperties()->setCategory("$value");

                    break;
                case 0x03:                                            break;
                case 0x04:                                            break;
                case 0x05:                                            break;
                case 0x06:                                            break;
                case 0x07:                                            break;
                case 0x08:                                            break;
                case 0x09:                                            break;
                case 0x0A:                                            break;
                case 0x0B:                                            break;
                case 0x0C:                                            break;
                case 0x0D:                                            break;
                case 0x0E:                        $this->spreadsheet->getProperties()->setManager("$value");

                    break;
                case 0x0F:                        $this->spreadsheet->getProperties()->setCompany("$value");

                    break;
                case 0x10:                                            break;
            }
        }
    }

        private function readDefault(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);

                $this->pos += 4 + $length;
    }

        private function readNote(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        if ($this->readDataOnly) {
            return;
        }

        $cellAddress = $this->readBIFF8CellAddress(substr($recordData, 0, 4));
        if ($this->version == self::XLS_BIFF8) {
            $noteObjID = self::getUInt2d($recordData, 6);
            $noteAuthor = self::readUnicodeStringLong(substr($recordData, 8));
            $noteAuthor = $noteAuthor['value'];
            $this->cellNotes[$noteObjID] = [
                'cellRef' => $cellAddress,
                'objectID' => $noteObjID,
                'author' => $noteAuthor,
            ];
        } else {
            $extension = false;
            if ($cellAddress == '$B$65536') {
                                                                                $extension = true;
                $arrayKeys = array_keys($this->phpSheet->getComments());
                $cellAddress = array_pop($arrayKeys);
            }

            $cellAddress = str_replace('$', '', (string) $cellAddress);
                        $noteText = trim(substr($recordData, 6));

            if ($extension) {
                                $comment = $this->phpSheet->getComment($cellAddress);
                $commentText = $comment->getText()->getPlainText();
                $comment->setText($this->parseRichText($commentText . $noteText));
            } else {
                                $this->phpSheet->getComment($cellAddress)->setText($this->parseRichText($noteText));
            }
        }
    }

        private function readTextObject(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        if ($this->readDataOnly) {
            return;
        }

                                                        $grbitOpts = self::getUInt2d($recordData, 0);
        $rot = self::getUInt2d($recordData, 2);
                $cbRuns = self::getUInt2d($recordData, 12);
        $text = $this->getSplicedRecordData();

        $textByte = $text['spliceOffsets'][1] - $text['spliceOffsets'][0] - 1;
        $textStr = substr($text['recordData'], $text['spliceOffsets'][0] + 1, $textByte);
                $is16Bit = ord($text['recordData'][0]);
                        if (($is16Bit & 0x01) === 0) {
            $textStr = StringHelper::ConvertEncoding($textStr, 'UTF-8', 'ISO-8859-1');
        } else {
            $textStr = $this->decodeCodepage($textStr);
        }

        $this->textObjects[$this->textObjRef] = [
            'text' => $textStr,
            'format' => substr($text['recordData'], $text['spliceOffsets'][1], $cbRuns),
            'alignment' => $grbitOpts,
            'rotation' => $rot,
        ];
    }

        private function readBof(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = substr($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

                $substreamType = self::getUInt2d($recordData, 2);

        switch ($substreamType) {
            case self::XLS_WORKBOOKGLOBALS:
                $version = self::getUInt2d($recordData, 0);
                if (($version != self::XLS_BIFF8) && ($version != self::XLS_BIFF7)) {
                    throw new Exception('Cannot read this Excel file. Version is too old.');
                }
                $this->version = $version;

                break;
            case self::XLS_WORKSHEET:
                                                break;
            default:
                                                do {
                    $code = self::getUInt2d($this->data, $this->pos);
                    $this->readDefault();
                } while ($code != self::XLS_TYPE_EOF && $this->pos < $this->dataSize);

                break;
        }
    }

        private function readFilepass(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);

        if ($length != 54) {
            throw new Exception('Unexpected file pass record length');
        }

        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        if (!$this->verifyPassword('VelvetSweatshop', substr($recordData, 6, 16), substr($recordData, 22, 16), substr($recordData, 38, 16), $this->md5Ctxt)) {
            throw new Exception('Decryption password incorrect');
        }

        $this->encryption = self::MS_BIFF_CRYPTO_RC4;

                $this->encryptionStartPos = $this->pos + self::getUInt2d($this->data, $this->pos + 2);
    }

        private function makeKey(int $block, string $valContext): Xls\RC4
    {
        $pwarray = str_repeat("\0", 64);

        for ($i = 0; $i < 5; ++$i) {
            $pwarray[$i] = $valContext[$i];
        }

        $pwarray[5] = chr($block & 0xFF);
        $pwarray[6] = chr(($block >> 8) & 0xFF);
        $pwarray[7] = chr(($block >> 16) & 0xFF);
        $pwarray[8] = chr(($block >> 24) & 0xFF);

        $pwarray[9] = "\x80";
        $pwarray[56] = "\x48";

        $md5 = new Xls\MD5();
        $md5->add($pwarray);

        $s = $md5->getContext();

        return new Xls\RC4($s);
    }

        private function verifyPassword(string $password, string $docid, string $salt_data, string $hashedsalt_data, string &$valContext): bool
    {
        $pwarray = str_repeat("\0", 64);

        $iMax = strlen($password);
        for ($i = 0; $i < $iMax; ++$i) {
            $o = ord(substr($password, $i, 1));
            $pwarray[2 * $i] = chr($o & 0xFF);
            $pwarray[2 * $i + 1] = chr(($o >> 8) & 0xFF);
        }
        $pwarray[2 * $i] = chr(0x80);
        $pwarray[56] = chr(($i << 4) & 0xFF);

        $md5 = new Xls\MD5();
        $md5->add($pwarray);

        $mdContext1 = $md5->getContext();

        $offset = 0;
        $keyoffset = 0;
        $tocopy = 5;

        $md5->reset();

        while ($offset != 16) {
            if ((64 - $offset) < 5) {
                $tocopy = 64 - $offset;
            }
            for ($i = 0; $i <= $tocopy; ++$i) {
                $pwarray[$offset + $i] = $mdContext1[$keyoffset + $i];
            }
            $offset += $tocopy;

            if ($offset == 64) {
                $md5->add($pwarray);
                $keyoffset = $tocopy;
                $tocopy = 5 - $tocopy;
                $offset = 0;

                continue;
            }

            $keyoffset = 0;
            $tocopy = 5;
            for ($i = 0; $i < 16; ++$i) {
                $pwarray[$offset + $i] = $docid[$i];
            }
            $offset += 16;
        }

        $pwarray[16] = "\x80";
        for ($i = 0; $i < 47; ++$i) {
            $pwarray[17 + $i] = "\0";
        }
        $pwarray[56] = "\x80";
        $pwarray[57] = "\x0a";

        $md5->add($pwarray);
        $valContext = $md5->getContext();

        $key = $this->makeKey(0, $valContext);

        $salt = $key->RC4($salt_data);
        $hashedsalt = $key->RC4($hashedsalt_data);

        $salt .= "\x80" . str_repeat("\0", 47);
        $salt[56] = "\x80";

        $md5->reset();
        $md5->add($salt);
        $mdContext2 = $md5->getContext();

        return $mdContext2 == $hashedsalt;
    }

        private function readCodepage(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

                $codepage = self::getUInt2d($recordData, 0);

        $this->codepage = CodePage::numberToName($codepage);
    }

        private function readDateMode(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

                Date::setExcelCalendar(Date::CALENDAR_WINDOWS_1900);
        if (ord($recordData[0]) == 1) {
            Date::setExcelCalendar(Date::CALENDAR_MAC_1904);
        }
    }

        private function readFont(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
            $objFont = new Font();

                        $size = self::getUInt2d($recordData, 0);
            $objFont->setSize($size / 20);

                                                $isItalic = (0x0002 & self::getUInt2d($recordData, 2)) >> 1;
            if ($isItalic) {
                $objFont->setItalic(true);
            }

                                    $isStrike = (0x0008 & self::getUInt2d($recordData, 2)) >> 3;
            if ($isStrike) {
                $objFont->setStrikethrough(true);
            }

                        $colorIndex = self::getUInt2d($recordData, 4);
            $objFont->colorIndex = $colorIndex;

                        $weight = self::getUInt2d($recordData, 6);
            switch ($weight) {
                case 0x02BC:
                    $objFont->setBold(true);

                    break;
            }

                        $escapement = self::getUInt2d($recordData, 8);
            CellFont::escapement($objFont, $escapement);

                        $underlineType = ord($recordData[10]);
            CellFont::underline($objFont, $underlineType);

                                                            if ($this->version == self::XLS_BIFF8) {
                $string = self::readUnicodeStringShort(substr($recordData, 14));
            } else {
                $string = $this->readByteStringShort(substr($recordData, 14));
            }
            $objFont->setName($string['value']);

            $this->objFonts[] = $objFont;
        }
    }

        private function readFormat(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
            $indexCode = self::getUInt2d($recordData, 0);

            if ($this->version == self::XLS_BIFF8) {
                $string = self::readUnicodeStringLong(substr($recordData, 2));
            } else {
                                $string = $this->readByteStringShort(substr($recordData, 2));
            }

            $formatString = $string['value'];
                        if ($formatString === 'GENERAL') {
                $formatString = NumberFormat::FORMAT_GENERAL;
            }
            $this->formats[$indexCode] = $formatString;
        }
    }

        private function readXf(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        $objStyle = new Style();

        if (!$this->readDataOnly) {
                        if (self::getUInt2d($recordData, 0) < 4) {
                $fontIndex = self::getUInt2d($recordData, 0);
            } else {
                                                $fontIndex = self::getUInt2d($recordData, 0) - 1;
            }
            if (isset($this->objFonts[$fontIndex])) {
                $objStyle->setFont($this->objFonts[$fontIndex]);
            }

                        $numberFormatIndex = self::getUInt2d($recordData, 2);
            if (isset($this->formats[$numberFormatIndex])) {
                                $numberFormat = ['formatCode' => $this->formats[$numberFormatIndex]];
            } elseif (($code = NumberFormat::builtInFormatCode($numberFormatIndex)) !== '') {
                                $numberFormat = ['formatCode' => $code];
            } else {
                                $numberFormat = ['formatCode' => NumberFormat::FORMAT_GENERAL];
            }
            $objStyle->getNumberFormat()->setFormatCode($numberFormat['formatCode']);

                                    $xfTypeProt = self::getUInt2d($recordData, 4);
                        $isLocked = (0x01 & $xfTypeProt) >> 0;
            $objStyle->getProtection()->setLocked($isLocked ? Protection::PROTECTION_INHERIT : Protection::PROTECTION_UNPROTECTED);

                        $isHidden = (0x02 & $xfTypeProt) >> 1;
            $objStyle->getProtection()->setHidden($isHidden ? Protection::PROTECTION_PROTECTED : Protection::PROTECTION_UNPROTECTED);

                        $isCellStyleXf = (0x04 & $xfTypeProt) >> 2;

                                    $horAlign = (0x07 & ord($recordData[6])) >> 0;
            Xls\Style\CellAlignment::horizontal($objStyle->getAlignment(), $horAlign);

                        $wrapText = (0x08 & ord($recordData[6])) >> 3;
            Xls\Style\CellAlignment::wrap($objStyle->getAlignment(), $wrapText);

                        $vertAlign = (0x70 & ord($recordData[6])) >> 4;
            Xls\Style\CellAlignment::vertical($objStyle->getAlignment(), $vertAlign);

            if ($this->version == self::XLS_BIFF8) {
                                $angle = ord($recordData[7]);
                $rotation = 0;
                if ($angle <= 90) {
                    $rotation = $angle;
                } elseif ($angle <= 180) {
                    $rotation = 90 - $angle;
                } elseif ($angle == Alignment::TEXTROTATION_STACK_EXCEL) {
                    $rotation = Alignment::TEXTROTATION_STACK_PHPSPREADSHEET;
                }
                $objStyle->getAlignment()->setTextRotation($rotation);

                                                $indent = (0x0F & ord($recordData[8])) >> 0;
                $objStyle->getAlignment()->setIndent($indent);

                                $shrinkToFit = (0x10 & ord($recordData[8])) >> 4;
                switch ($shrinkToFit) {
                    case 0:
                        $objStyle->getAlignment()->setShrinkToFit(false);

                        break;
                    case 1:
                        $objStyle->getAlignment()->setShrinkToFit(true);

                        break;
                }

                
                                                if ($bordersLeftStyle = Xls\Style\Border::lookup((0x0000000F & self::getInt4d($recordData, 10)) >> 0)) {
                    $objStyle->getBorders()->getLeft()->setBorderStyle($bordersLeftStyle);
                }
                                if ($bordersRightStyle = Xls\Style\Border::lookup((0x000000F0 & self::getInt4d($recordData, 10)) >> 4)) {
                    $objStyle->getBorders()->getRight()->setBorderStyle($bordersRightStyle);
                }
                                if ($bordersTopStyle = Xls\Style\Border::lookup((0x00000F00 & self::getInt4d($recordData, 10)) >> 8)) {
                    $objStyle->getBorders()->getTop()->setBorderStyle($bordersTopStyle);
                }
                                if ($bordersBottomStyle = Xls\Style\Border::lookup((0x0000F000 & self::getInt4d($recordData, 10)) >> 12)) {
                    $objStyle->getBorders()->getBottom()->setBorderStyle($bordersBottomStyle);
                }
                                $objStyle->getBorders()->getLeft()->colorIndex = (0x007F0000 & self::getInt4d($recordData, 10)) >> 16;

                                $objStyle->getBorders()->getRight()->colorIndex = (0x3F800000 & self::getInt4d($recordData, 10)) >> 23;

                                $diagonalDown = (0x40000000 & self::getInt4d($recordData, 10)) >> 30 ? true : false;

                                $diagonalUp = ((int) 0x80000000 & self::getInt4d($recordData, 10)) >> 31 ? true : false;

                if ($diagonalUp === false) {
                    if ($diagonalDown === false) {
                        $objStyle->getBorders()->setDiagonalDirection(Borders::DIAGONAL_NONE);
                    } else {
                        $objStyle->getBorders()->setDiagonalDirection(Borders::DIAGONAL_DOWN);
                    }
                } elseif ($diagonalDown === false) {
                    $objStyle->getBorders()->setDiagonalDirection(Borders::DIAGONAL_UP);
                } else {
                    $objStyle->getBorders()->setDiagonalDirection(Borders::DIAGONAL_BOTH);
                }

                                                $objStyle->getBorders()->getTop()->colorIndex = (0x0000007F & self::getInt4d($recordData, 14)) >> 0;

                                $objStyle->getBorders()->getBottom()->colorIndex = (0x00003F80 & self::getInt4d($recordData, 14)) >> 7;

                                $objStyle->getBorders()->getDiagonal()->colorIndex = (0x001FC000 & self::getInt4d($recordData, 14)) >> 14;

                                if ($bordersDiagonalStyle = Xls\Style\Border::lookup((0x01E00000 & self::getInt4d($recordData, 14)) >> 21)) {
                    $objStyle->getBorders()->getDiagonal()->setBorderStyle($bordersDiagonalStyle);
                }

                                if ($fillType = Xls\Style\FillPattern::lookup(((int) 0xFC000000 & self::getInt4d($recordData, 14)) >> 26)) {
                    $objStyle->getFill()->setFillType($fillType);
                }
                                                $objStyle->getFill()->startcolorIndex = (0x007F & self::getUInt2d($recordData, 18)) >> 0;

                                $objStyle->getFill()->endcolorIndex = (0x3F80 & self::getUInt2d($recordData, 18)) >> 7;
            } else {
                
                                $orientationAndFlags = ord($recordData[7]);

                                $xfOrientation = (0x03 & $orientationAndFlags) >> 0;
                switch ($xfOrientation) {
                    case 0:
                        $objStyle->getAlignment()->setTextRotation(0);

                        break;
                    case 1:
                        $objStyle->getAlignment()->setTextRotation(Alignment::TEXTROTATION_STACK_PHPSPREADSHEET);

                        break;
                    case 2:
                        $objStyle->getAlignment()->setTextRotation(90);

                        break;
                    case 3:
                        $objStyle->getAlignment()->setTextRotation(-90);

                        break;
                }

                                $borderAndBackground = self::getInt4d($recordData, 8);

                                $objStyle->getFill()->startcolorIndex = (0x0000007F & $borderAndBackground) >> 0;

                                $objStyle->getFill()->endcolorIndex = (0x00003F80 & $borderAndBackground) >> 7;

                                $objStyle->getFill()->setFillType(Xls\Style\FillPattern::lookup((0x003F0000 & $borderAndBackground) >> 16));

                                $objStyle->getBorders()->getBottom()->setBorderStyle(Xls\Style\Border::lookup((0x01C00000 & $borderAndBackground) >> 22));

                                $objStyle->getBorders()->getBottom()->colorIndex = ((int) 0xFE000000 & $borderAndBackground) >> 25;

                                $borderLines = self::getInt4d($recordData, 12);

                                $objStyle->getBorders()->getTop()->setBorderStyle(Xls\Style\Border::lookup((0x00000007 & $borderLines) >> 0));

                                $objStyle->getBorders()->getLeft()->setBorderStyle(Xls\Style\Border::lookup((0x00000038 & $borderLines) >> 3));

                                $objStyle->getBorders()->getRight()->setBorderStyle(Xls\Style\Border::lookup((0x000001C0 & $borderLines) >> 6));

                                $objStyle->getBorders()->getTop()->colorIndex = (0x0000FE00 & $borderLines) >> 9;

                                $objStyle->getBorders()->getLeft()->colorIndex = (0x007F0000 & $borderLines) >> 16;

                                $objStyle->getBorders()->getRight()->colorIndex = (0x3F800000 & $borderLines) >> 23;
            }

                        if ($isCellStyleXf) {
                                if ($this->xfIndex == 0) {
                    $this->spreadsheet->addCellStyleXf($objStyle);
                    $this->mapCellStyleXfIndex[$this->xfIndex] = 0;
                }
            } else {
                                $this->spreadsheet->addCellXf($objStyle);
                $this->mapCellXfIndex[$this->xfIndex] = count($this->spreadsheet->getCellXfCollection()) - 1;
            }

                        ++$this->xfIndex;
        }
    }

    private function readXfExt(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
            
            
            
            
                        $ixfe = self::getUInt2d($recordData, 14);

            
                        
                        $offset = 20;
            while ($offset < $length) {
                                $extType = self::getUInt2d($recordData, $offset);

                                $cb = self::getUInt2d($recordData, $offset + 2);

                                $extData = substr($recordData, $offset + 4, $cb);

                switch ($extType) {
                    case 4:                                $xclfType = self::getUInt2d($extData, 0);                         $xclrValue = substr($extData, 4, 4); 
                        if ($xclfType == 2) {
                            $rgb = sprintf('%02X%02X%02X', ord($xclrValue[0]), ord($xclrValue[1]), ord($xclrValue[2]));

                                                        if (isset($this->mapCellXfIndex[$ixfe])) {
                                $fill = $this->spreadsheet->getCellXfByIndex($this->mapCellXfIndex[$ixfe])->getFill();
                                $fill->getStartColor()->setRGB($rgb);
                                $fill->startcolorIndex = null;                             }
                        }

                        break;
                    case 5:                                $xclfType = self::getUInt2d($extData, 0);                         $xclrValue = substr($extData, 4, 4); 
                        if ($xclfType == 2) {
                            $rgb = sprintf('%02X%02X%02X', ord($xclrValue[0]), ord($xclrValue[1]), ord($xclrValue[2]));

                                                        if (isset($this->mapCellXfIndex[$ixfe])) {
                                $fill = $this->spreadsheet->getCellXfByIndex($this->mapCellXfIndex[$ixfe])->getFill();
                                $fill->getEndColor()->setRGB($rgb);
                                $fill->endcolorIndex = null;                             }
                        }

                        break;
                    case 7:                                $xclfType = self::getUInt2d($extData, 0);                         $xclrValue = substr($extData, 4, 4); 
                        if ($xclfType == 2) {
                            $rgb = sprintf('%02X%02X%02X', ord($xclrValue[0]), ord($xclrValue[1]), ord($xclrValue[2]));

                                                        if (isset($this->mapCellXfIndex[$ixfe])) {
                                $top = $this->spreadsheet->getCellXfByIndex($this->mapCellXfIndex[$ixfe])->getBorders()->getTop();
                                $top->getColor()->setRGB($rgb);
                                $top->colorIndex = null;                             }
                        }

                        break;
                    case 8:                                $xclfType = self::getUInt2d($extData, 0);                         $xclrValue = substr($extData, 4, 4); 
                        if ($xclfType == 2) {
                            $rgb = sprintf('%02X%02X%02X', ord($xclrValue[0]), ord($xclrValue[1]), ord($xclrValue[2]));

                                                        if (isset($this->mapCellXfIndex[$ixfe])) {
                                $bottom = $this->spreadsheet->getCellXfByIndex($this->mapCellXfIndex[$ixfe])->getBorders()->getBottom();
                                $bottom->getColor()->setRGB($rgb);
                                $bottom->colorIndex = null;                             }
                        }

                        break;
                    case 9:                                $xclfType = self::getUInt2d($extData, 0);                         $xclrValue = substr($extData, 4, 4); 
                        if ($xclfType == 2) {
                            $rgb = sprintf('%02X%02X%02X', ord($xclrValue[0]), ord($xclrValue[1]), ord($xclrValue[2]));

                                                        if (isset($this->mapCellXfIndex[$ixfe])) {
                                $left = $this->spreadsheet->getCellXfByIndex($this->mapCellXfIndex[$ixfe])->getBorders()->getLeft();
                                $left->getColor()->setRGB($rgb);
                                $left->colorIndex = null;                             }
                        }

                        break;
                    case 10:                                $xclfType = self::getUInt2d($extData, 0);                         $xclrValue = substr($extData, 4, 4); 
                        if ($xclfType == 2) {
                            $rgb = sprintf('%02X%02X%02X', ord($xclrValue[0]), ord($xclrValue[1]), ord($xclrValue[2]));

                                                        if (isset($this->mapCellXfIndex[$ixfe])) {
                                $right = $this->spreadsheet->getCellXfByIndex($this->mapCellXfIndex[$ixfe])->getBorders()->getRight();
                                $right->getColor()->setRGB($rgb);
                                $right->colorIndex = null;                             }
                        }

                        break;
                    case 11:                                $xclfType = self::getUInt2d($extData, 0);                         $xclrValue = substr($extData, 4, 4); 
                        if ($xclfType == 2) {
                            $rgb = sprintf('%02X%02X%02X', ord($xclrValue[0]), ord($xclrValue[1]), ord($xclrValue[2]));

                                                        if (isset($this->mapCellXfIndex[$ixfe])) {
                                $diagonal = $this->spreadsheet->getCellXfByIndex($this->mapCellXfIndex[$ixfe])->getBorders()->getDiagonal();
                                $diagonal->getColor()->setRGB($rgb);
                                $diagonal->colorIndex = null;                             }
                        }

                        break;
                    case 13:                            $xclfType = self::getUInt2d($extData, 0);                         $xclrValue = substr($extData, 4, 4); 
                        if ($xclfType == 2) {
                            $rgb = sprintf('%02X%02X%02X', ord($xclrValue[0]), ord($xclrValue[1]), ord($xclrValue[2]));

                                                        if (isset($this->mapCellXfIndex[$ixfe])) {
                                $font = $this->spreadsheet->getCellXfByIndex($this->mapCellXfIndex[$ixfe])->getFont();
                                $font->getColor()->setRGB($rgb);
                                $font->colorIndex = null;                             }
                        }

                        break;
                }

                $offset += $cb;
            }
        }
    }

        private function readStyle(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
                        $ixfe = self::getUInt2d($recordData, 0);

                        
                        $isBuiltIn = (bool) ((0x8000 & $ixfe) >> 15);

            if ($isBuiltIn) {
                                $builtInId = ord($recordData[2]);

                switch ($builtInId) {
                    case 0x00:
                                                break;
                    default:
                        break;
                }
            }
                    }
    }

        private function readPalette(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
                        $nm = self::getUInt2d($recordData, 0);

                        for ($i = 0; $i < $nm; ++$i) {
                $rgb = substr($recordData, 2 + 4 * $i, 4);
                $this->palette[] = self::readRGB($rgb);
            }
        }
    }

        private function readSheet(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                        $rec_offset = self::getInt4d($this->data, $this->pos + 4);

                $this->pos += 4 + $length;

                $sheetState = match (ord($recordData[4])) {
            0x00 => Worksheet::SHEETSTATE_VISIBLE,
            0x01 => Worksheet::SHEETSTATE_HIDDEN,
            0x02 => Worksheet::SHEETSTATE_VERYHIDDEN,
            default => Worksheet::SHEETSTATE_VISIBLE,
        };

                $sheetType = ord($recordData[5]);

                $rec_name = null;
        if ($this->version == self::XLS_BIFF8) {
            $string = self::readUnicodeStringShort(substr($recordData, 6));
            $rec_name = $string['value'];
        } elseif ($this->version == self::XLS_BIFF7) {
            $string = $this->readByteStringShort(substr($recordData, 6));
            $rec_name = $string['value'];
        }

        $this->sheets[] = [
            'name' => $rec_name,
            'offset' => $rec_offset,
            'sheetState' => $sheetState,
            'sheetType' => $sheetType,
        ];
    }

        private function readExternalBook(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

                $offset = 0;

                if (strlen($recordData) > 4) {
                                    $nm = self::getUInt2d($recordData, 0);
            $offset += 2;

                        $encodedUrlString = self::readUnicodeStringLong(substr($recordData, 2));
            $offset += $encodedUrlString['size'];

                        $externalSheetNames = [];
            for ($i = 0; $i < $nm; ++$i) {
                $externalSheetNameString = self::readUnicodeStringLong(substr($recordData, $offset));
                $externalSheetNames[] = $externalSheetNameString['value'];
                $offset += $externalSheetNameString['size'];
            }

                        $this->externalBooks[] = [
                'type' => 'external',
                'encodedUrl' => $encodedUrlString['value'],
                'externalSheetNames' => $externalSheetNames,
            ];
        } elseif (substr($recordData, 2, 2) == pack('CC', 0x01, 0x04)) {
                                                $this->externalBooks[] = [
                'type' => 'internal',
            ];
        } elseif (substr($recordData, 0, 4) == pack('vCC', 0x0001, 0x01, 0x3A)) {
                                    $this->externalBooks[] = [
                'type' => 'addInFunction',
            ];
        } elseif (substr($recordData, 0, 2) == pack('v', 0x0000)) {
                                                $this->externalBooks[] = [
                'type' => 'DDEorOLE',
            ];
        }
    }

        private function readExternName(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

                if ($this->version == self::XLS_BIFF8) {
                        
            
            
                        $nameString = self::readUnicodeStringShort(substr($recordData, 6));

                        $offset = 6 + $nameString['size'];
            $formula = $this->getFormulaFromStructure(substr($recordData, $offset));

            $this->externalNames[] = [
                'name' => $nameString['value'],
                'formula' => $formula,
            ];
        }
    }

        private function readExternSheet(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

                if ($this->version == self::XLS_BIFF8) {
                        $nm = self::getUInt2d($recordData, 0);
            for ($i = 0; $i < $nm; ++$i) {
                $this->ref[] = [
                                        'externalBookIndex' => self::getUInt2d($recordData, 2 + 6 * $i),
                                        'firstSheetIndex' => self::getUInt2d($recordData, 4 + 6 * $i),
                                        'lastSheetIndex' => self::getUInt2d($recordData, 6 + 6 * $i),
                ];
            }
        }
    }

        private function readDefinedName(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        if ($this->version == self::XLS_BIFF8) {
            
                        $opts = self::getUInt2d($recordData, 0);

                        $isBuiltInName = (0x0020 & $opts) >> 5;

            
                        $nlen = ord($recordData[3]);

                                    $flen = self::getUInt2d($recordData, 4);

                        $scope = self::getUInt2d($recordData, 8);

                        $string = self::readUnicodeString(substr($recordData, 14), $nlen);

                        $offset = 14 + $string['size'];
            $formulaStructure = pack('v', $flen) . substr($recordData, $offset);

            try {
                $formula = $this->getFormulaFromStructure($formulaStructure);
            } catch (PhpSpreadsheetException) {
                $formula = '';
            }

            $this->definedname[] = [
                'isBuiltInName' => $isBuiltInName,
                'name' => $string['value'],
                'formula' => $formula,
                'scope' => $scope,
            ];
        }
    }

        private function readMsoDrawingGroup(): void
    {
        
                $splicedRecordData = $this->getSplicedRecordData();
        $recordData = $splicedRecordData['recordData'];

        $this->drawingGroupData .= $recordData;
    }

        private function readSst(): void
    {
                $pos = 0;

                $limitposSST = 0;

                $splicedRecordData = $this->getSplicedRecordData();

        $recordData = $splicedRecordData['recordData'];
        $spliceOffsets = $splicedRecordData['spliceOffsets'];

                $pos += 4;

                $nm = self::getInt4d($recordData, 4);
        $pos += 4;

                foreach ($spliceOffsets as $spliceOffset) {
                                    if ($pos <= $spliceOffset) {
                $limitposSST = $spliceOffset;
            }
        }

                for ($i = 0; $i < $nm && $pos < $limitposSST; ++$i) {
                        $numChars = self::getUInt2d($recordData, $pos);
            $pos += 2;

                        $optionFlags = ord($recordData[$pos]);
            ++$pos;

                        $isCompressed = (($optionFlags & 0x01) == 0);

                        $hasAsian = (($optionFlags & 0x04) != 0);

                        $hasRichText = (($optionFlags & 0x08) != 0);

            $formattingRuns = 0;
            if ($hasRichText) {
                                $formattingRuns = self::getUInt2d($recordData, $pos);
                $pos += 2;
            }

            $extendedRunLength = 0;
            if ($hasAsian) {
                                $extendedRunLength = self::getInt4d($recordData, $pos);
                $pos += 4;
            }

                        $len = ($isCompressed) ? $numChars : $numChars * 2;

                        $limitpos = null;
            foreach ($spliceOffsets as $spliceOffset) {
                                                if ($pos <= $spliceOffset) {
                    $limitpos = $spliceOffset;

                    break;
                }
            }

            if ($pos + $len <= $limitpos) {
                
                $retstr = substr($recordData, $pos, $len);
                $pos += $len;
            } else {
                
                                $retstr = substr($recordData, $pos, $limitpos - $pos);

                $bytesRead = $limitpos - $pos;

                                $charsLeft = $numChars - (($isCompressed) ? $bytesRead : ($bytesRead / 2));

                $pos = $limitpos;

                                while ($charsLeft > 0) {
                                        foreach ($spliceOffsets as $spliceOffset) {
                        if ($pos < $spliceOffset) {
                            $limitpos = $spliceOffset;

                            break;
                        }
                    }

                                                            $option = ord($recordData[$pos]);
                    ++$pos;

                    if ($isCompressed && ($option == 0)) {
                                                                        $len = min($charsLeft, $limitpos - $pos);
                        $retstr .= substr($recordData, $pos, $len);
                        $charsLeft -= $len;
                        $isCompressed = true;
                    } elseif (!$isCompressed && ($option != 0)) {
                                                                        $len = min($charsLeft * 2, $limitpos - $pos);
                        $retstr .= substr($recordData, $pos, $len);
                        $charsLeft -= $len / 2;
                        $isCompressed = false;
                    } elseif (!$isCompressed && ($option == 0)) {
                                                                        $len = min($charsLeft, $limitpos - $pos);
                        for ($j = 0; $j < $len; ++$j) {
                            $retstr .= $recordData[$pos + $j]
                                . chr(0);
                        }
                        $charsLeft -= $len;
                        $isCompressed = false;
                    } else {
                                                                        $newstr = '';
                        $jMax = strlen($retstr);
                        for ($j = 0; $j < $jMax; ++$j) {
                            $newstr .= $retstr[$j] . chr(0);
                        }
                        $retstr = $newstr;
                        $len = min($charsLeft * 2, $limitpos - $pos);
                        $retstr .= substr($recordData, $pos, $len);
                        $charsLeft -= $len / 2;
                        $isCompressed = false;
                    }

                    $pos += $len;
                }
            }

                        $retstr = self::encodeUTF16($retstr, $isCompressed);

                        $fmtRuns = [];
            if ($hasRichText) {
                                for ($j = 0; $j < $formattingRuns; ++$j) {
                                        $charPos = self::getUInt2d($recordData, $pos + $j * 4);

                                        $fontIndex = self::getUInt2d($recordData, $pos + 2 + $j * 4);

                    $fmtRuns[] = [
                        'charPos' => $charPos,
                        'fontIndex' => $fontIndex,
                    ];
                }
                $pos += 4 * $formattingRuns;
            }

                        if ($hasAsian) {
                                $pos += $extendedRunLength;
            }

                        $this->sst[] = [
                'value' => $retstr,
                'fmtRuns' => $fmtRuns,
            ];
        }

            }

        private function readPrintGridlines(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        if ($this->version == self::XLS_BIFF8 && !$this->readDataOnly) {
                        $printGridlines = (bool) self::getUInt2d($recordData, 0);
            $this->phpSheet->setPrintGridlines($printGridlines);
        }
    }

        private function readDefaultRowHeight(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

                        $height = self::getUInt2d($recordData, 2);
        $this->phpSheet->getDefaultRowDimension()->setRowHeight($height / 20);
    }

        private function readSheetPr(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        
                $isSummaryBelow = (0x0040 & self::getUInt2d($recordData, 0)) >> 6;
        $this->phpSheet->setShowSummaryBelow((bool) $isSummaryBelow);

                $isSummaryRight = (0x0080 & self::getUInt2d($recordData, 0)) >> 7;
        $this->phpSheet->setShowSummaryRight((bool) $isSummaryRight);

                        $this->isFitToPages = (bool) ((0x0100 & self::getUInt2d($recordData, 0)) >> 8);
    }

        private function readHorizontalPageBreaks(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        if ($this->version == self::XLS_BIFF8 && !$this->readDataOnly) {
                        $nm = self::getUInt2d($recordData, 0);

                        for ($i = 0; $i < $nm; ++$i) {
                $r = self::getUInt2d($recordData, 2 + 6 * $i);
                $cf = self::getUInt2d($recordData, 2 + 6 * $i + 2);
                
                                $this->phpSheet->setBreak([$cf + 1, $r], Worksheet::BREAK_ROW);
            }
        }
    }

        private function readVerticalPageBreaks(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        if ($this->version == self::XLS_BIFF8 && !$this->readDataOnly) {
                        $nm = self::getUInt2d($recordData, 0);

                        for ($i = 0; $i < $nm; ++$i) {
                $c = self::getUInt2d($recordData, 2 + 6 * $i);
                $rf = self::getUInt2d($recordData, 2 + 6 * $i + 2);
                
                                $this->phpSheet->setBreak([$c + 1, ($rf > 0) ? $rf : 1], Worksheet::BREAK_COLUMN);
            }
        }
    }

        private function readHeader(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
                                    if ($recordData) {
                if ($this->version == self::XLS_BIFF8) {
                    $string = self::readUnicodeStringLong($recordData);
                } else {
                    $string = $this->readByteStringShort($recordData);
                }

                $this->phpSheet->getHeaderFooter()->setOddHeader($string['value']);
                $this->phpSheet->getHeaderFooter()->setEvenHeader($string['value']);
            }
        }
    }

        private function readFooter(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
                                    if ($recordData) {
                if ($this->version == self::XLS_BIFF8) {
                    $string = self::readUnicodeStringLong($recordData);
                } else {
                    $string = $this->readByteStringShort($recordData);
                }
                $this->phpSheet->getHeaderFooter()->setOddFooter($string['value']);
                $this->phpSheet->getHeaderFooter()->setEvenFooter($string['value']);
            }
        }
    }

        private function readHcenter(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
                        $isHorizontalCentered = (bool) self::getUInt2d($recordData, 0);

            $this->phpSheet->getPageSetup()->setHorizontalCentered($isHorizontalCentered);
        }
    }

        private function readVcenter(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
                        $isVerticalCentered = (bool) self::getUInt2d($recordData, 0);

            $this->phpSheet->getPageSetup()->setVerticalCentered($isVerticalCentered);
        }
    }

        private function readLeftMargin(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
                        $this->phpSheet->getPageMargins()->setLeft(self::extractNumber($recordData));
        }
    }

        private function readRightMargin(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
                        $this->phpSheet->getPageMargins()->setRight(self::extractNumber($recordData));
        }
    }

        private function readTopMargin(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
                        $this->phpSheet->getPageMargins()->setTop(self::extractNumber($recordData));
        }
    }

        private function readBottomMargin(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
                        $this->phpSheet->getPageMargins()->setBottom(self::extractNumber($recordData));
        }
    }

        private function readPageSetup(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
                        $paperSize = self::getUInt2d($recordData, 0);

                        $scale = self::getUInt2d($recordData, 2);

                        $fitToWidth = self::getUInt2d($recordData, 6);

                        $fitToHeight = self::getUInt2d($recordData, 8);

            
                        $isOverThenDown = (0x0001 & self::getUInt2d($recordData, 10));

                        $isPortrait = (0x0002 & self::getUInt2d($recordData, 10)) >> 1;

                                    $isNotInit = (0x0004 & self::getUInt2d($recordData, 10)) >> 2;

            if (!$isNotInit) {
                $this->phpSheet->getPageSetup()->setPaperSize($paperSize);
                $this->phpSheet->getPageSetup()->setPageOrder(((bool) $isOverThenDown) ? PageSetup::PAGEORDER_OVER_THEN_DOWN : PageSetup::PAGEORDER_DOWN_THEN_OVER);
                $this->phpSheet->getPageSetup()->setOrientation(((bool) $isPortrait) ? PageSetup::ORIENTATION_PORTRAIT : PageSetup::ORIENTATION_LANDSCAPE);

                $this->phpSheet->getPageSetup()->setScale($scale, false);
                $this->phpSheet->getPageSetup()->setFitToPage((bool) $this->isFitToPages);
                $this->phpSheet->getPageSetup()->setFitToWidth($fitToWidth, false);
                $this->phpSheet->getPageSetup()->setFitToHeight($fitToHeight, false);
            }

                        $marginHeader = self::extractNumber(substr($recordData, 16, 8));
            $this->phpSheet->getPageMargins()->setHeader($marginHeader);

                        $marginFooter = self::extractNumber(substr($recordData, 24, 8));
            $this->phpSheet->getPageMargins()->setFooter($marginFooter);
        }
    }

        private function readProtect(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        if ($this->readDataOnly) {
            return;
        }

        
                $bool = (0x01 & self::getUInt2d($recordData, 0)) >> 0;
        $this->phpSheet->getProtection()->setSheet((bool) $bool);
    }

        private function readScenProtect(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        if ($this->readDataOnly) {
            return;
        }

        
                $bool = (0x01 & self::getUInt2d($recordData, 0)) >> 0;

        $this->phpSheet->getProtection()->setScenarios((bool) $bool);
    }

        private function readObjectProtect(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        if ($this->readDataOnly) {
            return;
        }

        
                $bool = (0x01 & self::getUInt2d($recordData, 0)) >> 0;

        $this->phpSheet->getProtection()->setObjects((bool) $bool);
    }

        private function readPassword(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
                        $password = strtoupper(dechex(self::getUInt2d($recordData, 0)));             $this->phpSheet->getProtection()->setPassword($password, true);
        }
    }

        private function readDefColWidth(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

                $width = self::getUInt2d($recordData, 0);
        if ($width != 8) {
            $this->phpSheet->getDefaultColumnDimension()->setWidth($width);
        }
    }

        private function readColInfo(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
                        $firstColumnIndex = self::getUInt2d($recordData, 0);

                        $lastColumnIndex = self::getUInt2d($recordData, 2);

                        $width = self::getUInt2d($recordData, 4);

                        $xfIndex = self::getUInt2d($recordData, 6);

                                    $isHidden = (0x0001 & self::getUInt2d($recordData, 8)) >> 0;

                        $level = (0x0700 & self::getUInt2d($recordData, 8)) >> 8;

                        $isCollapsed = (bool) ((0x1000 & self::getUInt2d($recordData, 8)) >> 12);

            
            for ($i = $firstColumnIndex + 1; $i <= $lastColumnIndex + 1; ++$i) {
                if ($lastColumnIndex == 255 || $lastColumnIndex == 256) {
                    $this->phpSheet->getDefaultColumnDimension()->setWidth($width / 256);

                    break;
                }
                $this->phpSheet->getColumnDimensionByColumn($i)->setWidth($width / 256);
                $this->phpSheet->getColumnDimensionByColumn($i)->setVisible(!$isHidden);
                $this->phpSheet->getColumnDimensionByColumn($i)->setOutlineLevel($level);
                $this->phpSheet->getColumnDimensionByColumn($i)->setCollapsed($isCollapsed);
                if (isset($this->mapCellXfIndex[$xfIndex])) {
                    $this->phpSheet->getColumnDimensionByColumn($i)->setXfIndex($this->mapCellXfIndex[$xfIndex]);
                }
            }
        }
    }

        private function readRow(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
                        $r = self::getUInt2d($recordData, 0);

            
            
            
                        $height = (0x7FFF & self::getUInt2d($recordData, 6)) >> 0;

                        $useDefaultHeight = (0x8000 & self::getUInt2d($recordData, 6)) >> 15;

            if (!$useDefaultHeight) {
                $this->phpSheet->getRowDimension($r + 1)->setRowHeight($height / 20);
            }

            
            
            
                        $level = (0x00000007 & self::getInt4d($recordData, 12)) >> 0;
            $this->phpSheet->getRowDimension($r + 1)->setOutlineLevel($level);

                        $isCollapsed = (bool) ((0x00000010 & self::getInt4d($recordData, 12)) >> 4);
            $this->phpSheet->getRowDimension($r + 1)->setCollapsed($isCollapsed);

                        $isHidden = (0x00000020 & self::getInt4d($recordData, 12)) >> 5;
            $this->phpSheet->getRowDimension($r + 1)->setVisible(!$isHidden);

                        $hasExplicitFormat = (0x00000080 & self::getInt4d($recordData, 12)) >> 7;

                        $xfIndex = (0x0FFF0000 & self::getInt4d($recordData, 12)) >> 16;

            if ($hasExplicitFormat && isset($this->mapCellXfIndex[$xfIndex])) {
                $this->phpSheet->getRowDimension($r + 1)->setXfIndex($this->mapCellXfIndex[$xfIndex]);
            }
        }
    }

        private function readRk(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

                $row = self::getUInt2d($recordData, 0);

                $column = self::getUInt2d($recordData, 2);
        $columnString = Coordinate::stringFromColumnIndex($column + 1);

                if (($this->getReadFilter() !== null) && $this->getReadFilter()->readCell($columnString, $row + 1, $this->phpSheet->getTitle())) {
                        $xfIndex = self::getUInt2d($recordData, 4);

                        $rknum = self::getInt4d($recordData, 6);
            $numValue = self::getIEEE754($rknum);

            $cell = $this->phpSheet->getCell($columnString . ($row + 1));
            if (!$this->readDataOnly && isset($this->mapCellXfIndex[$xfIndex])) {
                                $cell->setXfIndex($this->mapCellXfIndex[$xfIndex]);
            }

                        $cell->setValueExplicit($numValue, DataType::TYPE_NUMERIC);
        }
    }

        private function readLabelSst(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

                $row = self::getUInt2d($recordData, 0);

                $column = self::getUInt2d($recordData, 2);
        $columnString = Coordinate::stringFromColumnIndex($column + 1);

        $cell = null;
                if (($this->getReadFilter() !== null) && $this->getReadFilter()->readCell($columnString, $row + 1, $this->phpSheet->getTitle())) {
                        $xfIndex = self::getUInt2d($recordData, 4);

                        $index = self::getInt4d($recordData, 6);

                        if (($fmtRuns = $this->sst[$index]['fmtRuns']) && !$this->readDataOnly) {
                                $richText = new RichText();
                $charPos = 0;
                $sstCount = count($this->sst[$index]['fmtRuns']);
                for ($i = 0; $i <= $sstCount; ++$i) {
                    if (isset($fmtRuns[$i])) {
                        $text = StringHelper::substring($this->sst[$index]['value'], $charPos, $fmtRuns[$i]['charPos'] - $charPos);
                        $charPos = $fmtRuns[$i]['charPos'];
                    } else {
                        $text = StringHelper::substring($this->sst[$index]['value'], $charPos, StringHelper::countCharacters($this->sst[$index]['value']));
                    }

                    if (StringHelper::countCharacters($text) > 0) {
                        if ($i == 0) {                             $richText->createText($text);
                        } else {
                            $textRun = $richText->createTextRun($text);
                            if (isset($fmtRuns[$i - 1])) {
                                if ($fmtRuns[$i - 1]['fontIndex'] < 4) {
                                    $fontIndex = $fmtRuns[$i - 1]['fontIndex'];
                                } else {
                                                                                                            $fontIndex = $fmtRuns[$i - 1]['fontIndex'] - 1;
                                }
                                if (array_key_exists($fontIndex, $this->objFonts) === false) {
                                    $fontIndex = count($this->objFonts) - 1;
                                }
                                $textRun->setFont(clone $this->objFonts[$fontIndex]);
                            }
                        }
                    }
                }
                if ($this->readEmptyCells || trim($richText->getPlainText()) !== '') {
                    $cell = $this->phpSheet->getCell($columnString . ($row + 1));
                    $cell->setValueExplicit($richText, DataType::TYPE_STRING);
                }
            } else {
                if ($this->readEmptyCells || trim($this->sst[$index]['value']) !== '') {
                    $cell = $this->phpSheet->getCell($columnString . ($row + 1));
                    $cell->setValueExplicit($this->sst[$index]['value'], DataType::TYPE_STRING);
                }
            }

            if (!$this->readDataOnly && $cell !== null && isset($this->mapCellXfIndex[$xfIndex])) {
                                $cell->setXfIndex($this->mapCellXfIndex[$xfIndex]);
            }
        }
    }

        private function readMulRk(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

                $row = self::getUInt2d($recordData, 0);

                $colFirst = self::getUInt2d($recordData, 2);

                $colLast = self::getUInt2d($recordData, $length - 2);
        $columns = $colLast - $colFirst + 1;

                $offset = 4;

        for ($i = 1; $i <= $columns; ++$i) {
            $columnString = Coordinate::stringFromColumnIndex($colFirst + $i);

                        if (($this->getReadFilter() !== null) && $this->getReadFilter()->readCell($columnString, $row + 1, $this->phpSheet->getTitle())) {
                                $xfIndex = self::getUInt2d($recordData, $offset);

                                $numValue = self::getIEEE754(self::getInt4d($recordData, $offset + 2));
                $cell = $this->phpSheet->getCell($columnString . ($row + 1));
                if (!$this->readDataOnly && isset($this->mapCellXfIndex[$xfIndex])) {
                                        $cell->setXfIndex($this->mapCellXfIndex[$xfIndex]);
                }

                                $cell->setValueExplicit($numValue, DataType::TYPE_NUMERIC);
            }

            $offset += 6;
        }
    }

        private function readNumber(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

                $row = self::getUInt2d($recordData, 0);

                $column = self::getUInt2d($recordData, 2);
        $columnString = Coordinate::stringFromColumnIndex($column + 1);

                if (($this->getReadFilter() !== null) && $this->getReadFilter()->readCell($columnString, $row + 1, $this->phpSheet->getTitle())) {
                        $xfIndex = self::getUInt2d($recordData, 4);

            $numValue = self::extractNumber(substr($recordData, 6, 8));

            $cell = $this->phpSheet->getCell($columnString . ($row + 1));
            if (!$this->readDataOnly && isset($this->mapCellXfIndex[$xfIndex])) {
                                $cell->setXfIndex($this->mapCellXfIndex[$xfIndex]);
            }

                        $cell->setValueExplicit($numValue, DataType::TYPE_NUMERIC);
        }
    }

        private function readFormula(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

                $row = self::getUInt2d($recordData, 0);

                $column = self::getUInt2d($recordData, 2);
        $columnString = Coordinate::stringFromColumnIndex($column + 1);

                $formulaStructure = substr($recordData, 20);

                $options = self::getUInt2d($recordData, 14);

                                $isPartOfSharedFormula = (bool) (0x0008 & $options);

                                        $isPartOfSharedFormula = $isPartOfSharedFormula && ord($formulaStructure[2]) == 0x01;

        if ($isPartOfSharedFormula) {
                                    $baseRow = self::getUInt2d($formulaStructure, 3);
            $baseCol = self::getUInt2d($formulaStructure, 5);
            $this->baseCell = Coordinate::stringFromColumnIndex($baseCol + 1) . ($baseRow + 1);
        }

                if (($this->getReadFilter() !== null) && $this->getReadFilter()->readCell($columnString, $row + 1, $this->phpSheet->getTitle())) {
            if ($isPartOfSharedFormula) {
                                $this->sharedFormulaParts[$columnString . ($row + 1)] = $this->baseCell;
            }

            
                        $xfIndex = self::getUInt2d($recordData, 4);

                        if ((ord($recordData[6]) == 0) && (ord($recordData[12]) == 255) && (ord($recordData[13]) == 255)) {
                                $dataType = DataType::TYPE_STRING;

                                $code = self::getUInt2d($this->data, $this->pos);
                if ($code == self::XLS_TYPE_SHAREDFMLA) {
                    $this->readSharedFmla();
                }

                                $value = $this->readString();
            } elseif (
                (ord($recordData[6]) == 1)
                && (ord($recordData[12]) == 255)
                && (ord($recordData[13]) == 255)
            ) {
                                $dataType = DataType::TYPE_BOOL;
                $value = (bool) ord($recordData[8]);
            } elseif (
                (ord($recordData[6]) == 2)
                && (ord($recordData[12]) == 255)
                && (ord($recordData[13]) == 255)
            ) {
                                $dataType = DataType::TYPE_ERROR;
                $value = Xls\ErrorCode::lookup(ord($recordData[8]));
            } elseif (
                (ord($recordData[6]) == 3)
                && (ord($recordData[12]) == 255)
                && (ord($recordData[13]) == 255)
            ) {
                                $dataType = DataType::TYPE_NULL;
                $value = '';
            } else {
                                $dataType = DataType::TYPE_NUMERIC;
                $value = self::extractNumber(substr($recordData, 6, 8));
            }

            $cell = $this->phpSheet->getCell($columnString . ($row + 1));
            if (!$this->readDataOnly && isset($this->mapCellXfIndex[$xfIndex])) {
                                $cell->setXfIndex($this->mapCellXfIndex[$xfIndex]);
            }

                        if (!$isPartOfSharedFormula) {
                                                try {
                    if ($this->version != self::XLS_BIFF8) {
                        throw new Exception('Not BIFF8. Can only read BIFF8 formulas');
                    }
                    $formula = $this->getFormulaFromStructure($formulaStructure);                     $cell->setValueExplicit('=' . $formula, DataType::TYPE_FORMULA);
                } catch (PhpSpreadsheetException) {
                    $cell->setValueExplicit($value, $dataType);
                }
            } else {
                if ($this->version == self::XLS_BIFF8) {
                                    } else {
                    $cell->setValueExplicit($value, $dataType);
                }
            }

                        $cell->setCalculatedValue($value, $dataType === DataType::TYPE_NUMERIC);
        }
    }

        private function readSharedFmla(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

                        
        
                
                $formula = substr($recordData, 8);

                $this->sharedFormulas[$this->baseCell] = $formula;
    }

        private function readString(): string
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        if ($this->version == self::XLS_BIFF8) {
            $string = self::readUnicodeStringLong($recordData);
            $value = $string['value'];
        } else {
            $string = $this->readByteStringLong($recordData);
            $value = $string['value'];
        }

        return $value;
    }

        private function readBoolErr(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

                $row = self::getUInt2d($recordData, 0);

                $column = self::getUInt2d($recordData, 2);
        $columnString = Coordinate::stringFromColumnIndex($column + 1);

                if (($this->getReadFilter() !== null) && $this->getReadFilter()->readCell($columnString, $row + 1, $this->phpSheet->getTitle())) {
                        $xfIndex = self::getUInt2d($recordData, 4);

                        $boolErr = ord($recordData[6]);

                        $isError = ord($recordData[7]);

            $cell = $this->phpSheet->getCell($columnString . ($row + 1));
            switch ($isError) {
                case 0:                     $value = (bool) $boolErr;

                                        $cell->setValueExplicit($value, DataType::TYPE_BOOL);

                    break;
                case 1:                     $value = Xls\ErrorCode::lookup($boolErr);

                                        $cell->setValueExplicit($value, DataType::TYPE_ERROR);

                    break;
            }

            if (!$this->readDataOnly && isset($this->mapCellXfIndex[$xfIndex])) {
                                $cell->setXfIndex($this->mapCellXfIndex[$xfIndex]);
            }
        }
    }

        private function readMulBlank(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

                $row = self::getUInt2d($recordData, 0);

                $fc = self::getUInt2d($recordData, 2);

                        if (!$this->readDataOnly && $this->readEmptyCells) {
            for ($i = 0; $i < $length / 2 - 3; ++$i) {
                $columnString = Coordinate::stringFromColumnIndex($fc + $i + 1);

                                if (($this->getReadFilter() !== null) && $this->getReadFilter()->readCell($columnString, $row + 1, $this->phpSheet->getTitle())) {
                    $xfIndex = self::getUInt2d($recordData, 4 + 2 * $i);
                    if (isset($this->mapCellXfIndex[$xfIndex])) {
                        $this->phpSheet->getCell($columnString . ($row + 1))->setXfIndex($this->mapCellXfIndex[$xfIndex]);
                    }
                }
            }
        }

            }

        private function readLabel(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

                $row = self::getUInt2d($recordData, 0);

                $column = self::getUInt2d($recordData, 2);
        $columnString = Coordinate::stringFromColumnIndex($column + 1);

                if (($this->getReadFilter() !== null) && $this->getReadFilter()->readCell($columnString, $row + 1, $this->phpSheet->getTitle())) {
                        $xfIndex = self::getUInt2d($recordData, 4);

                                    if ($this->version == self::XLS_BIFF8) {
                $string = self::readUnicodeStringLong(substr($recordData, 6));
                $value = $string['value'];
            } else {
                $string = $this->readByteStringLong(substr($recordData, 6));
                $value = $string['value'];
            }
            if ($this->readEmptyCells || trim($value) !== '') {
                $cell = $this->phpSheet->getCell($columnString . ($row + 1));
                $cell->setValueExplicit($value, DataType::TYPE_STRING);

                if (!$this->readDataOnly && isset($this->mapCellXfIndex[$xfIndex])) {
                                        $cell->setXfIndex($this->mapCellXfIndex[$xfIndex]);
                }
            }
        }
    }

        private function readBlank(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

                $row = self::getUInt2d($recordData, 0);

                $col = self::getUInt2d($recordData, 2);
        $columnString = Coordinate::stringFromColumnIndex($col + 1);

                if (($this->getReadFilter() !== null) && $this->getReadFilter()->readCell($columnString, $row + 1, $this->phpSheet->getTitle())) {
                        $xfIndex = self::getUInt2d($recordData, 4);

                        if (!$this->readDataOnly && $this->readEmptyCells && isset($this->mapCellXfIndex[$xfIndex])) {
                $this->phpSheet->getCell($columnString . ($row + 1))->setXfIndex($this->mapCellXfIndex[$xfIndex]);
            }
        }
    }

        private function readMsoDrawing(): void
    {
        
                $splicedRecordData = $this->getSplicedRecordData();
        $recordData = $splicedRecordData['recordData'];

        $this->drawingData .= $recordData;
    }

        private function readObj(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        if ($this->readDataOnly || $this->version != self::XLS_BIFF8) {
            return;
        }

                                                        
                $ftCmoType = self::getUInt2d($recordData, 0);
        $cbCmoSize = self::getUInt2d($recordData, 2);
        $otObjType = self::getUInt2d($recordData, 4);
        $idObjID = self::getUInt2d($recordData, 6);
        $grbitOpts = self::getUInt2d($recordData, 6);

        $this->objs[] = [
            'ftCmoType' => $ftCmoType,
            'cbCmoSize' => $cbCmoSize,
            'otObjType' => $otObjType,
            'idObjID' => $idObjID,
            'grbitOpts' => $grbitOpts,
        ];
        $this->textObjRef = $idObjID;
    }

        private function readWindow2(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

                $options = self::getUInt2d($recordData, 0);

                
                        $zoomscaleInPageBreakPreview = 0;
        $zoomscaleInNormalView = 0;
        if ($this->version === self::XLS_BIFF8) {
                                                            if (!isset($recordData[10])) {
                $zoomscaleInPageBreakPreview = 0;
            } else {
                $zoomscaleInPageBreakPreview = self::getUInt2d($recordData, 10);
            }

            if ($zoomscaleInPageBreakPreview === 0) {
                $zoomscaleInPageBreakPreview = 60;
            }

            if (!isset($recordData[12])) {
                $zoomscaleInNormalView = 0;
            } else {
                $zoomscaleInNormalView = self::getUInt2d($recordData, 12);
            }

            if ($zoomscaleInNormalView === 0) {
                $zoomscaleInNormalView = 100;
            }
        }

                $showGridlines = (bool) ((0x0002 & $options) >> 1);
        $this->phpSheet->setShowGridlines($showGridlines);

                $showRowColHeaders = (bool) ((0x0004 & $options) >> 2);
        $this->phpSheet->setShowRowColHeaders($showRowColHeaders);

                $this->frozen = (bool) ((0x0008 & $options) >> 3);

                $this->phpSheet->setRightToLeft((bool) ((0x0040 & $options) >> 6));

                $isActive = (bool) ((0x0400 & $options) >> 10);
        if ($isActive) {
            $this->spreadsheet->setActiveSheetIndex($this->spreadsheet->getIndex($this->phpSheet));
            $this->activeSheetSet = true;
        }

                $isPageBreakPreview = (bool) ((0x0800 & $options) >> 11);

        
        if ($this->phpSheet->getSheetView()->getView() !== SheetView::SHEETVIEW_PAGE_LAYOUT) {
                        $view = $isPageBreakPreview ? SheetView::SHEETVIEW_PAGE_BREAK_PREVIEW : SheetView::SHEETVIEW_NORMAL;
            $this->phpSheet->getSheetView()->setView($view);
            if ($this->version === self::XLS_BIFF8) {
                $zoomScale = $isPageBreakPreview ? $zoomscaleInPageBreakPreview : $zoomscaleInNormalView;
                $this->phpSheet->getSheetView()->setZoomScale($zoomScale);
                $this->phpSheet->getSheetView()->setZoomScaleNormal($zoomscaleInNormalView);
            }
        }
    }

        private function readPageLayoutView(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

                                                                
                $wScalePLV = self::getUInt2d($recordData, 12);
                $grbit = self::getUInt2d($recordData, 14);

                $fPageLayoutView = $grbit & 0x01;
                
        if ($fPageLayoutView === 1) {
            $this->phpSheet->getSheetView()->setView(SheetView::SHEETVIEW_PAGE_LAYOUT);
            $this->phpSheet->getSheetView()->setZoomScale($wScalePLV);         }
            }

        private function readScl(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

                $numerator = self::getUInt2d($recordData, 0);

                $denumerator = self::getUInt2d($recordData, 2);

                $this->phpSheet->getSheetView()->setZoomScale($numerator * 100 / $denumerator);
    }

        private function readPane(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
                        $px = self::getUInt2d($recordData, 0);

                        $py = self::getUInt2d($recordData, 2);

                        $rwTop = self::getUInt2d($recordData, 4);

                        $colLeft = self::getUInt2d($recordData, 6);

            if ($this->frozen) {
                                $cell = Coordinate::stringFromColumnIndex($px + 1) . ($py + 1);
                $topLeftCell = Coordinate::stringFromColumnIndex($colLeft + 1) . ($rwTop + 1);
                $this->phpSheet->freezePane($cell, $topLeftCell);
            }
                    }
    }

        private function readSelection(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
                        
                        
                        
                                    
                        $data = substr($recordData, 7);
            $cellRangeAddressList = $this->readBIFF5CellRangeAddressList($data); 
            $selectedCells = $cellRangeAddressList['cellRangeAddresses'][0];

                        if (preg_match('/^([A-Z]+1\:[A-Z]+)16384$/', $selectedCells)) {
                $selectedCells = (string) preg_replace('/^([A-Z]+1\:[A-Z]+)16384$/', '${1}1048576', $selectedCells);
            }

                        if (preg_match('/^([A-Z]+1\:[A-Z]+)65536$/', $selectedCells)) {
                $selectedCells = (string) preg_replace('/^([A-Z]+1\:[A-Z]+)65536$/', '${1}1048576', $selectedCells);
            }

                        if (preg_match('/^(A\d+\:)IV(\d+)$/', $selectedCells)) {
                $selectedCells = (string) preg_replace('/^(A\d+\:)IV(\d+)$/', '${1}XFD${2}', $selectedCells);
            }

            $this->phpSheet->setSelectedCells($selectedCells);
        }
    }

    private function includeCellRangeFiltered(string $cellRangeAddress): bool
    {
        $includeCellRange = true;
        if ($this->getReadFilter() !== null) {
            $includeCellRange = false;
            $rangeBoundaries = Coordinate::getRangeBoundaries($cellRangeAddress);
            ++$rangeBoundaries[1][0];
            for ($row = $rangeBoundaries[0][1]; $row <= $rangeBoundaries[1][1]; ++$row) {
                for ($column = $rangeBoundaries[0][0]; $column != $rangeBoundaries[1][0]; ++$column) {
                    if ($this->getReadFilter()->readCell($column, $row, $this->phpSheet->getTitle())) {
                        $includeCellRange = true;

                        break 2;
                    }
                }
            }
        }

        return $includeCellRange;
    }

        private function readMergedCells(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        if ($this->version == self::XLS_BIFF8 && !$this->readDataOnly) {
            $cellRangeAddressList = $this->readBIFF8CellRangeAddressList($recordData);
            foreach ($cellRangeAddressList['cellRangeAddresses'] as $cellRangeAddress) {
                if (
                    (str_contains($cellRangeAddress, ':'))
                    && ($this->includeCellRangeFiltered($cellRangeAddress))
                ) {
                    $this->phpSheet->mergeCells($cellRangeAddress, Worksheet::MERGE_CELL_CONTENT_HIDE);
                }
            }
        }
    }

        private function readHyperLink(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
                        try {
                $cellRange = $this->readBIFF8CellRangeAddressFixed($recordData);
            } catch (PhpSpreadsheetException) {
                return;
            }

            
            
                                    $isFileLinkOrUrl = (0x00000001 & self::getUInt2d($recordData, 28)) >> 0;

                        
                        $hasDesc = (0x00000014 & self::getUInt2d($recordData, 28)) >> 2;

                        $hasText = (0x00000008 & self::getUInt2d($recordData, 28)) >> 3;

                        $hasFrame = (0x00000080 & self::getUInt2d($recordData, 28)) >> 7;

                        $isUNC = (0x00000100 & self::getUInt2d($recordData, 28)) >> 8;

                        $offset = 32;

            if ($hasDesc) {
                                $dl = self::getInt4d($recordData, 32);
                                                $offset += 4 + 2 * $dl;
            }
            if ($hasFrame) {
                $fl = self::getInt4d($recordData, $offset);
                $offset += 4 + 2 * $fl;
            }

                        $hyperlinkType = null;

            if ($isUNC) {
                $hyperlinkType = 'UNC';
            } elseif (!$isFileLinkOrUrl) {
                $hyperlinkType = 'workbook';
            } elseif (ord($recordData[$offset]) == 0x03) {
                $hyperlinkType = 'local';
            } elseif (ord($recordData[$offset]) == 0xE0) {
                $hyperlinkType = 'URL';
            }

            switch ($hyperlinkType) {
                case 'URL':
                                        
                                        $offset += 16;
                                        $us = self::getInt4d($recordData, $offset);
                    $offset += 4;
                                        $url = self::encodeUTF16(substr($recordData, $offset, $us - 2), false);
                    $nullOffset = strpos($url, chr(0x00));
                    if ($nullOffset) {
                        $url = substr($url, 0, $nullOffset);
                    }
                    $url .= $hasText ? '#' : '';
                    $offset += $us;

                    break;
                case 'local':
                                                                                
                                        $offset += 16;

                                        $upLevelCount = self::getUInt2d($recordData, $offset);
                    $offset += 2;

                                        $sl = self::getInt4d($recordData, $offset);
                    $offset += 4;

                                        $shortenedFilePath = substr($recordData, $offset, $sl);
                    $shortenedFilePath = self::encodeUTF16($shortenedFilePath, true);
                    $shortenedFilePath = substr($shortenedFilePath, 0, -1); 
                    $offset += $sl;

                                        $offset += 24;

                                                            $sz = self::getInt4d($recordData, $offset);
                    $offset += 4;

                    $extendedFilePath = '';
                                        if ($sz > 0) {
                                                $xl = self::getInt4d($recordData, $offset);
                        $offset += 4;

                                                $offset += 2;

                                                $extendedFilePath = substr($recordData, $offset, $xl);
                        $extendedFilePath = self::encodeUTF16($extendedFilePath, false);
                        $offset += $xl;
                    }

                                        $url = str_repeat('..\\', $upLevelCount);
                    $url .= ($sz > 0) ? $extendedFilePath : $shortenedFilePath;                     $url .= $hasText ? '#' : '';

                    break;
                case 'UNC':
                                                            return;
                case 'workbook':
                                                            $url = 'sheet:
                    break;
                default:
                    return;
            }

            if ($hasText) {
                                $tl = self::getInt4d($recordData, $offset);
                $offset += 4;
                                $text = self::encodeUTF16(substr($recordData, $offset, 2 * ($tl - 1)), false);
                $url .= $text;
            }

                        foreach (Coordinate::extractAllCellReferencesInRange($cellRange) as $coordinate) {
                $this->phpSheet->getCell($coordinate)->getHyperLink()->setUrl($url);
            }
        }
    }

        private function readDataValidations(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        
                $this->pos += 4 + $length;
    }

        private function readDataValidation(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        if ($this->readDataOnly) {
            return;
        }

                $options = self::getInt4d($recordData, 0);

                $type = (0x0000000F & $options) >> 0;
        $type = Xls\DataValidationHelper::type($type);

                $errorStyle = (0x00000070 & $options) >> 4;
        $errorStyle = Xls\DataValidationHelper::errorStyle($errorStyle);

                        
                $allowBlank = (0x00000100 & $options) >> 8;

                $suppressDropDown = (0x00000200 & $options) >> 9;

                $showInputMessage = (0x00040000 & $options) >> 18;

                $showErrorMessage = (0x00080000 & $options) >> 19;

                $operator = (0x00F00000 & $options) >> 20;
        $operator = Xls\DataValidationHelper::operator($operator);

        if ($type === null || $errorStyle === null || $operator === null) {
            return;
        }

                $offset = 4;
        $string = self::readUnicodeStringLong(substr($recordData, $offset));
        $promptTitle = $string['value'] !== chr(0) ? $string['value'] : '';
        $offset += $string['size'];

                $string = self::readUnicodeStringLong(substr($recordData, $offset));
        $errorTitle = $string['value'] !== chr(0) ? $string['value'] : '';
        $offset += $string['size'];

                $string = self::readUnicodeStringLong(substr($recordData, $offset));
        $prompt = $string['value'] !== chr(0) ? $string['value'] : '';
        $offset += $string['size'];

                $string = self::readUnicodeStringLong(substr($recordData, $offset));
        $error = $string['value'] !== chr(0) ? $string['value'] : '';
        $offset += $string['size'];

                $sz1 = self::getUInt2d($recordData, $offset);
        $offset += 2;

                $offset += 2;

                $formula1 = substr($recordData, $offset, $sz1);
        $formula1 = pack('v', $sz1) . $formula1; 
        try {
            $formula1 = $this->getFormulaFromStructure($formula1);

                        if ($type == DataValidation::TYPE_LIST) {
                $formula1 = str_replace(chr(0), ',', $formula1);
            }
        } catch (PhpSpreadsheetException $e) {
            return;
        }
        $offset += $sz1;

                $sz2 = self::getUInt2d($recordData, $offset);
        $offset += 2;

                $offset += 2;

                $formula2 = substr($recordData, $offset, $sz2);
        $formula2 = pack('v', $sz2) . $formula2; 
        try {
            $formula2 = $this->getFormulaFromStructure($formula2);
        } catch (PhpSpreadsheetException) {
            return;
        }
        $offset += $sz2;

                $cellRangeAddressList = $this->readBIFF8CellRangeAddressList(substr($recordData, $offset));
        $cellRangeAddresses = $cellRangeAddressList['cellRangeAddresses'];

        foreach ($cellRangeAddresses as $cellRange) {
            $stRange = $this->phpSheet->shrinkRangeToFit($cellRange);
            foreach (Coordinate::extractAllCellReferencesInRange($stRange) as $coordinate) {
                $objValidation = $this->phpSheet->getCell($coordinate)->getDataValidation();
                $objValidation->setType($type);
                $objValidation->setErrorStyle($errorStyle);
                $objValidation->setAllowBlank((bool) $allowBlank);
                $objValidation->setShowInputMessage((bool) $showInputMessage);
                $objValidation->setShowErrorMessage((bool) $showErrorMessage);
                $objValidation->setShowDropDown(!$suppressDropDown);
                $objValidation->setOperator($operator);
                $objValidation->setErrorTitle($errorTitle);
                $objValidation->setError($error);
                $objValidation->setPromptTitle($promptTitle);
                $objValidation->setPrompt($prompt);
                $objValidation->setFormula1($formula1);
                $objValidation->setFormula2($formula2);
            }
        }
    }

        private function readSheetLayout(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        if (!$this->readDataOnly) {
            
            
                                    $sz = self::getInt4d($recordData, 12);

            switch ($sz) {
                case 0x14:
                                        $colorIndex = self::getUInt2d($recordData, 16);
                    $color = Xls\Color::map($colorIndex, $this->palette, $this->version);
                    $this->phpSheet->getTabColor()->setRGB($color['rgb']);

                    break;
                case 0x28:
                                        return;
            }
        }
    }

        private function readSheetProtection(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        if ($this->readDataOnly) {
            return;
        }

        
        
        
                $isf = self::getUInt2d($recordData, 12);
        if ($isf != 2) {
            return;
        }

        
        
                        $options = self::getUInt2d($recordData, 19);

                        $bool = (0x0001 & $options) >> 0;
        $this->phpSheet->getProtection()->setObjects((bool) $bool);

                        $bool = (0x0002 & $options) >> 1;
        $this->phpSheet->getProtection()->setScenarios((bool) $bool);

                $bool = (0x0004 & $options) >> 2;
        $this->phpSheet->getProtection()->setFormatCells(!$bool);

                $bool = (0x0008 & $options) >> 3;
        $this->phpSheet->getProtection()->setFormatColumns(!$bool);

                $bool = (0x0010 & $options) >> 4;
        $this->phpSheet->getProtection()->setFormatRows(!$bool);

                $bool = (0x0020 & $options) >> 5;
        $this->phpSheet->getProtection()->setInsertColumns(!$bool);

                $bool = (0x0040 & $options) >> 6;
        $this->phpSheet->getProtection()->setInsertRows(!$bool);

                $bool = (0x0080 & $options) >> 7;
        $this->phpSheet->getProtection()->setInsertHyperlinks(!$bool);

                $bool = (0x0100 & $options) >> 8;
        $this->phpSheet->getProtection()->setDeleteColumns(!$bool);

                $bool = (0x0200 & $options) >> 9;
        $this->phpSheet->getProtection()->setDeleteRows(!$bool);

                        $bool = (0x0400 & $options) >> 10;
        $this->phpSheet->getProtection()->setSelectLockedCells((bool) $bool);

                $bool = (0x0800 & $options) >> 11;
        $this->phpSheet->getProtection()->setSort(!$bool);

                $bool = (0x1000 & $options) >> 12;
        $this->phpSheet->getProtection()->setAutoFilter(!$bool);

                $bool = (0x2000 & $options) >> 13;
        $this->phpSheet->getProtection()->setPivotTables(!$bool);

                        $bool = (0x4000 & $options) >> 14;
        $this->phpSheet->getProtection()->setSelectUnlockedCells((bool) $bool);

            }

        private function readRangeProtection(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

                $offset = 0;

        if (!$this->readDataOnly) {
            $offset += 12;

                        $isf = self::getUInt2d($recordData, 12);
            if ($isf != 2) {
                                return;
            }
            $offset += 2;

            $offset += 5;

                        $cref = self::getUInt2d($recordData, 19);
            $offset += 2;

            $offset += 6;

                        $cellRanges = [];
            for ($i = 0; $i < $cref; ++$i) {
                try {
                    $cellRange = $this->readBIFF8CellRangeAddressFixed(substr($recordData, 27 + 8 * $i, 8));
                } catch (PhpSpreadsheetException) {
                    return;
                }
                $cellRanges[] = $cellRange;
                $offset += 8;
            }

                                    $offset += 4;

                        $wPassword = self::getInt4d($recordData, $offset);
            $offset += 4;

                        if ($cellRanges) {
                $this->phpSheet->protectCells(implode(' ', $cellRanges), strtoupper(dechex($wPassword)), true);
            }
        }
    }

        private function readContinue(): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                        if ($this->drawingData == '') {
                        $this->pos += 4 + $length;

            return;
        }

                if ($length < 4) {
                        $this->pos += 4 + $length;

            return;
        }

                                                        $validSplitPoints = [0xF003, 0xF004, 0xF00D]; 
        $splitPoint = self::getUInt2d($recordData, 2);
        if (in_array($splitPoint, $validSplitPoints)) {
                        $splicedRecordData = $this->getSplicedRecordData();
            $this->drawingData .= $splicedRecordData['recordData'];

            return;
        }

                $this->pos += 4 + $length;
    }

        private function getSplicedRecordData(): array
    {
        $data = '';
        $spliceOffsets = [];

        $i = 0;
        $spliceOffsets[0] = 0;

        do {
            ++$i;

                                                $length = self::getUInt2d($this->data, $this->pos + 2);
            $data .= $this->readRecordData($this->data, $this->pos + 4, $length);

            $spliceOffsets[$i] = $spliceOffsets[$i - 1] + $length;

            $this->pos += 4 + $length;
            $nextIdentifier = self::getUInt2d($this->data, $this->pos);
        } while ($nextIdentifier == self::XLS_TYPE_CONTINUE);

        return [
            'recordData' => $data,
            'spliceOffsets' => $spliceOffsets,
        ];
    }

        private function getFormulaFromStructure(string $formulaStructure, string $baseCell = 'A1'): string
    {
                $sz = self::getUInt2d($formulaStructure, 0);

                $formulaData = substr($formulaStructure, 2, $sz);

                if (strlen($formulaStructure) > 2 + $sz) {
            $additionalData = substr($formulaStructure, 2 + $sz);
        } else {
            $additionalData = '';
        }

        return $this->getFormulaFromData($formulaData, $additionalData, $baseCell);
    }

        private function getFormulaFromData(string $formulaData, string $additionalData = '', string $baseCell = 'A1'): string
    {
                $tokens = [];

        while ($formulaData !== '' && $token = $this->getNextToken($formulaData, $baseCell)) {
            $tokens[] = $token;
            $formulaData = substr($formulaData, $token['size']);
        }

        $formulaString = $this->createFormulaFromTokens($tokens, $additionalData);

        return $formulaString;
    }

        private function createFormulaFromTokens(array $tokens, string $additionalData): string
    {
                if (empty($tokens)) {
            return '';
        }

        $formulaStrings = [];
        foreach ($tokens as $token) {
                        $space0 = $space0 ?? '';             $space1 = $space1 ?? '';             $space2 = $space2 ?? '';             $space3 = $space3 ?? '';             $space4 = $space4 ?? '';             $space5 = $space5 ?? ''; 
            switch ($token['name']) {
                case 'tAdd':                 case 'tConcat':                 case 'tDiv':                 case 'tEQ':                 case 'tGE':                 case 'tGT':                 case 'tIsect':                 case 'tLE':                 case 'tList':                 case 'tLT':                 case 'tMul':                 case 'tNE':                 case 'tPower':                 case 'tRange':                 case 'tSub':                     $op2 = array_pop($formulaStrings);
                    $op1 = array_pop($formulaStrings);
                    $formulaStrings[] = "$op1$space1$space0{$token['data']}$op2";
                    unset($space0, $space1);

                    break;
                case 'tUplus':                 case 'tUminus':                     $op = array_pop($formulaStrings);
                    $formulaStrings[] = "$space1$space0{$token['data']}$op";
                    unset($space0, $space1);

                    break;
                case 'tPercent':                     $op = array_pop($formulaStrings);
                    $formulaStrings[] = "$op$space1$space0{$token['data']}";
                    unset($space0, $space1);

                    break;
                case 'tAttrVolatile':                 case 'tAttrIf':
                case 'tAttrSkip':
                case 'tAttrChoose':
                                                            break;
                case 'tAttrSpace':                                         switch ($token['data']['spacetype']) {
                        case 'type0':
                            $space0 = str_repeat(' ', $token['data']['spacecount']);

                            break;
                        case 'type1':
                            $space1 = str_repeat("\n", $token['data']['spacecount']);

                            break;
                        case 'type2':
                            $space2 = str_repeat(' ', $token['data']['spacecount']);

                            break;
                        case 'type3':
                            $space3 = str_repeat("\n", $token['data']['spacecount']);

                            break;
                        case 'type4':
                            $space4 = str_repeat(' ', $token['data']['spacecount']);

                            break;
                        case 'type5':
                            $space5 = str_repeat("\n", $token['data']['spacecount']);

                            break;
                    }

                    break;
                case 'tAttrSum':                     $op = array_pop($formulaStrings);
                    $formulaStrings[] = "{$space1}{$space0}SUM($op)";
                    unset($space0, $space1);

                    break;
                case 'tFunc':                 case 'tFuncV':                     if ($token['data']['function'] != '') {
                                                $ops = [];                         for ($i = 0; $i < $token['data']['args']; ++$i) {
                            $ops[] = array_pop($formulaStrings);
                        }
                        $ops = array_reverse($ops);
                        $formulaStrings[] = "$space1$space0{$token['data']['function']}(" . implode(',', $ops) . ')';
                        unset($space0, $space1);
                    } else {
                                                $ops = [];                         for ($i = 0; $i < $token['data']['args'] - 1; ++$i) {
                            $ops[] = array_pop($formulaStrings);
                        }
                        $ops = array_reverse($ops);
                        $function = array_pop($formulaStrings);
                        $formulaStrings[] = "$space1$space0$function(" . implode(',', $ops) . ')';
                        unset($space0, $space1);
                    }

                    break;
                case 'tParen':                     $expression = array_pop($formulaStrings);
                    $formulaStrings[] = "$space3$space2($expression$space5$space4)";
                    unset($space2, $space3, $space4, $space5);

                    break;
                case 'tArray':                     $constantArray = self::readBIFF8ConstantArray($additionalData);
                    $formulaStrings[] = $space1 . $space0 . $constantArray['value'];
                    $additionalData = substr($additionalData, $constantArray['size']);                     unset($space0, $space1);

                    break;
                case 'tMemArea':
                                        $cellRangeAddressList = $this->readBIFF8CellRangeAddressList($additionalData);
                    $additionalData = substr($additionalData, $cellRangeAddressList['size']);
                    $formulaStrings[] = "$space1$space0{$token['data']}";
                    unset($space0, $space1);

                    break;
                case 'tArea':                 case 'tBool':                 case 'tErr':                 case 'tInt':                 case 'tMemErr':
                case 'tMemFunc':
                case 'tMissArg':
                case 'tName':
                case 'tNameX':
                case 'tNum':                 case 'tRef':                 case 'tRef3d':                 case 'tArea3d':                 case 'tRefN':
                case 'tAreaN':
                case 'tStr':                     $formulaStrings[] = "$space1$space0{$token['data']}";
                    unset($space0, $space1);

                    break;
            }
        }
        $formulaString = $formulaStrings[0];

        return $formulaString;
    }

        private function getNextToken(string $formulaData, string $baseCell = 'A1'): array
    {
                $id = ord($formulaData[0]);         $name = false; 
        switch ($id) {
            case 0x03:
                $name = 'tAdd';
                $size = 1;
                $data = '+';

                break;
            case 0x04:
                $name = 'tSub';
                $size = 1;
                $data = '-';

                break;
            case 0x05:
                $name = 'tMul';
                $size = 1;
                $data = '*';

                break;
            case 0x06:
                $name = 'tDiv';
                $size = 1;
                $data = '/';

                break;
            case 0x07:
                $name = 'tPower';
                $size = 1;
                $data = '^';

                break;
            case 0x08:
                $name = 'tConcat';
                $size = 1;
                $data = '&';

                break;
            case 0x09:
                $name = 'tLT';
                $size = 1;
                $data = '<';

                break;
            case 0x0A:
                $name = 'tLE';
                $size = 1;
                $data = '<=';

                break;
            case 0x0B:
                $name = 'tEQ';
                $size = 1;
                $data = '=';

                break;
            case 0x0C:
                $name = 'tGE';
                $size = 1;
                $data = '>=';

                break;
            case 0x0D:
                $name = 'tGT';
                $size = 1;
                $data = '>';

                break;
            case 0x0E:
                $name = 'tNE';
                $size = 1;
                $data = '<>';

                break;
            case 0x0F:
                $name = 'tIsect';
                $size = 1;
                $data = ' ';

                break;
            case 0x10:
                $name = 'tList';
                $size = 1;
                $data = ',';

                break;
            case 0x11:
                $name = 'tRange';
                $size = 1;
                $data = ':';

                break;
            case 0x12:
                $name = 'tUplus';
                $size = 1;
                $data = '+';

                break;
            case 0x13:
                $name = 'tUminus';
                $size = 1;
                $data = '-';

                break;
            case 0x14:
                $name = 'tPercent';
                $size = 1;
                $data = '%';

                break;
            case 0x15:                    $name = 'tParen';
                $size = 1;
                $data = null;

                break;
            case 0x16:                    $name = 'tMissArg';
                $size = 1;
                $data = '';

                break;
            case 0x17:                    $name = 'tStr';
                                $string = self::readUnicodeStringShort(substr($formulaData, 1));
                $size = 1 + $string['size'];
                $data = self::UTF8toExcelDoubleQuoted($string['value']);

                break;
            case 0x19:                                    switch (ord($formulaData[1])) {
                    case 0x01:
                        $name = 'tAttrVolatile';
                        $size = 4;
                        $data = null;

                        break;
                    case 0x02:
                        $name = 'tAttrIf';
                        $size = 4;
                        $data = null;

                        break;
                    case 0x04:
                        $name = 'tAttrChoose';
                                                $nc = self::getUInt2d($formulaData, 2);
                                                                        $size = 2 * $nc + 6;
                        $data = null;

                        break;
                    case 0x08:
                        $name = 'tAttrSkip';
                        $size = 4;
                        $data = null;

                        break;
                    case 0x10:
                        $name = 'tAttrSum';
                        $size = 4;
                        $data = null;

                        break;
                    case 0x40:
                    case 0x41:
                        $name = 'tAttrSpace';
                        $size = 4;
                                                $spacetype = match (ord($formulaData[2])) {
                            0x00 => 'type0',
                            0x01 => 'type1',
                            0x02 => 'type2',
                            0x03 => 'type3',
                            0x04 => 'type4',
                            0x05 => 'type5',
                            default => throw new Exception('Unrecognized space type in tAttrSpace token'),
                        };
                                                $spacecount = ord($formulaData[3]);

                        $data = ['spacetype' => $spacetype, 'spacecount' => $spacecount];

                        break;
                    default:
                        throw new Exception('Unrecognized attribute flag in tAttr token');
                }

                break;
            case 0x1C:                                    $name = 'tErr';
                $size = 2;
                $data = Xls\ErrorCode::lookup(ord($formulaData[1]));

                break;
            case 0x1D:                                    $name = 'tBool';
                $size = 2;
                $data = ord($formulaData[1]) ? 'TRUE' : 'FALSE';

                break;
            case 0x1E:                                    $name = 'tInt';
                $size = 3;
                $data = self::getUInt2d($formulaData, 1);

                break;
            case 0x1F:                                    $name = 'tNum';
                $size = 9;
                $data = self::extractNumber(substr($formulaData, 1));
                $data = str_replace(',', '.', (string) $data); 
                break;
            case 0x20:                case 0x40:
            case 0x60:
                                $name = 'tArray';
                $size = 8;
                $data = null;

                break;
            case 0x21:                case 0x41:
            case 0x61:
                $name = 'tFunc';
                $size = 3;
                                switch (self::getUInt2d($formulaData, 1)) {
                    case 2:
                        $function = 'ISNA';
                        $args = 1;

                        break;
                    case 3:
                        $function = 'ISERROR';
                        $args = 1;

                        break;
                    case 10:
                        $function = 'NA';
                        $args = 0;

                        break;
                    case 15:
                        $function = 'SIN';
                        $args = 1;

                        break;
                    case 16:
                        $function = 'COS';
                        $args = 1;

                        break;
                    case 17:
                        $function = 'TAN';
                        $args = 1;

                        break;
                    case 18:
                        $function = 'ATAN';
                        $args = 1;

                        break;
                    case 19:
                        $function = 'PI';
                        $args = 0;

                        break;
                    case 20:
                        $function = 'SQRT';
                        $args = 1;

                        break;
                    case 21:
                        $function = 'EXP';
                        $args = 1;

                        break;
                    case 22:
                        $function = 'LN';
                        $args = 1;

                        break;
                    case 23:
                        $function = 'LOG10';
                        $args = 1;

                        break;
                    case 24:
                        $function = 'ABS';
                        $args = 1;

                        break;
                    case 25:
                        $function = 'INT';
                        $args = 1;

                        break;
                    case 26:
                        $function = 'SIGN';
                        $args = 1;

                        break;
                    case 27:
                        $function = 'ROUND';
                        $args = 2;

                        break;
                    case 30:
                        $function = 'REPT';
                        $args = 2;

                        break;
                    case 31:
                        $function = 'MID';
                        $args = 3;

                        break;
                    case 32:
                        $function = 'LEN';
                        $args = 1;

                        break;
                    case 33:
                        $function = 'VALUE';
                        $args = 1;

                        break;
                    case 34:
                        $function = 'TRUE';
                        $args = 0;

                        break;
                    case 35:
                        $function = 'FALSE';
                        $args = 0;

                        break;
                    case 38:
                        $function = 'NOT';
                        $args = 1;

                        break;
                    case 39:
                        $function = 'MOD';
                        $args = 2;

                        break;
                    case 40:
                        $function = 'DCOUNT';
                        $args = 3;

                        break;
                    case 41:
                        $function = 'DSUM';
                        $args = 3;

                        break;
                    case 42:
                        $function = 'DAVERAGE';
                        $args = 3;

                        break;
                    case 43:
                        $function = 'DMIN';
                        $args = 3;

                        break;
                    case 44:
                        $function = 'DMAX';
                        $args = 3;

                        break;
                    case 45:
                        $function = 'DSTDEV';
                        $args = 3;

                        break;
                    case 48:
                        $function = 'TEXT';
                        $args = 2;

                        break;
                    case 61:
                        $function = 'MIRR';
                        $args = 3;

                        break;
                    case 63:
                        $function = 'RAND';
                        $args = 0;

                        break;
                    case 65:
                        $function = 'DATE';
                        $args = 3;

                        break;
                    case 66:
                        $function = 'TIME';
                        $args = 3;

                        break;
                    case 67:
                        $function = 'DAY';
                        $args = 1;

                        break;
                    case 68:
                        $function = 'MONTH';
                        $args = 1;

                        break;
                    case 69:
                        $function = 'YEAR';
                        $args = 1;

                        break;
                    case 71:
                        $function = 'HOUR';
                        $args = 1;

                        break;
                    case 72:
                        $function = 'MINUTE';
                        $args = 1;

                        break;
                    case 73:
                        $function = 'SECOND';
                        $args = 1;

                        break;
                    case 74:
                        $function = 'NOW';
                        $args = 0;

                        break;
                    case 75:
                        $function = 'AREAS';
                        $args = 1;

                        break;
                    case 76:
                        $function = 'ROWS';
                        $args = 1;

                        break;
                    case 77:
                        $function = 'COLUMNS';
                        $args = 1;

                        break;
                    case 83:
                        $function = 'TRANSPOSE';
                        $args = 1;

                        break;
                    case 86:
                        $function = 'TYPE';
                        $args = 1;

                        break;
                    case 97:
                        $function = 'ATAN2';
                        $args = 2;

                        break;
                    case 98:
                        $function = 'ASIN';
                        $args = 1;

                        break;
                    case 99:
                        $function = 'ACOS';
                        $args = 1;

                        break;
                    case 105:
                        $function = 'ISREF';
                        $args = 1;

                        break;
                    case 111:
                        $function = 'CHAR';
                        $args = 1;

                        break;
                    case 112:
                        $function = 'LOWER';
                        $args = 1;

                        break;
                    case 113:
                        $function = 'UPPER';
                        $args = 1;

                        break;
                    case 114:
                        $function = 'PROPER';
                        $args = 1;

                        break;
                    case 117:
                        $function = 'EXACT';
                        $args = 2;

                        break;
                    case 118:
                        $function = 'TRIM';
                        $args = 1;

                        break;
                    case 119:
                        $function = 'REPLACE';
                        $args = 4;

                        break;
                    case 121:
                        $function = 'CODE';
                        $args = 1;

                        break;
                    case 126:
                        $function = 'ISERR';
                        $args = 1;

                        break;
                    case 127:
                        $function = 'ISTEXT';
                        $args = 1;

                        break;
                    case 128:
                        $function = 'ISNUMBER';
                        $args = 1;

                        break;
                    case 129:
                        $function = 'ISBLANK';
                        $args = 1;

                        break;
                    case 130:
                        $function = 'T';
                        $args = 1;

                        break;
                    case 131:
                        $function = 'N';
                        $args = 1;

                        break;
                    case 140:
                        $function = 'DATEVALUE';
                        $args = 1;

                        break;
                    case 141:
                        $function = 'TIMEVALUE';
                        $args = 1;

                        break;
                    case 142:
                        $function = 'SLN';
                        $args = 3;

                        break;
                    case 143:
                        $function = 'SYD';
                        $args = 4;

                        break;
                    case 162:
                        $function = 'CLEAN';
                        $args = 1;

                        break;
                    case 163:
                        $function = 'MDETERM';
                        $args = 1;

                        break;
                    case 164:
                        $function = 'MINVERSE';
                        $args = 1;

                        break;
                    case 165:
                        $function = 'MMULT';
                        $args = 2;

                        break;
                    case 184:
                        $function = 'FACT';
                        $args = 1;

                        break;
                    case 189:
                        $function = 'DPRODUCT';
                        $args = 3;

                        break;
                    case 190:
                        $function = 'ISNONTEXT';
                        $args = 1;

                        break;
                    case 195:
                        $function = 'DSTDEVP';
                        $args = 3;

                        break;
                    case 196:
                        $function = 'DVARP';
                        $args = 3;

                        break;
                    case 198:
                        $function = 'ISLOGICAL';
                        $args = 1;

                        break;
                    case 199:
                        $function = 'DCOUNTA';
                        $args = 3;

                        break;
                    case 207:
                        $function = 'REPLACEB';
                        $args = 4;

                        break;
                    case 210:
                        $function = 'MIDB';
                        $args = 3;

                        break;
                    case 211:
                        $function = 'LENB';
                        $args = 1;

                        break;
                    case 212:
                        $function = 'ROUNDUP';
                        $args = 2;

                        break;
                    case 213:
                        $function = 'ROUNDDOWN';
                        $args = 2;

                        break;
                    case 214:
                        $function = 'ASC';
                        $args = 1;

                        break;
                    case 215:
                        $function = 'DBCS';
                        $args = 1;

                        break;
                    case 221:
                        $function = 'TODAY';
                        $args = 0;

                        break;
                    case 229:
                        $function = 'SINH';
                        $args = 1;

                        break;
                    case 230:
                        $function = 'COSH';
                        $args = 1;

                        break;
                    case 231:
                        $function = 'TANH';
                        $args = 1;

                        break;
                    case 232:
                        $function = 'ASINH';
                        $args = 1;

                        break;
                    case 233:
                        $function = 'ACOSH';
                        $args = 1;

                        break;
                    case 234:
                        $function = 'ATANH';
                        $args = 1;

                        break;
                    case 235:
                        $function = 'DGET';
                        $args = 3;

                        break;
                    case 244:
                        $function = 'INFO';
                        $args = 1;

                        break;
                    case 252:
                        $function = 'FREQUENCY';
                        $args = 2;

                        break;
                    case 261:
                        $function = 'ERROR.TYPE';
                        $args = 1;

                        break;
                    case 271:
                        $function = 'GAMMALN';
                        $args = 1;

                        break;
                    case 273:
                        $function = 'BINOMDIST';
                        $args = 4;

                        break;
                    case 274:
                        $function = 'CHIDIST';
                        $args = 2;

                        break;
                    case 275:
                        $function = 'CHIINV';
                        $args = 2;

                        break;
                    case 276:
                        $function = 'COMBIN';
                        $args = 2;

                        break;
                    case 277:
                        $function = 'CONFIDENCE';
                        $args = 3;

                        break;
                    case 278:
                        $function = 'CRITBINOM';
                        $args = 3;

                        break;
                    case 279:
                        $function = 'EVEN';
                        $args = 1;

                        break;
                    case 280:
                        $function = 'EXPONDIST';
                        $args = 3;

                        break;
                    case 281:
                        $function = 'FDIST';
                        $args = 3;

                        break;
                    case 282:
                        $function = 'FINV';
                        $args = 3;

                        break;
                    case 283:
                        $function = 'FISHER';
                        $args = 1;

                        break;
                    case 284:
                        $function = 'FISHERINV';
                        $args = 1;

                        break;
                    case 285:
                        $function = 'FLOOR';
                        $args = 2;

                        break;
                    case 286:
                        $function = 'GAMMADIST';
                        $args = 4;

                        break;
                    case 287:
                        $function = 'GAMMAINV';
                        $args = 3;

                        break;
                    case 288:
                        $function = 'CEILING';
                        $args = 2;

                        break;
                    case 289:
                        $function = 'HYPGEOMDIST';
                        $args = 4;

                        break;
                    case 290:
                        $function = 'LOGNORMDIST';
                        $args = 3;

                        break;
                    case 291:
                        $function = 'LOGINV';
                        $args = 3;

                        break;
                    case 292:
                        $function = 'NEGBINOMDIST';
                        $args = 3;

                        break;
                    case 293:
                        $function = 'NORMDIST';
                        $args = 4;

                        break;
                    case 294:
                        $function = 'NORMSDIST';
                        $args = 1;

                        break;
                    case 295:
                        $function = 'NORMINV';
                        $args = 3;

                        break;
                    case 296:
                        $function = 'NORMSINV';
                        $args = 1;

                        break;
                    case 297:
                        $function = 'STANDARDIZE';
                        $args = 3;

                        break;
                    case 298:
                        $function = 'ODD';
                        $args = 1;

                        break;
                    case 299:
                        $function = 'PERMUT';
                        $args = 2;

                        break;
                    case 300:
                        $function = 'POISSON';
                        $args = 3;

                        break;
                    case 301:
                        $function = 'TDIST';
                        $args = 3;

                        break;
                    case 302:
                        $function = 'WEIBULL';
                        $args = 4;

                        break;
                    case 303:
                        $function = 'SUMXMY2';
                        $args = 2;

                        break;
                    case 304:
                        $function = 'SUMX2MY2';
                        $args = 2;

                        break;
                    case 305:
                        $function = 'SUMX2PY2';
                        $args = 2;

                        break;
                    case 306:
                        $function = 'CHITEST';
                        $args = 2;

                        break;
                    case 307:
                        $function = 'CORREL';
                        $args = 2;

                        break;
                    case 308:
                        $function = 'COVAR';
                        $args = 2;

                        break;
                    case 309:
                        $function = 'FORECAST';
                        $args = 3;

                        break;
                    case 310:
                        $function = 'FTEST';
                        $args = 2;

                        break;
                    case 311:
                        $function = 'INTERCEPT';
                        $args = 2;

                        break;
                    case 312:
                        $function = 'PEARSON';
                        $args = 2;

                        break;
                    case 313:
                        $function = 'RSQ';
                        $args = 2;

                        break;
                    case 314:
                        $function = 'STEYX';
                        $args = 2;

                        break;
                    case 315:
                        $function = 'SLOPE';
                        $args = 2;

                        break;
                    case 316:
                        $function = 'TTEST';
                        $args = 4;

                        break;
                    case 325:
                        $function = 'LARGE';
                        $args = 2;

                        break;
                    case 326:
                        $function = 'SMALL';
                        $args = 2;

                        break;
                    case 327:
                        $function = 'QUARTILE';
                        $args = 2;

                        break;
                    case 328:
                        $function = 'PERCENTILE';
                        $args = 2;

                        break;
                    case 331:
                        $function = 'TRIMMEAN';
                        $args = 2;

                        break;
                    case 332:
                        $function = 'TINV';
                        $args = 2;

                        break;
                    case 337:
                        $function = 'POWER';
                        $args = 2;

                        break;
                    case 342:
                        $function = 'RADIANS';
                        $args = 1;

                        break;
                    case 343:
                        $function = 'DEGREES';
                        $args = 1;

                        break;
                    case 346:
                        $function = 'COUNTIF';
                        $args = 2;

                        break;
                    case 347:
                        $function = 'COUNTBLANK';
                        $args = 1;

                        break;
                    case 350:
                        $function = 'ISPMT';
                        $args = 4;

                        break;
                    case 351:
                        $function = 'DATEDIF';
                        $args = 3;

                        break;
                    case 352:
                        $function = 'DATESTRING';
                        $args = 1;

                        break;
                    case 353:
                        $function = 'NUMBERSTRING';
                        $args = 2;

                        break;
                    case 360:
                        $function = 'PHONETIC';
                        $args = 1;

                        break;
                    case 368:
                        $function = 'BAHTTEXT';
                        $args = 1;

                        break;
                    default:
                        throw new Exception('Unrecognized function in formula');
                }
                $data = ['function' => $function, 'args' => $args];

                break;
            case 0x22:                case 0x42:
            case 0x62:
                $name = 'tFuncV';
                $size = 4;
                                $args = ord($formulaData[1]);
                                $index = self::getUInt2d($formulaData, 2);
                $function = match ($index) {
                    0 => 'COUNT',
                    1 => 'IF',
                    4 => 'SUM',
                    5 => 'AVERAGE',
                    6 => 'MIN',
                    7 => 'MAX',
                    8 => 'ROW',
                    9 => 'COLUMN',
                    11 => 'NPV',
                    12 => 'STDEV',
                    13 => 'DOLLAR',
                    14 => 'FIXED',
                    28 => 'LOOKUP',
                    29 => 'INDEX',
                    36 => 'AND',
                    37 => 'OR',
                    46 => 'VAR',
                    49 => 'LINEST',
                    50 => 'TREND',
                    51 => 'LOGEST',
                    52 => 'GROWTH',
                    56 => 'PV',
                    57 => 'FV',
                    58 => 'NPER',
                    59 => 'PMT',
                    60 => 'RATE',
                    62 => 'IRR',
                    64 => 'MATCH',
                    70 => 'WEEKDAY',
                    78 => 'OFFSET',
                    82 => 'SEARCH',
                    100 => 'CHOOSE',
                    101 => 'HLOOKUP',
                    102 => 'VLOOKUP',
                    109 => 'LOG',
                    115 => 'LEFT',
                    116 => 'RIGHT',
                    120 => 'SUBSTITUTE',
                    124 => 'FIND',
                    125 => 'CELL',
                    144 => 'DDB',
                    148 => 'INDIRECT',
                    167 => 'IPMT',
                    168 => 'PPMT',
                    169 => 'COUNTA',
                    183 => 'PRODUCT',
                    193 => 'STDEVP',
                    194 => 'VARP',
                    197 => 'TRUNC',
                    204 => 'USDOLLAR',
                    205 => 'FINDB',
                    206 => 'SEARCHB',
                    208 => 'LEFTB',
                    209 => 'RIGHTB',
                    216 => 'RANK',
                    219 => 'ADDRESS',
                    220 => 'DAYS360',
                    222 => 'VDB',
                    227 => 'MEDIAN',
                    228 => 'SUMPRODUCT',
                    247 => 'DB',
                    255 => '',
                    269 => 'AVEDEV',
                    270 => 'BETADIST',
                    272 => 'BETAINV',
                    317 => 'PROB',
                    318 => 'DEVSQ',
                    319 => 'GEOMEAN',
                    320 => 'HARMEAN',
                    321 => 'SUMSQ',
                    322 => 'KURT',
                    323 => 'SKEW',
                    324 => 'ZTEST',
                    329 => 'PERCENTRANK',
                    330 => 'MODE',
                    336 => 'CONCATENATE',
                    344 => 'SUBTOTAL',
                    345 => 'SUMIF',
                    354 => 'ROMAN',
                    358 => 'GETPIVOTDATA',
                    359 => 'HYPERLINK',
                    361 => 'AVERAGEA',
                    362 => 'MAXA',
                    363 => 'MINA',
                    364 => 'STDEVPA',
                    365 => 'VARPA',
                    366 => 'STDEVA',
                    367 => 'VARA',
                    default => throw new Exception('Unrecognized function in formula'),
                };
                $data = ['function' => $function, 'args' => $args];

                break;
            case 0x23:                case 0x43:
            case 0x63:
                $name = 'tName';
                $size = 5;
                                $definedNameIndex = self::getUInt2d($formulaData, 1) - 1;
                                $data = $this->definedname[$definedNameIndex]['name'] ?? '';

                break;
            case 0x24:                case 0x44:
            case 0x64:
                $name = 'tRef';
                $size = 5;
                $data = $this->readBIFF8CellAddress(substr($formulaData, 1, 4));

                break;
            case 0x25:                case 0x45:
            case 0x65:
                $name = 'tArea';
                $size = 9;
                $data = $this->readBIFF8CellRangeAddress(substr($formulaData, 1, 8));

                break;
            case 0x26:                case 0x46:
            case 0x66:
                $name = 'tMemArea';
                                                $subSize = self::getUInt2d($formulaData, 5);
                $size = 7 + $subSize;
                $data = $this->getFormulaFromData(substr($formulaData, 7, $subSize));

                break;
            case 0x27:                case 0x47:
            case 0x67:
                $name = 'tMemErr';
                                                $subSize = self::getUInt2d($formulaData, 5);
                $size = 7 + $subSize;
                $data = $this->getFormulaFromData(substr($formulaData, 7, $subSize));

                break;
            case 0x29:                case 0x49:
            case 0x69:
                $name = 'tMemFunc';
                                $subSize = self::getUInt2d($formulaData, 1);
                $size = 3 + $subSize;
                $data = $this->getFormulaFromData(substr($formulaData, 3, $subSize));

                break;
            case 0x2C:             case 0x4C:
            case 0x6C:
                $name = 'tRefN';
                $size = 5;
                $data = $this->readBIFF8CellAddressB(substr($formulaData, 1, 4), $baseCell);

                break;
            case 0x2D:                case 0x4D:
            case 0x6D:
                $name = 'tAreaN';
                $size = 9;
                $data = $this->readBIFF8CellRangeAddressB(substr($formulaData, 1, 8), $baseCell);

                break;
            case 0x39:                case 0x59:
            case 0x79:
                $name = 'tNameX';
                $size = 7;
                                                $index = self::getUInt2d($formulaData, 3);
                                $data = $this->externalNames[$index - 1]['name'] ?? '';

                                break;
            case 0x3A:                case 0x5A:
            case 0x7A:
                $name = 'tRef3d';
                $size = 7;

                try {
                                        $sheetRange = $this->readSheetRangeByRefIndex(self::getUInt2d($formulaData, 1));
                                        $cellAddress = $this->readBIFF8CellAddress(substr($formulaData, 3, 4));

                    $data = "$sheetRange!$cellAddress";
                } catch (PhpSpreadsheetException) {
                                        $data = '#REF!';
                }

                break;
            case 0x3B:                case 0x5B:
            case 0x7B:
                $name = 'tArea3d';
                $size = 11;

                try {
                                        $sheetRange = $this->readSheetRangeByRefIndex(self::getUInt2d($formulaData, 1));
                                        $cellRangeAddress = $this->readBIFF8CellRangeAddress(substr($formulaData, 3, 8));

                    $data = "$sheetRange!$cellRangeAddress";
                } catch (PhpSpreadsheetException) {
                                        $data = '#REF!';
                }

                break;
                            default:
                throw new Exception('Unrecognized token ' . sprintf('%02X', $id) . ' in formula');
        }

        return [
            'id' => $id,
            'name' => $name,
            'size' => $size,
            'data' => $data,
        ];
    }

        private function readBIFF8CellAddress(string $cellAddressStructure): string
    {
                $row = self::getUInt2d($cellAddressStructure, 0) + 1;

                        $column = Coordinate::stringFromColumnIndex((0x00FF & self::getUInt2d($cellAddressStructure, 2)) + 1);

                if (!(0x4000 & self::getUInt2d($cellAddressStructure, 2))) {
            $column = '$' . $column;
        }
                if (!(0x8000 & self::getUInt2d($cellAddressStructure, 2))) {
            $row = '$' . $row;
        }

        return $column . $row;
    }

        private function readBIFF8CellAddressB(string $cellAddressStructure, string $baseCell = 'A1'): string
    {
        [$baseCol, $baseRow] = Coordinate::coordinateFromString($baseCell);
        $baseCol = Coordinate::columnIndexFromString($baseCol) - 1;
        $baseRow = (int) $baseRow;

                $rowIndex = self::getUInt2d($cellAddressStructure, 0);
        $row = self::getUInt2d($cellAddressStructure, 0) + 1;

                if (!(0x4000 & self::getUInt2d($cellAddressStructure, 2))) {
                                    $colIndex = 0x00FF & self::getUInt2d($cellAddressStructure, 2);

            $column = Coordinate::stringFromColumnIndex($colIndex + 1);
            $column = '$' . $column;
        } else {
                                    $relativeColIndex = 0x00FF & self::getInt2d($cellAddressStructure, 2);
            $colIndex = $baseCol + $relativeColIndex;
            $colIndex = ($colIndex < 256) ? $colIndex : $colIndex - 256;
            $colIndex = ($colIndex >= 0) ? $colIndex : $colIndex + 256;
            $column = Coordinate::stringFromColumnIndex($colIndex + 1);
        }

                if (!(0x8000 & self::getUInt2d($cellAddressStructure, 2))) {
            $row = '$' . $row;
        } else {
            $rowIndex = ($rowIndex <= 32767) ? $rowIndex : $rowIndex - 65536;
            $row = $baseRow + $rowIndex;
        }

        return $column . $row;
    }

        private function readBIFF5CellRangeAddressFixed(string $subData): string
    {
                $fr = self::getUInt2d($subData, 0) + 1;

                $lr = self::getUInt2d($subData, 2) + 1;

                $fc = ord($subData[4]);

                $lc = ord($subData[5]);

                if ($fr > $lr || $fc > $lc) {
            throw new Exception('Not a cell range address');
        }

                $fc = Coordinate::stringFromColumnIndex($fc + 1);
        $lc = Coordinate::stringFromColumnIndex($lc + 1);

        if ($fr == $lr && $fc == $lc) {
            return "$fc$fr";
        }

        return "$fc$fr:$lc$lr";
    }

        private function readBIFF8CellRangeAddressFixed(string $subData): string
    {
                $fr = self::getUInt2d($subData, 0) + 1;

                $lr = self::getUInt2d($subData, 2) + 1;

                $fc = self::getUInt2d($subData, 4);

                $lc = self::getUInt2d($subData, 6);

                if ($fr > $lr || $fc > $lc) {
            throw new Exception('Not a cell range address');
        }

                $fc = Coordinate::stringFromColumnIndex($fc + 1);
        $lc = Coordinate::stringFromColumnIndex($lc + 1);

        if ($fr == $lr && $fc == $lc) {
            return "$fc$fr";
        }

        return "$fc$fr:$lc$lr";
    }

        private function readBIFF8CellRangeAddress(string $subData): string
    {
                
                $fr = self::getUInt2d($subData, 0) + 1;

                $lr = self::getUInt2d($subData, 2) + 1;

        
                $fc = Coordinate::stringFromColumnIndex((0x00FF & self::getUInt2d($subData, 4)) + 1);

                if (!(0x4000 & self::getUInt2d($subData, 4))) {
            $fc = '$' . $fc;
        }

                if (!(0x8000 & self::getUInt2d($subData, 4))) {
            $fr = '$' . $fr;
        }

        
                $lc = Coordinate::stringFromColumnIndex((0x00FF & self::getUInt2d($subData, 6)) + 1);

                if (!(0x4000 & self::getUInt2d($subData, 6))) {
            $lc = '$' . $lc;
        }

                if (!(0x8000 & self::getUInt2d($subData, 6))) {
            $lr = '$' . $lr;
        }

        return "$fc$fr:$lc$lr";
    }

        private function readBIFF8CellRangeAddressB(string $subData, string $baseCell = 'A1'): string
    {
        [$baseCol, $baseRow] = Coordinate::indexesFromString($baseCell);
        $baseCol = $baseCol - 1;

                
                $frIndex = self::getUInt2d($subData, 0); 
                $lrIndex = self::getUInt2d($subData, 2); 
                if (!(0x4000 & self::getUInt2d($subData, 4))) {
                                                $fcIndex = 0x00FF & self::getUInt2d($subData, 4);
            $fc = Coordinate::stringFromColumnIndex($fcIndex + 1);
            $fc = '$' . $fc;
        } else {
                                                $relativeFcIndex = 0x00FF & self::getInt2d($subData, 4);
            $fcIndex = $baseCol + $relativeFcIndex;
            $fcIndex = ($fcIndex < 256) ? $fcIndex : $fcIndex - 256;
            $fcIndex = ($fcIndex >= 0) ? $fcIndex : $fcIndex + 256;
            $fc = Coordinate::stringFromColumnIndex($fcIndex + 1);
        }

                if (!(0x8000 & self::getUInt2d($subData, 4))) {
                        $fr = $frIndex + 1;
            $fr = '$' . $fr;
        } else {
                        $frIndex = ($frIndex <= 32767) ? $frIndex : $frIndex - 65536;
            $fr = $baseRow + $frIndex;
        }

                if (!(0x4000 & self::getUInt2d($subData, 6))) {
                                                $lcIndex = 0x00FF & self::getUInt2d($subData, 6);
            $lc = Coordinate::stringFromColumnIndex($lcIndex + 1);
            $lc = '$' . $lc;
        } else {
                                                $relativeLcIndex = 0x00FF & self::getInt2d($subData, 4);
            $lcIndex = $baseCol + $relativeLcIndex;
            $lcIndex = ($lcIndex < 256) ? $lcIndex : $lcIndex - 256;
            $lcIndex = ($lcIndex >= 0) ? $lcIndex : $lcIndex + 256;
            $lc = Coordinate::stringFromColumnIndex($lcIndex + 1);
        }

                if (!(0x8000 & self::getUInt2d($subData, 6))) {
                        $lr = $lrIndex + 1;
            $lr = '$' . $lr;
        } else {
                        $lrIndex = ($lrIndex <= 32767) ? $lrIndex : $lrIndex - 65536;
            $lr = $baseRow + $lrIndex;
        }

        return "$fc$fr:$lc$lr";
    }

        private function readBIFF8CellRangeAddressList(string $subData): array
    {
        $cellRangeAddresses = [];

                $nm = self::getUInt2d($subData, 0);

        $offset = 2;
                for ($i = 0; $i < $nm; ++$i) {
            $cellRangeAddresses[] = $this->readBIFF8CellRangeAddressFixed(substr($subData, $offset, 8));
            $offset += 8;
        }

        return [
            'size' => 2 + 8 * $nm,
            'cellRangeAddresses' => $cellRangeAddresses,
        ];
    }

        private function readBIFF5CellRangeAddressList(string $subData): array
    {
        $cellRangeAddresses = [];

                $nm = self::getUInt2d($subData, 0);

        $offset = 2;
                for ($i = 0; $i < $nm; ++$i) {
            $cellRangeAddresses[] = $this->readBIFF5CellRangeAddressFixed(substr($subData, $offset, 6));
            $offset += 6;
        }

        return [
            'size' => 2 + 6 * $nm,
            'cellRangeAddresses' => $cellRangeAddresses,
        ];
    }

        private function readSheetRangeByRefIndex(int $index): string|false
    {
        if (isset($this->ref[$index])) {
            $type = $this->externalBooks[$this->ref[$index]['externalBookIndex']]['type'];

            switch ($type) {
                case 'internal':
                                        if ($this->ref[$index]['firstSheetIndex'] == 0xFFFF || $this->ref[$index]['lastSheetIndex'] == 0xFFFF) {
                        throw new Exception('Deleted sheet reference');
                    }

                                        $firstSheetName = $this->sheets[$this->ref[$index]['firstSheetIndex']]['name'];
                    $lastSheetName = $this->sheets[$this->ref[$index]['lastSheetIndex']]['name'];

                    if ($firstSheetName == $lastSheetName) {
                                                $sheetRange = $firstSheetName;
                    } else {
                        $sheetRange = "$firstSheetName:$lastSheetName";
                    }

                                        $sheetRange = str_replace("'", "''", $sheetRange);

                                                                                                    if (preg_match("/[ !\"@#£$%&{()}<>=+'|^,;-]/u", $sheetRange)) {
                        $sheetRange = "'$sheetRange'";
                    }

                    return $sheetRange;
                default:
                                        throw new Exception('Xls reader only supports internal sheets in formulas');
            }
        }

        return false;
    }

        private static function readBIFF8ConstantArray(string $arrayData): array
    {
                $nc = ord($arrayData[0]);

                $nr = self::getUInt2d($arrayData, 1);
        $size = 3;         $arrayData = substr($arrayData, 3);

                $matrixChunks = [];
        for ($r = 1; $r <= $nr + 1; ++$r) {
            $items = [];
            for ($c = 1; $c <= $nc + 1; ++$c) {
                $constant = self::readBIFF8Constant($arrayData);
                $items[] = $constant['value'];
                $arrayData = substr($arrayData, $constant['size']);
                $size += $constant['size'];
            }
            $matrixChunks[] = implode(',', $items);         }
        $matrix = '{' . implode(';', $matrixChunks) . '}';

        return [
            'value' => $matrix,
            'size' => $size,
        ];
    }

        private static function readBIFF8Constant(string $valueData): array
    {
                $identifier = ord($valueData[0]);

        switch ($identifier) {
            case 0x00:                 $value = '';
                $size = 9;

                break;
            case 0x01:                                 $value = self::extractNumber(substr($valueData, 1, 8));
                $size = 9;

                break;
            case 0x02:                                 $string = self::readUnicodeStringLong(substr($valueData, 1));
                $value = '"' . $string['value'] . '"';
                $size = 1 + $string['size'];

                break;
            case 0x04:                                 if (ord($valueData[1])) {
                    $value = 'TRUE';
                } else {
                    $value = 'FALSE';
                }
                $size = 9;

                break;
            case 0x10:                                 $value = Xls\ErrorCode::lookup(ord($valueData[1]));
                $size = 9;

                break;
            default:
                throw new PhpSpreadsheetException('Unsupported BIFF8 constant');
        }

        return [
            'value' => $value,
            'size' => $size,
        ];
    }

        private static function readRGB(string $rgb): array
    {
                $r = ord($rgb[0]);

                $g = ord($rgb[1]);

                $b = ord($rgb[2]);

                $rgb = sprintf('%02X%02X%02X', $r, $g, $b);

        return ['rgb' => $rgb];
    }

        private function readByteStringShort(string $subData): array
    {
                $ln = ord($subData[0]);

                $value = $this->decodeCodepage(substr($subData, 1, $ln));

        return [
            'value' => $value,
            'size' => 1 + $ln,         ];
    }

        private function readByteStringLong(string $subData): array
    {
                $ln = self::getUInt2d($subData, 0);

                $value = $this->decodeCodepage(substr($subData, 2));

                return [
            'value' => $value,
            'size' => 2 + $ln,         ];
    }

        private static function readUnicodeStringShort(string $subData): array
    {
                $characterCount = ord($subData[0]);

        $string = self::readUnicodeString(substr($subData, 1), $characterCount);

                ++$string['size'];

        return $string;
    }

        private static function readUnicodeStringLong(string $subData): array
    {
                $characterCount = self::getUInt2d($subData, 0);

        $string = self::readUnicodeString(substr($subData, 2), $characterCount);

                $string['size'] += 2;

        return $string;
    }

        private static function readUnicodeString(string $subData, int $characterCount): array
    {
                        $isCompressed = !((0x01 & ord($subData[0])) >> 0);

                
                
                                $value = self::encodeUTF16(substr($subData, 1, $isCompressed ? $characterCount : 2 * $characterCount), $isCompressed);

        return [
            'value' => $value,
            'size' => $isCompressed ? 1 + $characterCount : 1 + 2 * $characterCount,         ];
    }

        private static function UTF8toExcelDoubleQuoted(string $value): string
    {
        return '"' . str_replace('"', '""', $value) . '"';
    }

        private static function extractNumber(string $data): int|float
    {
        $rknumhigh = self::getInt4d($data, 4);
        $rknumlow = self::getInt4d($data, 0);
        $sign = ($rknumhigh & (int) 0x80000000) >> 31;
        $exp = (($rknumhigh & 0x7FF00000) >> 20) - 1023;
        $mantissa = (0x100000 | ($rknumhigh & 0x000FFFFF));
        $mantissalow1 = ($rknumlow & (int) 0x80000000) >> 31;
        $mantissalow2 = ($rknumlow & 0x7FFFFFFF);
        $value = $mantissa / 2 ** (20 - $exp);

        if ($mantissalow1 != 0) {
            $value += 1 / 2 ** (21 - $exp);
        }

        $value += $mantissalow2 / 2 ** (52 - $exp);
        if ($sign) {
            $value *= -1;
        }

        return $value;
    }

    private static function getIEEE754(int $rknum): float|int
    {
        if (($rknum & 0x02) != 0) {
            $value = $rknum >> 2;
        } else {
                                                                        $sign = ($rknum & (int) 0x80000000) >> 31;
            $exp = ($rknum & 0x7FF00000) >> 20;
            $mantissa = (0x100000 | ($rknum & 0x000FFFFC));
            $value = $mantissa / 2 ** (20 - ($exp - 1023));
            if ($sign) {
                $value = -1 * $value;
            }
                    }
        if (($rknum & 0x01) != 0) {
            $value /= 100;
        }

        return $value;
    }

        private static function encodeUTF16(string $string, bool $compressed = false): string
    {
        if ($compressed) {
            $string = self::uncompressByteString($string);
        }

        return StringHelper::convertEncoding($string, 'UTF-8', 'UTF-16LE');
    }

        private static function uncompressByteString(string $string): string
    {
        $uncompressedString = '';
        $strLen = strlen($string);
        for ($i = 0; $i < $strLen; ++$i) {
            $uncompressedString .= $string[$i] . "\0";
        }

        return $uncompressedString;
    }

        private function decodeCodepage(string $string): string
    {
        return StringHelper::convertEncoding($string, 'UTF-8', $this->codepage);
    }

        public static function getUInt2d(string $data, int $pos): int
    {
        return ord($data[$pos]) | (ord($data[$pos + 1]) << 8);
    }

        public static function getInt2d(string $data, int $pos): int
    {
        return unpack('s', $data[$pos] . $data[$pos + 1])[1];     }

        public static function getInt4d(string $data, int $pos): int
    {
                                $_or_24 = ord($data[$pos + 3]);
        if ($_or_24 >= 128) {
                        $_ord_24 = -abs((256 - $_or_24) << 24);
        } else {
            $_ord_24 = ($_or_24 & 127) << 24;
        }

        return ord($data[$pos]) | (ord($data[$pos + 1]) << 8) | (ord($data[$pos + 2]) << 16) | $_ord_24;
    }

    private function parseRichText(string $is): RichText
    {
        $value = new RichText();
        $value->createText($is);

        return $value;
    }

        public function getMapCellStyleXfIndex(): array
    {
        return $this->mapCellStyleXfIndex;
    }

    private function readCFHeader(): array
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        if ($this->readDataOnly) {
            return [];
        }

        
                $cellRangeAddressList = ($this->version == self::XLS_BIFF8)
            ? $this->readBIFF8CellRangeAddressList(substr($recordData, 12))
            : $this->readBIFF5CellRangeAddressList(substr($recordData, 12));
        $cellRangeAddresses = $cellRangeAddressList['cellRangeAddresses'];

        return $cellRangeAddresses;
    }

    private function readCFRule(array $cellRangeAddresses): void
    {
        $length = self::getUInt2d($this->data, $this->pos + 2);
        $recordData = $this->readRecordData($this->data, $this->pos + 4, $length);

                $this->pos += 4 + $length;

        if ($this->readDataOnly) {
            return;
        }

                $cfRule = self::getUInt2d($recordData, 0);

                $type = (0x00FF & $cfRule) >> 0;
        $type = ConditionalFormatting::type($type);

                $operator = (0xFF00 & $cfRule) >> 8;
        $operator = ConditionalFormatting::operator($operator);

        if ($type === null || $operator === null) {
            return;
        }

                $size1 = self::getUInt2d($recordData, 2);

                $size2 = self::getUInt2d($recordData, 4);

                $options = self::getInt4d($recordData, 6);

        $style = new Style(false, true);         
        $hasFontRecord = (bool) ((0x04000000 & $options) >> 26);
        $hasAlignmentRecord = (bool) ((0x08000000 & $options) >> 27);
        $hasBorderRecord = (bool) ((0x10000000 & $options) >> 28);
        $hasFillRecord = (bool) ((0x20000000 & $options) >> 29);
        $hasProtectionRecord = (bool) ((0x40000000 & $options) >> 30);

        $offset = 12;

        if ($hasFontRecord === true) {
            $fontStyle = substr($recordData, $offset, 118);
            $this->getCFFontStyle($fontStyle, $style);
            $offset += 118;
        }

        if ($hasAlignmentRecord === true) {
                                    $offset += 8;
        }

        if ($hasBorderRecord === true) {
                                    $offset += 8;
        }

        if ($hasFillRecord === true) {
            $fillStyle = substr($recordData, $offset, 4);
            $this->getCFFillStyle($fillStyle, $style);
            $offset += 4;
        }

        if ($hasProtectionRecord === true) {
                                    $offset += 2;
        }

        $formula1 = $formula2 = null;
        if ($size1 > 0) {
            $formula1 = $this->readCFFormula($recordData, $offset, $size1);
            if ($formula1 === null) {
                return;
            }

            $offset += $size1;
        }

        if ($size2 > 0) {
            $formula2 = $this->readCFFormula($recordData, $offset, $size2);
            if ($formula2 === null) {
                return;
            }

            $offset += $size2;
        }

        $this->setCFRules($cellRangeAddresses, $type, $operator, $formula1, $formula2, $style);
    }

    
    private function getCFFontStyle(string $options, Style $style): void
    {
        $fontSize = self::getInt4d($options, 64);
        if ($fontSize !== -1) {
            $style->getFont()->setSize($fontSize / 20);         }

        $bold = self::getUInt2d($options, 72) === 700;         $style->getFont()->setBold($bold);

        $color = self::getInt4d($options, 80);

        if ($color !== -1) {
            $style->getFont()->getColor()->setRGB(Xls\Color::map($color, $this->palette, $this->version)['rgb']);
        }
    }

    
    
    private function getCFFillStyle(string $options, Style $style): void
    {
        $fillPattern = self::getUInt2d($options, 0);
                $fillPattern = (0xFC00 & $fillPattern) >> 10;
        $fillPattern = FillPattern::lookup($fillPattern);
        $fillPattern = $fillPattern === Fill::FILL_NONE ? Fill::FILL_SOLID : $fillPattern;

        if ($fillPattern !== Fill::FILL_NONE) {
            $style->getFill()->setFillType($fillPattern);

            $fillColors = self::getUInt2d($options, 2);

                        $color1 = (0x007F & $fillColors) >> 0;
            $style->getFill()->getStartColor()->setRGB(Xls\Color::map($color1, $this->palette, $this->version)['rgb']);

                        $color2 = (0x3F80 & $fillColors) >> 7;
            $style->getFill()->getEndColor()->setRGB(Xls\Color::map($color2, $this->palette, $this->version)['rgb']);
        }
    }

    
    private function readCFFormula(string $recordData, int $offset, int $size): float|int|string|null
    {
        try {
            $formula = substr($recordData, $offset, $size);
            $formula = pack('v', $size) . $formula; 
            $formula = $this->getFormulaFromStructure($formula);
            if (is_numeric($formula)) {
                return (str_contains($formula, '.')) ? (float) $formula : (int) $formula;
            }

            return $formula;
        } catch (PhpSpreadsheetException) {
            return null;
        }
    }

    private function setCFRules(array $cellRanges, string $type, string $operator, null|float|int|string $formula1, null|float|int|string $formula2, Style $style): void
    {
        foreach ($cellRanges as $cellRange) {
            $conditional = new Conditional();
            $conditional->setConditionType($type);
            $conditional->setOperatorType($operator);
            if ($formula1 !== null) {
                $conditional->addCondition($formula1);
            }
            if ($formula2 !== null) {
                $conditional->addCondition($formula2);
            }
            $conditional->setStyle($style);

            $conditionalStyles = $this->phpSheet->getStyle($cellRange)->getConditionalStyles();
            $conditionalStyles[] = $conditional;

            $this->phpSheet->getStyle($cellRange)->setConditionalStyles($conditionalStyles);
        }
    }

    public function getVersion(): int
    {
        return $this->version;
    }
}
