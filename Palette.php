<?php

namespace Trojal\PhpRo;

class Palette
{
    public function __construct($data)
    {
        if (strlen($data) != 1024)
            throw new \Exception('Error in palette data length.');

        $this->colors = array();
        for ($i = 0;$i < 256;$i++) {
            $this->colors[] = unpack('Cr/Cg/Cb/Ca', $data);
            $data = substr($data, 4);
        }
    }
}
