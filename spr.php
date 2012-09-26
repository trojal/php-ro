<?php
class SpriteReader {

  private $resource = null;
  private $md5 = null;
  private $header = null;
  private $frames = null;
  private $palette = null;

  public function __construct($resource) {
    define('SIZE_SPR_HEADER', 8);
    define('PALETTE_IMAGE', 1);
    define('RGBA_IMAGE', 2);

    if (!$resource)
      throw new Exception('No resource available for reading.');

    // if string => assume filename
    if (is_string($resource)) {
      $resource = fopen($resource, 'r');
    }
    $this->resource = $resource;
    if (isset($md5))
      $this->md5 = $md5;
  }

  public function get_header() {
    $header = unpack('a2header/CversionMajor/CversionMinor/SframeCountPalette/SframeCountRGBA', fread($this->resource, 0x08));
    $this->header = new SpriteHeader($header);

    return $this->header;
  }

  public function get_frames() {
    fseek($this->resource, SIZE_SPR_HEADER, SEEK_SET);

    for ($nFrame = 0;$nFrame < $this->header->get_frame_count(PALETTE_IMAGE);$nFrame++) {
      $frame = array();
      $frame['type'] = PALETTE_IMAGE;
      $frame += unpack('Swidth/Sheight', fread($this->resource, 0x04));
      if ($this->get_version() == 0)
        $frame['frameLength'] = $frame['width'] * $frame['height'];
      else
        $frame += unpack('SframeLength', fread($this->resource, 0x02));
      $frame['offset'] = ftell($this->resource);

      if (is_null($this->frames))
        $this->frames = array();
      $this->frames[] = new SpriteFrame($frame);

      fseek($this->resource, $frame['frameLength'], SEEK_CUR);
    }

    for ($nFrame = 0;$nFrame < $this->header->get_frame_count(RGBA_IMAGE);$nFrame++) {
      $frame = array();
      $frame['type'] = RGBA_IMAGE;
      $frame += unpack('Swidth/Sheight', fread($this->resource, 0x04));
      $frame['frameLength'] = $frame['width'] * $frame['height'] * 4;
      $frame['offset'] = ftell($this->resource);

      if (is_null($this->frames))
        $this->frames = array();
      $this->frames[] = new SpriteFrame($frame);

      fseek($this->resource, $frame['frameLength'], SEEK_CUR);
    }

    $this->header->set_palette_offset(ftell($this->resource));

    return $this->frames;
  }

  public function get_frame_data($offset) {
    if (!isset($this->frames[$offset]))
      return 'Requested frame not available.';

    $frame = $this->frames[$offset];
    fseek($this->resource, $frame->get_offset(), SEEK_SET);

    $frameData = null;
    $data = fread($this->resource, $frame->get_frameLength());

    if ($frame->get_type() == PALETTE_IMAGE) {
      while ($data) {
        if (ord($data[0]) == 0 && $this->get_version() != 0) {
          $data = substr($data, 1);
          $frameData .= str_repeat(chr(0), ord($data[0]));
        } else {
          $frameData .= $data[0];
        }
        $data = substr($data, 1);
      }
    } else if ($frame->get_type() == RGBA_IMAGE) {
      $frameData = $data;
    }

    $this->frameData[$offset] = $frameData;

    return $this->frameData[$offset];
  }

  public function get_frame_image($offset, $output='gif') {
    $image = new SpriteImage($this->frameData[$offset], $this->palette);

    $image->set_type($this->frames[$offset]->get_type());
    $image->set_output($output);
    $image->set_dimensions($this->frames[$offset]->get_width(), $this->frames[$offset]->get_height());

    return $image->render();
  }

  public function get_palette() {
    fseek($this->resource, $this->header->get_palette_offset(), SEEK_SET);

    $palette = fread($this->resource, 0x400);

    $this->palette = new SpritePalette($palette);

    return $this->palette;
  }

  private function get_version() {
    return $this->header->get_version();
  }
}

class SpriteHeader {
  private $header = null;
  private $versionMajor = null;
  private $versionMinor = null;
  private $frameCountPalette = null;
  private $frameCountRGBA = null;
  private $paletteOffset = null;

  public function __construct($header) {
    foreach ($header as $key=>$value)
      if (property_exists(get_class(), $key))
        $this->$key = $value;
  }

  public function set_palette_offset($offset) {
    $this->paletteOffset = $offset;
  }

  public function get_palette_offset() {
    return $this->paletteOffset;
  }

  public function get_frame_count($type) {
    if ($type == PALETTE_IMAGE)
      return $this->frameCountPalette;
    else if ($type == RGBA_IMAGE)
      return $this->frameCountRGBA;
    else
      return 0;
  }

  public function get_version() {
    return $this->versionMajor;
  }
}

class SpritePalette {
  private $colors = null;

  public function __construct($data) {
    if (strlen($data) != 1024)
      return 'Error in palette size.';
    $this->colors = array();
    for ($i = 0;$i < 256;$i++) {
      $this->colors[] = unpack('Cr/Cg/Cb/Ca', $data);
      $data = substr($data, 4);
    }
  }

  public function get_colors() {
    return $this->colors;
  }
}

class SpriteFrame {
  private $type = null;
  private $width = null;
  private $height = null;
  private $frameLength = null;
  private $offset = null;

  public function __construct($header) {
    foreach ($header as $key=>$value)
      if (property_exists(get_class(), $key))
        $this->$key = $value;
  }

  public function get_frameLength() {
    return $this->frameLength;
  }

  public function get_offset() {
    return $this->offset;
  }

  public function get_width() {
    return $this->width;
  }

  public function get_height() {
    return $this->height;
  }

  public function get_type() {
    return $this->type;
  }
}

class SpriteImage {
  private $type = null;
  private $width = null;
  private $height = null;
  private $resource = null;
  private $resourcePalette = null;
  private $data = null;
  private $palette = null;
  private $validOutput = array('gif', 'jpg', 'png');

  public function __construct($data, $palette) {
    $this->data = $data;
    $this->palette = $palette;
  }

  public function set_output($output) {
    if (in_array($output, $this->validOutput))
      $this->output = $output;
    else
      return 0;
  }

  public function set_type($type) {
    $this->type = $type;
  }

  public function set_dimensions($width, $height) {
    $this->width = $width;
    $this->height = $height;
  }

  public function render() {
    if (is_null($this->type))
      return 'Filetype not set.';

    $this->resource = imagecreatetruecolor($this->width, $this->height);

    if ($this->type == PALETTE_IMAGE) {
      foreach ($this->palette->get_colors() as $i=>$color) {
        $this->resourcePalette[$i] = imagecolorallocatealpha($this->resource, $color['r'], $color['g'], $color['b'], $color['a']);
      }
      imagecolortransparent($this->resource, $this->resourcePalette[0]);

      $data = $this->data;
      for ($i=0;$data;$i++) {
        imagesetpixel($this->resource, $i%$this->width, floor($i/$this->width), $this->resourcePalette[ord($data[0])]);
        $data = substr($data, 1);
      }
    } else if ($this->type == RGBA_IMAGE) {
      imagefill($this->resource, 0, 0, imagecolorallocatealpha($this->resource, 0, 0, 0, 127));
      imagealphablending($this->resource, false);
      imagesavealpha($this->resource, true);


      $data = $this->data;
      for ($i=0;$data;$i++) {
        if (!$color = imagecolorexactalpha(
          $this->resource,
          255 - ord($data[3]),
          255 - ord($data[2]),
          255 - ord($data[1]),
          127 - ord($data[0])/2
        ))
          $color = imagecolorallocatealpha(
            $this->resource,
            255 - ord($data[3]),
            255 - ord($data[2]),
            255 - ord($data[1]),
            127 - ord($data[0])/2
          ); 
        imagesetpixel($this->resource, $i%$this->width, floor($i/$this->width), $color);
        $data = substr($data, 4);
      }
    }

    ob_start(null, 0);
    switch ($this->output) {
      case 'gif':
        imagegif($this->resource);
        break;
      case 'jpg':
        imagejpeg($this->resource);
        break;
      case 'png':
        imagepng($this->resource);
        break;
    }
    return ob_get_clean();
  }
}
