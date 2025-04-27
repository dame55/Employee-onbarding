<?php

namespace PhpOffice\PhpSpreadsheet\Writer;

abstract class BaseWriter implements IWriter
{
        protected bool $includeCharts = false;

        protected bool $preCalculateFormulas = true;

        private bool $useDiskCaching = false;

        private string $diskCachingDirectory = './';

        protected $fileHandle;

    private bool $shouldCloseFile;

    public function getIncludeCharts(): bool
    {
        return $this->includeCharts;
    }

    public function setIncludeCharts(bool $includeCharts): self
    {
        $this->includeCharts = $includeCharts;

        return $this;
    }

    public function getPreCalculateFormulas(): bool
    {
        return $this->preCalculateFormulas;
    }

    public function setPreCalculateFormulas(bool $precalculateFormulas): self
    {
        $this->preCalculateFormulas = $precalculateFormulas;

        return $this;
    }

    public function getUseDiskCaching(): bool
    {
        return $this->useDiskCaching;
    }

    public function setUseDiskCaching(bool $useDiskCache, ?string $cacheDirectory = null): self
    {
        $this->useDiskCaching = $useDiskCache;

        if ($cacheDirectory !== null) {
            if (is_dir($cacheDirectory)) {
                $this->diskCachingDirectory = $cacheDirectory;
            } else {
                throw new Exception("Directory does not exist: $cacheDirectory");
            }
        }

        return $this;
    }

    public function getDiskCachingDirectory(): string
    {
        return $this->diskCachingDirectory;
    }

    protected function processFlags(int $flags): void
    {
        if (((bool) ($flags & self::SAVE_WITH_CHARTS)) === true) {
            $this->setIncludeCharts(true);
        }
        if (((bool) ($flags & self::DISABLE_PRECALCULATE_FORMULAE)) === true) {
            $this->setPreCalculateFormulas(false);
        }
    }

        public function openFileHandle($filename): void
    {
        if (is_resource($filename)) {
            $this->fileHandle = $filename;
            $this->shouldCloseFile = false;

            return;
        }

        $mode = 'wb';
        $scheme = parse_url($filename, PHP_URL_SCHEME);
        if ($scheme === 's3') {
                        $mode = 'w';
                    }
        $fileHandle = $filename ? fopen($filename, $mode) : false;
        if ($fileHandle === false) {
            throw new Exception('Could not open file "' . $filename . '" for writing.');
        }

        $this->fileHandle = $fileHandle;
        $this->shouldCloseFile = true;
    }

        protected function maybeCloseFileHandle(): void
    {
        if ($this->shouldCloseFile) {
            if (!fclose($this->fileHandle)) {
                throw new Exception('Could not close file after writing.');
            }
        }
    }
}
