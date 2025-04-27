<?php

namespace PhpOffice\PhpSpreadsheet\Shared\OLE;

use PhpOffice\PhpSpreadsheet\Shared\OLE;

class ChainedBlockStream
{
        public $context;

        public ?OLE $ole = null;

        public array $params = [];

        public string $data;

        public int $pos = 0;

        public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool     {
        if ($mode[0] !== 'r') {
            if ($options & STREAM_REPORT_ERRORS) {
                trigger_error('Only reading is supported', E_USER_WARNING);
            }

            return false;
        }

                parse_str(substr($path, 25), $this->params);
        if (!isset($this->params['oleInstanceId'], $this->params['blockId'], $GLOBALS['_OLE_INSTANCES'][$this->params['oleInstanceId']])) {
            if ($options & STREAM_REPORT_ERRORS) {
                trigger_error('OLE stream not found', E_USER_WARNING);
            }

            return false;
        }
        $this->ole = $GLOBALS['_OLE_INSTANCES'][$this->params['oleInstanceId']];

        $blockId = $this->params['blockId'];
        $this->data = '';
        if (isset($this->params['size']) && $this->params['size'] < $this->ole->bigBlockThreshold && $blockId != $this->ole->root->startBlock) {
                        $rootPos = $this->ole->getBlockOffset($this->ole->root->startBlock);
            while ($blockId != -2) {
                $pos = $rootPos + $blockId * $this->ole->bigBlockSize;
                $blockId = $this->ole->sbat[$blockId];
                fseek($this->ole->_file_handle, $pos);
                $this->data .= fread($this->ole->_file_handle, $this->ole->bigBlockSize);
            }
        } else {
                        while ($blockId != -2) {
                $pos = $this->ole->getBlockOffset($blockId);
                fseek($this->ole->_file_handle, $pos);
                $this->data .= fread($this->ole->_file_handle, $this->ole->bigBlockSize);
                $blockId = $this->ole->bbat[$blockId];
            }
        }
        if (isset($this->params['size'])) {
            $this->data = substr($this->data, 0, $this->params['size']);
        }

        if ($options & STREAM_USE_PATH) {
            $openedPath = $path;
        }

        return true;
    }

        public function stream_close(): void     {
        $this->ole = null;
        unset($GLOBALS['_OLE_INSTANCES']);
    }

        public function stream_read(int $count): bool|string     {
        if ($this->stream_eof()) {
            return false;
        }
        $s = substr($this->data, (int) $this->pos, $count);
        $this->pos += $count;

        return $s;
    }

        public function stream_eof(): bool     {
        return $this->pos >= strlen($this->data);
    }

        public function stream_tell(): int     {
        return $this->pos;
    }

        public function stream_seek(int $offset, int $whence): bool     {
        if ($whence == SEEK_SET && $offset >= 0) {
            $this->pos = $offset;
        } elseif ($whence == SEEK_CUR && -$offset <= $this->pos) {
            $this->pos += $offset;
        } elseif ($whence == SEEK_END && -$offset <= count($this->data)) {             $this->pos = strlen($this->data) + $offset;
        } else {
            return false;
        }

        return true;
    }

        public function stream_stat(): array     {
        return [
            'size' => strlen($this->data),
        ];
    }

                                            }
