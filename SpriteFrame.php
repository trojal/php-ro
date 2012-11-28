<?php

namespace Trojal\PhpRo;

use Trojal\PhpRo\DataReader;

class SpriteFrame
{
    public function __construct($header, DataReader $dataReader)
    {
        $this->header = $header;
        $this->dataReader = $dataReader;
    }

    public function requireData()
    {
        if (isset($this->data))
            return $this;

        $data = $this
            ->dataReader
            ->seek($this->header['offset'])
            ->read($this->header['frameLength']);

        $frameData = null;

        if ($this->header['type'] == PHPRO_FRAME_TYPE_PALETTE) {
            while ($data) {
                if (ord($data[0]) == 0 && $this->header['version'] != 0) {
                    $data = substr($data, 1);
                    $frameData .= str_repeat(chr(0), ord($data[0]));
                } else {
                    $frameData .= $data[0];
                }
                $data = substr($data, 1);
            }
        } else if ($this->header['type'] == PHPRO_FRAME_TYPE_RGBA) {
            $frameData = $data;
        }

        $this->data = $frameData;

        return $this;
    }
}
