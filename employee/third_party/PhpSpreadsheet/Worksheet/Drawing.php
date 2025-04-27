<?php

namespace PhpOffice\PhpSpreadsheet\Worksheet;

use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;
use ZipArchive;

class Drawing extends BaseDrawing
{
    const IMAGE_TYPES_CONVERTION_MAP = [
        IMAGETYPE_GIF => IMAGETYPE_PNG,
        IMAGETYPE_JPEG => IMAGETYPE_JPEG,
        IMAGETYPE_PNG => IMAGETYPE_PNG,
        IMAGETYPE_BMP => IMAGETYPE_PNG,
    ];

        private string $path;

        private bool $isUrl;

        public function __construct()
    {
                $this->path = '';
        $this->isUrl = false;

                parent::__construct();
    }

        public function getFilename(): string
    {
        return basename($this->path);
    }

        public function getIndexedFilename(): string
    {
        return md5($this->path) . '.' . $this->getExtension();
    }

        public function getExtension(): string
    {
        $exploded = explode('.', basename($this->path));

        return $exploded[count($exploded) - 1];
    }

        public function getMediaFilename(): string
    {
        if (!array_key_exists($this->type, self::IMAGE_TYPES_CONVERTION_MAP)) {
            throw new PhpSpreadsheetException('Unsupported image type in comment background. Supported types: PNG, JPEG, BMP, GIF.');
        }

        return sprintf('image%d%s', $this->getImageIndex(), $this->getImageFileExtensionForSave());
    }

        public function getPath(): string
    {
        return $this->path;
    }

        public function setPath(string $path, bool $verifyFile = true, ?ZipArchive $zip = null): static
    {
        if ($verifyFile && preg_match('~^data:image/[a-z]+;base64,~', $path) !== 1) {
                        if (filter_var($path, FILTER_VALIDATE_URL)) {
                $this->path = $path;
                                $this->isUrl = true;
                $imageContents = file_get_contents($path);
                $filePath = tempnam(sys_get_temp_dir(), 'Drawing');
                if ($filePath) {
                    file_put_contents($filePath, $imageContents);
                    if (file_exists($filePath)) {
                        $this->setSizesAndType($filePath);
                        unlink($filePath);
                    }
                }
            } elseif (file_exists($path)) {
                $this->path = $path;
                $this->setSizesAndType($path);
            } elseif ($zip instanceof ZipArchive) {
                $zipPath = explode('#', $path)[1];
                if ($zip->locateName($zipPath) !== false) {
                    $this->path = $path;
                    $this->setSizesAndType($path);
                }
            } else {
                throw new PhpSpreadsheetException("File $path not found!");
            }
        } else {
            $this->path = $path;
        }

        return $this;
    }

        public function getIsURL(): bool
    {
        return $this->isUrl;
    }

        public function setIsURL(bool $isUrl): self
    {
        $this->isUrl = $isUrl;

        return $this;
    }

        public function getHashCode(): string
    {
        return md5(
            $this->path
            . parent::getHashCode()
            . __CLASS__
        );
    }

        public function getImageTypeForSave(): int
    {
        if (!array_key_exists($this->type, self::IMAGE_TYPES_CONVERTION_MAP)) {
            throw new PhpSpreadsheetException('Unsupported image type in comment background. Supported types: PNG, JPEG, BMP, GIF.');
        }

        return self::IMAGE_TYPES_CONVERTION_MAP[$this->type];
    }

        public function getImageFileExtensionForSave(bool $includeDot = true): string
    {
        if (!array_key_exists($this->type, self::IMAGE_TYPES_CONVERTION_MAP)) {
            throw new PhpSpreadsheetException('Unsupported image type in comment background. Supported types: PNG, JPEG, BMP, GIF.');
        }

        $result = image_type_to_extension(self::IMAGE_TYPES_CONVERTION_MAP[$this->type], $includeDot);

        return "$result";
    }

        public function getImageMimeType(): string
    {
        if (!array_key_exists($this->type, self::IMAGE_TYPES_CONVERTION_MAP)) {
            throw new PhpSpreadsheetException('Unsupported image type in comment background. Supported types: PNG, JPEG, BMP, GIF.');
        }

        return image_type_to_mime_type(self::IMAGE_TYPES_CONVERTION_MAP[$this->type]);
    }
}
