<?php

namespace Trojal\PhpRo;

class ActionLayer
{

    public function __construct($header, $sprite)
    {
        $this->header = & $header;
        $this->sprite = & $sprite;
    }

    public function getDimensions()
    {
        if (!isset($this->header['xSize'], $this->header['ySize'])) {
            $this->header['xSize'] = imagesx(
                $this->sprite->getImage(
                    $this->getSpriteFrame(),
                    PHPRO_IMG_RAW
                )
            );
            $this->header['ySize'] = imagesy(
                $this->sprite->getImage(
                    $this->getSpriteFrame(), PHPRO_IMG_RAW
                )
            );
        }

        $width = intval($this->header['xSize'] * $this->header['xScale']);
        $height = intval($this->header['ySize'] * $this->header['yScale']);

        return array(
            'width' => $width,
            'height' => $height
        );
    }

    public function getSpriteFrame()
    {
        return $this->header['sprFrame'];
    }

    public function getHeader()
    {
        return $this->header;
    }
}
