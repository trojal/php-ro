<?php

namespace Trojal\PhpRo;

use Trojal\PhpRo\DataReader,
    Trojal\PhpRo\SpriteFrame,
    Trojal\PhpRo\Palette;

define('PHPRO_IMG_GIF', 1);
define('PHPRO_IMG_PNG', 2);
define('PHPRO_IMG_JPG', 3);

define('PHPRO_SPR_HEADER_SIZE', 0x08);
define('PHPRO_FRAME_TYPE_PALETTE', 1);
define('PHPRO_FRAME_TYPE_RGBA', 2);

class Sprite
{
    public function __construct(DataReader $dataReader)
    {
        $this->dataReader = $dataReader;
    }

    public function getFrame($frameNumber)
    {
        return $this
            ->requireHeaders()
            ->frames[$frameNumber]
            ->requireData();
    }

    public function getImage($frameNumber, $imageType = PHPRO_IMG_GIF)
    {
        $frame = $this
            ->requireHeaders()
            ->requirePalette()
            ->frames[$frameNumber]
            ->requireData();

        $image = imagecreatetruecolor(
            $frame->header['width'],
            $frame->header['height']
        );

        if ($frame->header['type'] == PHPRO_FRAME_TYPE_PALETTE) {
            foreach ($this->palette->colors as $i => $color) {
                $resourcePalette[$i] = imagecolorallocatealpha($image, $color['r'], $color['g'], $color['b'], $color['a']);
            }
            imagecolortransparent($image, $resourcePalette[0]);

            $data = $frame->data;
            for ($i = 0; $data; $i++) {
                imagesetpixel($image, $i % $frame->header['width'], floor($i / $frame->header['width']), $resourcePalette[ord($data[0])]);
                $data = substr($data, 1);
            }
        } else if ($frame->header['type'] == PHPRO_FRAME_TYPE_RGBA) {
            imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
            imagealphablending($image, false);
            imagesavealpha($image, true);


            $data = $frame->data;
            for ($i = 0; $data; $i++) {
                if (!$color = imagecolorexactalpha(
                    $image,
                    255 - ord($data[3]),
                    255 - ord($data[2]),
                    255 - ord($data[1]),
                    127 - ord($data[0]) / 2
                )
                )
                    $color = imagecolorallocatealpha(
                        $image,
                        255 - ord($data[3]),
                        255 - ord($data[2]),
                        255 - ord($data[1]),
                        127 - ord($data[0]) / 2
                    );
                imagesetpixel($image, $i % $frame->header['width'], floor($i / $frame->header['width']), $color);
                $data = substr($data, 4);
            }
        }

        ob_start(null, 0);
        switch ($imageType) {
            case PHPRO_IMG_GIF:
                imagegif($image);
                break;
            case PHPRO_IMG_JPG:
                imagejpeg($image);
                break;
            case PHPRO_IMG_PNG:
                imagepng($image);
                break;
        }
        return ob_get_clean();
    }

    public function getPalette()
    {
        if (isset($this->palette))
            return $this->palette;

        $paletteData = $this
            ->requireHeaders()
            ->dataReader
            ->seek($this->header['paletteOffset'], SEEK_SET)
            ->read(0x400);

        $this->palette = new Palette($paletteData);
        return $this->palette;
    }

    public function setFrame($frameNumber, SpriteFrame $frame)
    {
        $this->frames[$frameNumber] = $frame;

        return $this;
    }

    public function setPalette(Palette $palette)
    {
        $this->palette = $palette;

        return $this;
    }

    public function requireHeaders()
    {
        if (isset($this->header))
            return $this;

        $this->header = unpack('a2header/CversionMajor/CversionMinor/SframeCountPalette/SframeCountRGBA', $this->dataReader->read(0x08));

        $this->frames = array();

        for ($nFrame = 0; $nFrame < $this->header['frameCountPalette']; $nFrame++) {
            $frameHeader = array();
            $frameHeader['type'] = PHPRO_FRAME_TYPE_PALETTE;
            $frameHeader['version'] = $this->header['versionMajor'];
            $frameHeader += unpack('Swidth/Sheight', $this->dataReader->read(0x04));
            if ($this->header['versionMajor'] == 0)
                $frameHeader['frameLength'] = $frameHeader['width'] * $frameHeader['height'];
            else
                $frameHeader += unpack('SframeLength', $this->dataReader->read(0x02));
            $frameHeader['offset'] = $this->dataReader->tell();

            $this->frames[] = new SpriteFrame($frameHeader, $this->dataReader);

            $this->dataReader->seek($frameHeader['frameLength'], SEEK_CUR);
        }

        for ($nFrame = 0; $nFrame < $this->header['frameCountRGBA']; $nFrame++) {
            $frameHeader = array();
            $frameHeader['type'] = PHPRO_FRAME_TYPE_RGBA;
            $frameHeader['version'] = $this->header['versionMajor'];
            $frameHeader += unpack('Swidth/Sheight', $this->dataReader->read(0x04));
            $frameHeader['frameLength'] = $frameHeader['width'] * $frameHeader['height'] * 4;
            $frameHeader['offset'] = $this->dataReader->tell();

            $this->frames[] = new SpriteFrame($frameHeader, $this->dataReader);

            $this->dataReader->seek($frameHeader['frameLength'], SEEK_CUR);
        }

        $this->header['paletteOffset'] = $this->dataReader->tell();

        return $this;
    }

    public function requirePalette()
    {
        if (isset($this->palette))
            return $this;

        $this->getPalette();

        return $this;
    }
}
