<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\FileGallery\FileWrapper;

use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToWriteFile;

class FlysystemFile implements WrapperInterface
{
    public $path;
    private $filesystem;
    private $basePath;
    private $temporaryFile = false;

    public function __construct($filesystem, $path)
    {
        $this->filesystem = $filesystem;
        $this->path = $path;
    }

    public function __destruct()
    {
        if (false !== $this->temporaryFile) {
            \unlink($this->temporaryFile);
        }
    }

    public function getReadableFile()
    {
        if (false !== $this->temporaryFile) {
            return $this->temporaryFile;
        }

        $sIniUploadTmpDir = \ini_get('upload_tmp_dir');
        if (! empty($sIniUploadTmpDir)) {
            $sTmpDir = \ini_get('upload_tmp_dir');
        } else {
            $sTmpDir = '/tmp';
        }

        $this->temporaryFile = $tmpfname = \tempnam($sTmpDir, 'wiki_');
        @\file_put_contents($tmpfname, $this->getContents());
        return $tmpfname;
    }

    public function getContents()
    {
        return $this->filesystem->read($this->path);
    }

    public function getChecksum()
    {
        return md5($this->getContents());
    }

    public function getSize()
    {
        return $this->filesystem->fileSize($this->path);
    }

    public function isFileLocal()
    {
        return false;
    }

    public function replaceContents($data)
    {
        try {
            $this->filesystem->write($this->path, $data);
        } catch (FilesystemException | UnableToWriteFile $e) {
            throw new WriteException(tr("Unable to write to destination path: %0, error: %1", $this->path, $e->getMessage()));
        }
    }

    public function getStorableContent()
    {
        return [
            'data' => null,
            'path' => $this->path,
            'hash' => $this->getChecksum(),
            'filesize' => $this->getSize(),
            'filetype' => $this->filesystem->mimeType($this->path),
            'lastModif' => $this->filesystem->lastModified($this->path),
        ];
    }
}
