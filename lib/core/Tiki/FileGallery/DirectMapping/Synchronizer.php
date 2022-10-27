<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\FileGallery\DirectMapping;

use Tiki\FileGallery\Definition;
use Tiki\FileGallery\File;
use TikiLib;

class Synchronizer
{
    protected $cache;
    protected $gallery;
    protected $filesystem;
    protected $filegal;

    public function __construct(Definition $gallery, $filesystem)
    {
        $this->gallery = $gallery;
        $this->filesystem = $filesystem;
        $this->filegal = TikiLib::lib('filegal');
        $this->cache = [];
    }

    public function handle($item)
    {
        $path = $item->path();
        $parentId = $this->syncDirectoryStructure($path);
        if ($item->isDir()) {
            $galleryId = $this->findOrCreateFilegal($parentId, basename($path));
            $this->filegal->table('tiki_file_galleries')->update(['lastModif' => $item->lastModified()], ['galleryId' => $galleryId]);
        } else {
            $this->updateFile($parentId, $item);
        }
    }

    public function syncDirectoryStructure($path)
    {
        $parents = $this->parents($path);
        $parentId = $this->gallery->getId();
        foreach ($parents as $parent) {
            $parentId = $this->findOrCreateFilegal($parentId, $parent);
        }
        return $parentId;
    }

    protected function parents($path)
    {
        $parents = [];
        $parent = dirname($path);
        while ($parent && $parent != '.') {
            array_unshift($parents, basename($parent));
            $parent = dirname($parent);
        }
        return $parents;
    }

    protected function findOrCreateFilegal($parentId, $name)
    {
        if (isset($this->cache[$parentId][$name])) {
            return $this->cache[$parentId][$name];
        }
        $fgal = $this->filegal->get_file_gallery_by_name($parentId, $name);
        if (! $fgal) {
            $new_info = $this->gallery->getInfo();
            unset($new_info['galleryId']);
            $new_info['name'] = $name;
            $new_info['parentId'] = $parentId;
            $new_info['direct'] = null;
            $galleryId = $this->filegal->replace_file_gallery($new_info);
        } else {
            $galleryId = $fgal['galleryId'];
        }
        $this->cache[$parentId][$name] = $galleryId;
        return $galleryId;
    }

    protected function updateFile($parentId, $item)
    {
        $path = $item->path();
        $name = basename($path);
        $existing = $this->filegal->get_file_by_name($parentId, $path, 'path');

        if ($existing) {
            if ($existing['lastModif'] == $item->lastModified()) {
                return;
            }
            $file = new File($existing);
        } else {
            $file = new File([
                'galleryId' => $parentId,
                'name' => $name,
                'filename' => $name,
                'path' => $path,
                'description' => '',
                'created' => $item->lastModified(),
            ]);
        }

        $file->setParam('data', null);
        $file->setParam('hash', md5($path));
        $file->setParam('filesize', $item->fileSize());
        $file->setParam('filetype', $this->filesystem->mimeType($path));
        $file->setParam('lastModif', $item->lastModified());

        // TODO: handle post-processing like Manipulators in File::replace and final save event/indexing
        if ($existing) {
            $this->filegal->table('tiki_files')->update($file->getParamsForDB(), ['fileId' => $existing['fileId']]);
        } else {
            $this->filegal->table('tiki_files')->insert($file->getParamsForDB());
        }
    }
}
