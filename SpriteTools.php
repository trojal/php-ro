<?php

namespace Trojal\PhpRo;

class SpriteTools
{
    static private function rotateX($x, $y, $theta)
    {
        return $x * cos($theta) - $y * sin($theta);
    }

    static private function rotateY($x, $y, $theta)
    {
        return $x * sin($theta) + $y * cos($theta);
    }

    public static function rotateGdBitmap(&$srcImg, $angle, $bgcolor, $ignore_transparent = 0)
    {

        $srcw = imagesx($srcImg);
        $srch = imagesy($srcImg);

        if ($angle == 0) return $srcImg;

        // Convert the angle to radians
        $theta = deg2rad($angle);

        // Calculate the width of the destination image.
        $temp = array(self::rotateX(0, 0, 0 - $theta),
            self::rotateX($srcw, 0, 0 - $theta),
            self::rotateX(0, $srch, 0 - $theta),
            self::rotateX($srcw, $srch, 0 - $theta)
        );
        $minX = floor(min($temp));
        $maxX = ceil(max($temp));
        $width = $maxX - $minX;

        // Calculate the height of the destination image.
        $temp = array(self::rotateY(0, 0, 0 - $theta),
            self::rotateY($srcw, 0, 0 - $theta),
            self::rotateY(0, $srch, 0 - $theta),
            self::rotateY($srcw, $srch, 0 - $theta)
        );
        $minY = floor(min($temp));
        $maxY = ceil(max($temp));
        $height = $maxY - $minY;

        $destimg = imagecreatetruecolor($width, $height);
        imagefill($destimg, 0, 0, imagecolorallocate($destimg, 0, 255, 0));

        // sets all pixels in the new image
        for ($x = $minX; $x < $maxX; $x++) {
            for ($y = $minY; $y < $maxY; $y++) {
                // fetch corresponding pixel from the source image
                $srcX = round(self::rotateX($x, $y, $theta));
                $srcY = round(self::rotateY($x, $y, $theta));
                if ($srcX >= 0 && $srcX < $srcw && $srcY >= 0 && $srcY < $srch) {
                    $color = imagecolorat($srcImg, $srcX, $srcY);
                } else {
                    $color = $bgcolor;
                }
                imagesetpixel($destimg, $x - $minX, $y - $minY, $color);
            }
        }
        return $destimg;
    }
}