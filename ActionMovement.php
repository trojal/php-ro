<?php

namespace Trojal\PhpRo;

class ActionMovement
{
    public function __construct($header)
    {
        $this->header = & $header;
        $this->frames = array();
    }

    public function getFrameCount()
    {
        return $this
            ->header['frameCount'];
    }

    public function addFrame(ActionFrame $actionFrame)
    {
        $this->frames[] = & $actionFrame;
    }

    public function getFrame($frameNumber)
    {
        return $this->frames[$frameNumber];
    }

    public function setDuration($duration)
    {
        $this->duration = & $duration;
    }

    public function getImage($imageType = PHPRO_IMG_GIF)
    {
        $this->getDimensions();

        $renderedFrames = array();
        $time = array();

        foreach ($this->frames as &$frame) {
            $renderedFrames[] = $frame->getImage($imageType);
            $time[] = $this->duration * 2.5;
        }

        $gif = new \GIFEncoder (
            $renderedFrames,
            $time,
            0,
            2,
            0xFF, 0, 0xFF,
            0,
            "bin"
        );

        return $gif->GetAnimation();
    }

    public function getDimensions()
    {
        if (isset($this->dimensions))
            return $this->dimensions;

        $this->dimensions = array(
            'width' => 0,
            'height' => 0
        );

        foreach ($this->frames as &$frame) {
            $frameDimensions = $frame->getDimensions();

            if ($this->dimensions['width'] < $frameDimensions['width'])
                $this->dimensions['width'] = $frameDimensions['width'];

            if ($this->dimensions['height'] < $frameDimensions['height'])
                $this->dimensions['height'] = $frameDimensions['height'];
        }

        foreach ($this->frames as &$frame) {
            $frame->dimensions = $this->dimensions;
        }

        return $this->dimensions;
    }
}