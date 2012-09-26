<?php
class GrfReader {

  private $resource = null;
  private $md5 = null;
  private $header = null;

  public function __construct ($resource, $md5=null) {
    define('SIZE_GRF_HEADER', 46);

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

  // get grf headers
  public function get_header() {
    if (!isset($this->header)) {
      $header = unpack('a16header/a14key/LfileTableOffset/Lseed/LfilesCount/Lversion', fread($this->resource, 46));
      $this->header = new GrfHeader($header);
    }
    return $this->header;
  }

  // get file table from grf
  public function get_file_table() {
    fseek($this->resource, $this->header->fileTableOffset + SIZE_GRF_HEADER, SEEK_SET);
    $fData = unpack('LzSize/Lsize', fread($this->resource, 8));
    $this->fileTable = new GrfFileTable($fData);
    $fileTable = gzuncompress(fread($this->resource, $this->fileTable->zSize));

    // generate list of files
    while (strlen($fileTable)) {
        sscanf($fileTable, "%s", $fileName);
        $fileTable = substr($fileTable, strlen($fileName)+1);

        $file = unpack('LzSize/LzSizeAl/Lsize/cflags/Loffset', $fileTable);
        $file['name'] = $fileName;
        $this->fileTable->addFile($file);

        $fileTable = substr($fileTable, 17);
    }

    return $this->fileTable;
  }

  // check for file (or directory)
  public function is_file($file) {
    if ($file->flags != 1 || $file->zSize <= 0 || $file->size <= 0)
      return true;
    return false;
  }

  // get contents of a file
  public function get_file_contents($file) {
    // we can't read data from a directory
    if ($file->flags != 1 || $file->zSize <= 0 || $file->size <= 0)
      return "Error reading file data: ($file->name).\n";
    fseek($this->resource, $file->offset + SIZE_GRF_HEADER, SEEK_SET);
    $zData = fread($this->resource, $file->zSize);

    $fileData = gzuncompress($zData, $file->size);
    return $fileData;
  }
}

class GrfHeader {
  public $header = null;
  public $key = null;
  public $fileTableOffset = null;
  public $seed = null;
  public $filesCount = null;
  public $version = null;

  public function __construct($header) {
    foreach ($header as $key=>$value)
      if (property_exists(get_class(), $key))
        $this->$key = $value;
  }
}

class GrfFileTable {
  public $zSize = null;
  public $size = null;
  public $files = null;

  public function __construct($header) {
    foreach ($header as $key=>$value)
      if (property_exists(get_class(), $key))
        $this->$key = $value;
  }

  public function addFile($header) {
    if (is_null($this->files))
      $this->files = array();

    $file = new GrfFile($header);

    // index by filename when available
    if (isset($file->name))
      $this->files[$file->name] = $file;
    else
      $this->files[] = $file;
  }
}

class GrfFile {
  public $zSize = null;
  public $zSizeAl = null;
  public $size = null;
  public $flags = null;
  public $offset = null;
  public $name = null;

  public function __construct($header) {
    foreach ($header as $key=>$value)
      if (property_exists(get_class(), $key))
        $this->$key = $value;
  }
}
