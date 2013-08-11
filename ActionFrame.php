<?php

namespace Trojal\PhpRo;

class ActionFrame
{
    public function __construct($header, $sprite)
    {
        $this->header = & $header;
        $this->sprite = & $sprite;

        $this->layers = array();
    }

    public function addLayer(ActionLayer $actionLayer)
    {
        $this->layers[] = & $actionLayer;
    }

    public function addData($frameData)
    {
        $this->frameData = & $frameData;
    }

    public function getDimensions()
    {
        if (isset($this->dimensions))
            return $this->dimensions;

        $this->dimensions = array(
            'width' => 0,
            'height' => 0
        );

        foreach ($this->layers as &$layer) {
            $layerDimensions = $layer->getDimensions();
            $layerHeader = $layer->getHeader();

            if ($this->dimensions['width'] < abs($layerDimensions['width']) + 2 * abs($layerHeader['xOffset']))
                $this->dimensions['width'] = abs($layerDimensions['width']) + 2 * abs($layerHeader['xOffset']);

            if ($this->dimensions['height'] < abs($layerDimensions['height']) + 2 * abs($layerHeader['yOffset']))
                $this->dimensions['height'] = abs($layerDimensions['height']) + 2 * abs($layerHeader['yOffset']);
        }

        return $this->dimensions;
    }

    public function getImage($imageType = PHPRO_IMG_GIF)
    {
        $this->getDimensions();

        $containerImage = imagecreatetruecolor($this->dimensions['width'], $this->dimensions['height']);
        $transparentColor = imagecolorallocatealpha($containerImage, 0xFF, 0xFF, 0xFF, 127);
        imagecolortransparent($containerImage, $transparentColor);
        imagefill($containerImage, 0, 0, $transparentColor);

        foreach ($this->layers as &$layer) {
            $layerImage = & $this->sprite->getImage(
                $layer->getSpriteFrame(),
                PHPRO_IMG_RAW
            );

            $layerHeader = & $layer->getHeader();

            if ($layerHeader['mirror'] != 0) {
                $mirrorImage = imagecreatetruecolor(imagesx($layerImage), imagesy($layerImage));
                imagecopyresampled($mirrorImage, $layerImage, 0, 0, imagesx($layerImage) - 1, 0, imagesx($layerImage), imagesy($layerImage), -1 * imagesx($layerImage), imagesy($layerImage));
                imagecolortransparent($mirrorImage, imagecolortransparent($layerImage));
                $layerImage = $mirrorImage;
            }

            if ($layerHeader['rotation'] != 0) {
                $rotatedImage = SpriteTools::rotateGdBitmap($layerImage, 360 - $layerHeader['rotation'], $this->sprite->resourcePalette[0], 1);
                imagecolortransparent($rotatedImage, imagecolortransparent($layerImage));
                $layerImage = $rotatedImage;
            }

            $xPosition = intval(($this->dimensions['width'] - imagesx($layerImage) * $layerHeader['xScale']) / 2 + $layerHeader['xOffset']);
            $yPosition = intval(($this->dimensions['height'] - imagesy($layerImage) * $layerHeader['yScale']) / 2 + $layerHeader['yOffset']);

            imagecopyresized(
                $containerImage,
                $layerImage,
                $xPosition,
                $yPosition,
                0,
                0,
                imagesx($layerImage) * $layerHeader['xScale'],
                imagesy($layerImage) * $layerHeader['yScale'],
                imagesx($layerImage),
                imagesy($layerImage)
            );
        }

        ob_start(null, 0);
        switch ($imageType) {
            case PHPRO_IMG_GIF:
                imagegif($containerImage);
                break;
            case PHPRO_IMG_JPG:
                imagejpeg($containerImage);
                break;
            case PHPRO_IMG_PNG:
                imagepng($containerImage);
                break;
            case PHPRO_IMG_GD2:
                imagegd2($containerImage);
                break;
            case PHPRO_IMG_RAW:
                return $containerImage;
                break;
        }
        return ob_get_clean();
    }
}
