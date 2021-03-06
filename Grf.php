<?php

namespace Trojal\PhpRo;

use Trojal\PhpRo\DataReader;

define('PHPRO_GRF_HEADER_SIZE', 0x2e);

class Grf
{
    public function __construct(DataReader $dataReader)
    {
        $this->dataReader = $dataReader;
    }

    public function getFilesArray()
    {
        if (isset($this->files))
            return $this->files;

        $fileData = $this
            ->requireHeaders()
            ->fileTable;

        $this->files = array();

        $fileDataSize = strlen($fileData);
        $fileDataPointer = 0;
        while ($fileDataPointer < $fileDataSize) {
            $endPointer = $fileDataPointer;
            while (ord($fileData[++$endPointer]) != 0) ;

            $fileName = substr($fileData, $fileDataPointer, $endPointer - $fileDataPointer);
            $fileDataPointer = $endPointer + 1;

            $file = unpack('LzSize/LzSizeAl/Lsize/cflags/Loffset', substr($fileData, $fileDataPointer, 0x11));

            $this->files[utf8_encode($fileName)] = $file;

            $fileDataPointer += 0x11;
        }

        return $this->files;
    }

    public function getFile($filename)
    {
        if (isset($this->files)) {
            $file = $this->files[$filename];
        } else {
            $fileStart = strpos(
                $this
                    ->requireHeaders()
                    ->fileTable,
                utf8_decode($filename)
            );

            $fileData = substr($this->fileTable, $fileStart);

            $endPointer = 0;
            while (ord($fileData[++$endPointer]) != 0) ;

            $fileName = substr($fileData, 0, $endPointer);
            $fileData = substr($fileData, strlen($fileName) + 1);

            $file = unpack('LzSize/LzSizeAl/Lsize/cflags/Loffset', $fileData);
        }

        if ($file['flags'] != 1 || $file['zSize'] <= 0 || $file['size'] <= 0)
            throw new \Exception('Cannot read directory contents.');

        $zData = $this
            ->dataReader
            ->seek($file['offset'] + PHPRO_GRF_HEADER_SIZE, SEEK_SET)
            ->read($file['zSize']);

        return gzuncompress($zData, $file['size']);
    }

    public function requireHeaders()
    {
        if (isset($this->header))
            return $this;

        $this->header = unpack('a16header/a14key/LfileTableOffset/Lseed/LfilesCount/Lversion', $this->dataReader->read(PHPRO_GRF_HEADER_SIZE));

        $this->dataReader->seek($this->header['fileTableOffset'] + PHPRO_GRF_HEADER_SIZE, SEEK_SET);

        $fileTable = unpack('LzSize/Lsize', $this->dataReader->read(8));
        $this->fileTable = gzuncompress($this->dataReader->read($fileTable['zSize']));

        return $this;
    }
}
