<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Image;

use Exception;

class ImageFileHandler
{
    /**
     * Converts a HEIC file to JPEG.
     *
     * @param string $imagepath Path to the HEIC image file.
     * @return string The content of the converted image.
     * @throws Exception If the conversion fails.
     */
    public function convertHeicToJpg(string $imagepath): string
    {
        if (! class_exists('Maestroerror\HeicToJpg')) {
            throw new Exception('HEIC/HEIF conversion class not found');
        }

        try {
            return \Maestroerror\HeicToJpg::convert($imagepath)->get();
        } catch (Exception $e) {
            throw new Exception('Error converting HEIC/HEIF image: ' . $e->getMessage());
        }
    }

     /**
     * Converts a JPEG XL file to JPG.
     *
     * @param string $imagepath Path to the JPEG XL image file.
     * @return string The content of the converted image.
     * @throws Exception If the conversion fails.
     */
    public function convertJxlToJpg(string $imagepath): string
    {

        $tempJpgPath = tempnam(sys_get_temp_dir(), 'jxl_') . '.jpg';

        $command = "djxl {$imagepath} {$tempJpgPath}";
        exec($command, $output, $return_var);

        if ($return_var !== 0) {
            throw new Exception('Error decoding JPEG XL image: ' . implode("\n", $output));
        }

        $content = file_get_contents($tempJpgPath);

        unlink($tempJpgPath);

        return $content;
    }

    /**
     * Processes the given file info and converts HEIC/HEIF or JPEG XL files to JPG.
     *
     * @param object $fileInfo Contains file metadata (like filename).
     * @param object $wrapper Wrapper for file operations.
     * @return string|null The content of the converted image, or null if no conversion was needed.
     * @throws Exception If the conversion fails or the file type is unsupported.
     */
    public function processFile($fileInfo, $wrapper): string
    {
        $mimelib = \TikiLib::lib('mime');
        $filetype = $mimelib->from_content($fileInfo['filename'], $wrapper->getContents());

        if ($filetype === 'image/heic' || $filetype === 'image/heif') {
            $imagepath = $wrapper->getReadableFile();
            return $this->convertHeicToJpg($imagepath);
        } elseif ($filetype === 'image/jxl') {
            $imagepath = $wrapper->getReadableFile();
            return $this->convertJxlToJpg($imagepath);
        } else {
            return $wrapper->getContents();
        }
    }
}
