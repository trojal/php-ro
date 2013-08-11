<?php

namespace Trojal\PhpRo;

use Trojal\PhpRo\ActionLayer;
use Trojal\PhpRo\DataReader;
use Trojal\PhpRo\Sprite;

class Action
{
    public function __construct(DataReader $dataReader)
    {
        $this->dataReader = $dataReader;
    }

    public function getAnimation($animationNumber, $imageType = PHPRO_IMG_GIF)
    {
        return $this
            ->requireHeaders()
            ->getMovement($animationNumber)
            ->getImage($imageType);
    }

    public function getSprite()
    {
        if (!isset($this->sprite))
            throw new Exception('Sprite required and not set.');

        return $this->sprite;
    }

    public function setSprite(Sprite $sprite)
    {
        $this->sprite = $sprite;
    }

    public function requireHeaders()
    {
        if (isset($this->header))
            return $this;

        $this->header = unpack('a2header/cversion/x/SmovementCount', $this->dataReader->read(0x10));

        $this->movements = array();

        for ($nMovement = 0; $nMovement < $this->header['movementCount']; $nMovement++) {
            $movementHeader = unpack('LframeCount', $this->dataReader->read(0x04));

            $this->movements[] = new ActionMovement($movementHeader);

            for ($nFrame = 0; $nFrame < $movementHeader['frameCount']; $nFrame++) {
                $this->dataReader->seek(0x20, SEEK_CUR);

                $frameHeader = unpack('LlayerCount', $this->dataReader->read(0x04));

                $this
                    ->getMovement($nMovement)
                    ->addFrame(new ActionFrame($frameHeader, $this->getSprite()));

                for ($nLayer = 0; $nLayer < $frameHeader['layerCount']; $nLayer++) {
                    $layerHeader = unpack('lxOffset/lyOffset/LsprFrame/Lmirror/Cred/Cgreen/Cblue/Calpha', $this->dataReader->read(0x14));

                    if ($this->header['version'] >= 2)
                        $layerHeader += unpack('fxScale', $this->dataReader->read(0x04));
                    else
                        $layerHeader['xScale'] = 0;

                    if ($this->header['version'] >= 4)
                        $layerHeader += unpack('fyScale', $this->dataReader->read(0x04));
                    else
                        $layerHeader['yScale'] = $layerHeader['xScale'];

                    $layerHeader += unpack('Lrotation/LsprType', $this->dataReader->read(0x08));

                    if ($this->header['version'] >= 5)
                        $layerHeader += unpack('LxSize/LySize', $this->dataReader->read(0x08));

                    $this
                        ->getMovement($nMovement)
                        ->getFrame($nFrame)
                        ->addLayer(new ActionLayer($layerHeader, $this->getSprite()));
                }

                $frameData = unpack('LsoundFrame/Linfo', $this->dataReader->read(0x08));

                if ($frameData['info'] == 1) {
                    $this->dataReader->seek(0x10, SEEK_CUR);
                }

                $this
                    ->getMovement($nMovement)
                    ->getFrame($nFrame)
                    ->addData($frameData);
            }
        }

        $this->header += unpack('LsoundCount', $this->dataReader->read(0x04));

        $this->sound = array();

        for ($nSound = 0; $nSound < $this->header['soundCount']; $nSound++) {
            $this->sound[] = array_pop(unpack('a40', $this->dataReader->read(0x28)));
        }

        for ($nMovement = 0; $nMovement < $this->header['movementCount']; $nMovement++) {
            {
                $this
                    ->getMovement($nMovement)
                    ->setDuration(array_pop(unpack('fmovementDuration', $this->dataReader->read(0x04))));
            }
        }

        return $this;
    }

    public function getMovement($movementNumber)
    {
        return $this
            ->movements[$movementNumber];
    }
}
