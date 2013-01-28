<?php

namespace Trojal\PhpRo;

define('PHPRO_DATA_FILE', 1);
define('PHPRO_DATA_STRING', 2);

class DataReader
{
    public function __construct($dataSource, $type = PHPRO_DATA_FILE)
    {
        switch ($type) {
            case PHPRO_DATA_FILE:
                $this->dataSource = fopen($dataSource, 'r');
                break;
            case PHPRO_DATA_STRING:
                $this->dataSource = fopen('data://text/plain;base64,' . base64_encode($dataSource), 'r');
                break;
            default:
                throw new \Exception('Unrecognized data type.');
                break;
        }
    }

    public function read($length)
    {
        return fread($this->dataSource, $length);
    }

    public function tell()
    {
        return ftell($this->dataSource);
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        fseek($this->dataSource, $offset, $whence);

        return $this;
    }
}
