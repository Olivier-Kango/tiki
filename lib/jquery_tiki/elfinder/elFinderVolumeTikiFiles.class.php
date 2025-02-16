<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

// phpcs:disable PSR2.Methods.MethodDeclaration.Underscore -- is mostly a clone of upstream files, so ignore method name

/**
 * Started life as copy of elFinderVolumeMySQL.class.php
 * Initial convertion to work with Tiki filegals for Tiki 10
 *
 **/

class elFinderVolumeTikiFiles extends elFinderVolumeDriver
{
    private $filesTable;
    private $fileGalleriesTable;

    /**
     * Driver id
     * Must be started from letter and contains [a-z0-9]
     * Used as part of volume id
     *
     * @var string
     **/
    protected $driverId = 'f';

    /**
     * Directory for tmp files
     * If not set driver will try to use tmbDir as tmpDir
     *
     * @var string
     **/
    protected $tmpPath = '';

    /**
     * Last db error message
     *
     * @var string
     **/
    protected $dbError = '';

    private $filegallib;

    /**
     * Constructor
     * Extend options with required fields
     *
     * @return void
     * @author Dmitry (dio) Levashov
     **/
    public function __construct()
    {
        global $tikidomainslash, $prefs;

        $this->fileGalleriesTable = TikiDb::get()->table('tiki_file_galleries');
        $this->filesTable = TikiDb::get()->table('tiki_files');

        $opts = [
            'tmbPath'       => TEMP_PUBLIC_PATH . '/' . $tikidomainslash,
            'tmpPath'       => TEMP_PATH . '/' . $tikidomainslash,
            'tmbURL'        => TEMP_PUBLIC_PATH . '/' . $tikidomainslash,
            //'tmbSize'     => $prefs['fgal_thumb_max_size'],
        ];
        $this->options = array_merge($this->options, $opts);
        $this->options['mimeDetect'] = 'internal';

        $this->filegallib = TikiLib::lib('filegal');
    }

    /*********************************************************************/
    /*                        INIT AND CONFIGURE                         */
    /*********************************************************************/

    /**
     * Prepare driver before mount volume.
     * Connect to db, check required tables and fetch root path
     *
     * @return bool
     * @author Dmitry (dio) Levashov
     **/
    protected function init()
    {

        return true;
    }



    /**
     * Set tmp path
     *
     * @return void
     * @author Dmitry (dio) Levashov
     **/
    protected function configure()
    {
        parent::configure();

        if (($tmp = $this->options['tmpPath'])) {
            if (! file_exists($tmp)) {
                if (@mkdir($tmp)) {
                    @chmod($tmp, $this->options['tmbPathMode']);
                }
            }

            $this->tmpPath = is_dir($tmp) && is_writable($tmp) ? $tmp : false;
        }

        if (! $this->tmpPath && $this->tmbPath && $this->tmbPathWritable) {
            $this->tmpPath = $this->tmbPath;
        }

        $this->mimeDetect = 'internal';
    }

    /**
     * Close connection
     *
     * @return void
     * @author Dmitry (dio) Levashov
     **/
    public function umount()
    {
    }

    /**
     * Return debug info for client
     *
     * @return array
     * @author Dmitry (dio) Levashov
     **/
    public function debug()
    {
        $debug = parent::debug();
        if ($this->dbError) {
            $debug['dbError'] = $this->dbError;
        }
        return $debug;
    }

    /**
     * Create empty object with required mimetype
     *
     * @param  string  $path  parent dir path
     * @param  string  $name  object name
     * @param  string  $mime  mime type
     * @return bool
     * @author Dmitry (dio) Levashov
     **/
    protected function make($path, $name, $mime)
    {
        return false;
    }

    /**
     * Return temporary file path for required file
     *
     * @param  string  $path   file path
     * @return string
     * @author Dmitry (dio) Levashov
     **/
    protected function tmpname($path)
    {
        return $this->tmpPath . DIRECTORY_SEPARATOR . md5($path);
    }

    /**
     * Resize image
     *
     * @param string $hash   image file
     * @param int    $width  new width
     * @param int    $height new height
     * @param        $x
     * @param        $y
     * @param string $mode
     * @param string $bg
     * @param int    $degree
     * @param null   $jpgQuality
     *
     * @return array|false
     */
    public function resize($hash, $width, $height, $x, $y, $mode = 'resize', $bg = '', $degree = 0, $jpgQuality = null)
    {

           return false;
    }

    // initially from https://github.com/Studio-42/elFinder/wiki/Adding-file-description-to-Properties-dialog
    // adapted to send back tiki id's and syntax etc

    public function info($target, $newdesc = '')
    {
        $path = $this->decode($target);
        $id = $this->pathToId($path);
        $isGal = ($path[0] !== 'f');
        $smarty = TikiLib::lib('smarty');

        if ($isGal) {
            $info = $this->filegallib->get_file_gallery($id);
            $allowed = ['galleryId', 'name', 'type', 'description', 'created', 'visible',
                'lastModif', 'user', 'hits', 'votes', 'public', 'icon_field'
            ];
            // clever way of filtering by keys from http://stackoverflow.com/questions/4260086
            $info = array_intersect_key($info, array_flip($allowed));
            $info['link'] = smarty_function_object_link(
                [
                    'id' => $info['galleryId'],
                    'type' => 'file gallery',
                    'title' => $info['name'],
                ],
                $smarty->getEmptyInternalTemplate()
            );
            $perms = TikiLib::lib('tiki')->get_perm_object($id, 'file gallery', $info);
        } else {
            $info = $this->filegallib->get_file($id);
            $allowed = ['fileId', 'galleryId', 'name', 'description', 'created', 'filename', 'filesize',
                'filetype', 'user', 'author', 'hits', 'maxhits', 'votes', 'points', 'metadata',
                'lastModif', 'lastModifUser', 'lockedby', 'comment', 'archiveId'
            ];
            $info = array_intersect_key($info, array_flip($allowed));
            $info['wiki_syntax'] = $this->filegallib->getWikiSyntax($info['galleryId'], $info);
            if (in_array($info['filetype'], ['image/jpeg', 'image/gif', 'image/png'])) {
                $type = 'display';
            } else {
                $type = 'file';
            }
            $info['link'] = smarty_function_object_link(
                [
                    'id' => $info['fileId'],
                    'type' => $type,
                    'title' => $info['name'],
                ],
                $smarty->getEmptyInternalTemplate()
            );
            $perms = TikiLib::lib('tiki')->get_perm_object($id, 'file', $info);
        }

        if ($perms['tiki_p_download_files'] === 'y') {
            global $user;

            if ($newdesc && $perms['tiki_p_edit_gallery_file'] === 'y') {
                if ($isGal) {
                    unset($info['link']);
                    $info['description'] = $newdesc;
                    $this->filegallib->replace_file_gallery($info);
                } else {
                    $this->filegallib->update_file($id, [
                        'name' => $info['name'],
                        'description' => $newdesc,
                        'lastModifUser' => $user,
                        'comment' => $info['comment']
                    ]);
                }
            }
            return array_filter($info);
        }
        return '';
    }


    /*********************************************************************/
    /*                               FS API                              */
    /*********************************************************************/

    /**
     * Cache dir contents
     *
     * @param  string  $path  dir path
     * @return
     **/
    protected function cacheDir($path)
    {
        $this->dirsCache[$path] = [];

        $start = 0;
        $hasMore = true;

        while ($hasMore) {
            $res = $this->filegallib->get_files($start, 1000, 'name_desc', '', $this->pathToId($path), false, true);
            foreach ($res['data'] as $row) {
                // debug($row);
                list($r, $id) = $this->processTikiFile($row);

                if (($stat = $this->updateCache($id, $r)) && empty($stat['hidden'])) {
                    $this->dirsCache[$path][] = $id;
                }
            }
            $start += 1000;
            $hasMore = $start < $res['cant'];
        }

        return $this->dirsCache[$path];
    }

    /**
     * @param $row
     * @return array
     */
    protected function processTikiFile($row)
    {
        $r = [];
        if ($row['isgal']) {
            if (! isset($row['id'])) {
                $row['id'] = $row['galleryId'];
            }
            $id = $row['id'];
            $id = 'd_' . $id;
            $dirs = $this->filegallib->list_file_galleries(0, -1, 'name_desc', '', '', $row['id']);
            $r['dirs'] = $dirs['cant'];
            $r['mime'] = "directory";
            $r['size'] = 0;
        } else {
            $id = isset($row['id']) ? $row['id'] : $row['fileId'];
            $id = 'f_' . $id;
            $filetype = $row['filetype'];
            // elFinder assigns standard mime types like application/vnd.ms-word to ms doc, we use application/msword etc in tiki for some obscure reason :(
            if (strpos($filetype, 'application/ms') !== false) {
                $filetype = str_replace('application/ms', 'application/vnd.ms-', $filetype);
                $filetype = str_replace('ms--', 'ms-', $filetype);  // in case it was application/ms-word
            }

            $r['mime'] = $filetype;
            $r['size'] = $row['filesize'];
            $row['parentId'] = $row['galleryId'];
        }
        $r['ts'] = $row['lastModif'];
        $r['name'] = tra(empty($row['name']) ? $row['filename'] : $row['name']);
        if (empty($r['name'])) {
            $r['name'] = tra('Unnamed file');
        }
        if ($row['parentId'] > 0) {
            $r['phash'] = $this->encode(
                ($row['parentId'] == $this->options['path'] ? '' : 'd_') . $row['parentId']
            );
        }
        $r['locked'] = 0;   // these are set later
        $r['read'] = 1;
        $r['write'] = 0;

        return [$r, $id];
    }

    /**
     * Return array of parents paths (ids)
     *
     * @param  int   $path  file path (id)
     * @return array
     * @author Dmitry (dio) Levashov
     **/
    protected function getParents($path)
    {
        $parents = [];

        while ($path) {
            if ($file = $this->stat($path)) {
                array_unshift($parents, $path);
                $path = isset($file['phash']) ? $this->decode($file['phash']) : false;
            }
        }

        if (count($parents)) {
            array_pop($parents);
        }
        return $parents;
    }

    /**
     * Return correct file path for LOAD_FILE method
     *
     * @param  string $path  file path (id)
     * @return string
     * @author Troex Nevelin
     **/
    protected function loadFilePath($path)
    {
        $realPath = realpath($path);
        if (DIRECTORY_SEPARATOR == '\\') { // windows
            $realPath = str_replace('\\', '\\\\', $realPath);
        }
        return $realPath;
    }

    /*********************** paths/urls *************************/

    /**
     * Return parent directory path - for tiki this is the galleryId
     *
     * @param  string  $path  file path
     * @return string
     * @author Dmitry (dio) Levashov
     **/
    protected function _dirname($path)
    {
        $parent = '';

        if (! empty($this->cache[$path])) {
            $parent = $this->decode($this->cache[$path]['phash']);
        } else {
            $ar = explode('_', $path);
            if (count($ar) === 2 && $ar[0] === 'd') {
                if (! empty($this->cache[$ar[1]])) {
                    if (! empty($this->cache[$ar[1]]['phash'])) {
                        $parent = $this->decode($this->cache[$ar[1]]['phash']);
                    }
                } else {
                    // not cached, get from database just in case
                    $info = $this->filegallib->get_file_gallery_info($ar[1]);
                    $parent = $info['parentId'];
                }
            }
        }
        return $parent;
    }

    /**
     * Return file name
     *
     * @param  string  $path  file path
     * @return string
     * @author Dmitry (dio) Levashov
     **/
    protected function _basename($path)
    {
        return ($stat = $this->stat($path) && isset($stat['name'])) ? $stat['name'] : false;
    }

    /**
     * Join dir name and file name and return full path
     *
     * @param  string  $dir
     * @param  string  $name
     * @return string
     * @author Dmitry (dio) Levashov
     **/
    protected function _joinPath($dir, $name)
    {
        if ($fileId = $this->filesTable->fetchOne('fileId', ['name' => $name, 'galleryId' => $this->pathToId($dir)])) {
            return 'f_' . $fileId;
        } else {
            if ($galleryId = $this->fileGalleriesTable->fetchOne('galleryId', ['name' => $name, 'parentId' => $this->pathToId($dir)])) {
                return 'd_' . $galleryId;
            }
        }
        return '';
    }

    /**
     * Return normalized path, this works the same as os.path.normpath() in Python
     *
     * @param  string  $path  path
     * @return string
     * @author Troex Nevelin
     **/
    protected function _normpath($path)
    {
        return $path;
    }

    /**
     * Return file path related to root dir
     *
     * @param  string  $path  file path
     * @return string
     * @author Dmitry (dio) Levashov
     **/
    protected function _relpath($path)
    {
        return $path;
    }

    /**
     * Convert path related to root dir into real path
     *
     * @param  string  $path  file path
     * @return string
     * @author Dmitry (dio) Levashov
     **/
    protected function _abspath($path)
    {
        return $path;
    }

    /**
     * Return fake path started from root dir
     *
     * @param  string  $path  file path
     * @return string
     * @author Dmitry (dio) Levashov
     **/
    protected function _path($path)
    {
        if (($file = $this->stat($path)) == false) {
            return '';
        }

        $parentsIds = $this->getParents($path);
        $path = '';
        foreach ($parentsIds as $id) {
            $dir = $this->stat($id);
            $path .= isset($dir['name']) ? $dir['name'] : '' . $this->separator;
        }
        return $path . isset($file['name']) ? $file['name'] : '';
    }

    /**
     * Return true if $path is children of $parent
     *
     * @param  string  $path    path to check
     * @param  string  $parent  parent path
     * @return bool
     * @author Dmitry (dio) Levashov
     **/
    protected function _inpath($path, $parent)
    {
        return $path == $parent
            ? true
            : in_array($parent, $this->getParents($path));
    }

    /***************** file stat ********************/
    /**
     * Return stat for given path.
     * Stat contains following fields:
     * - (int)    size    file size in b. required
     * - (int)    ts      file modification time in unix time. required
     * - (string) mime    mimetype. required for folders, others - optionally
     * - (bool)   read    read permissions. required
     * - (bool)   write   write permissions. required
     * - (bool)   locked  is object locked. optionally
     * - (bool)   hidden  is object hidden. optionally
     * - (string) alias   for symlinks - link target path relative to root path. optionally
     * - (string) target  for symlinks - link target path. optionally
     *
     * If file does not exists - returns empty array or false.
     *
     * @param  string  $path    file path
     * @return array|false
     * @author Dmitry (dio) Levashov
     **/
    protected function _stat($path)
    {
        if (empty($path)) {
            return false;
        }
        // convert from galleryId/name file convention
        $ar = explode('/', $path);
        if (count($ar) === 2) {
            if ($fileId = $this->filesTable->fetchOne('fileId', ['name' => $ar[1]])) {
                $path = 'f_' . $fileId;
            } else {
                return false;
            }
        }

        $ar = explode('_', $path);
        if (count($ar) === 2) {
            $isgal = $ar[0] === 'd';
            $path = $ar[1];
        } else {
            $isgal = true;
        }
        if ($isgal) {
            $res = $this->filegallib->get_file_gallery($path);
        } else {
            $res = $this->filegallib->get_file($path);
        }

        if (empty($res['galleryId'])) {
            return [];
        }

        if ($res) {
            $res['isgal'] = $isgal;
            list($stat, $id) = $this->processTikiFile($res);

            return $stat;
        }
        return [];
    }

    /**
     * Return true if path is dir and has at least one childs directory
     *
     * @param  string  $path  dir path
     * @return bool
     * @author Dmitry (dio) Levashov
     **/
    protected function _subdirs($path)
    {
        return ($stat = $this->stat($path)) && isset($stat['dirs']) ? $stat['dirs'] : false;
    }

    /**
     * Return object width and height
     * Usualy used for images, but can be realize for video etc...
     *
     * @param  string  $path  file path
     * @param  string  $mime  file mime type
     * @return string
     * @author Dmitry (dio) Levashov
     **/
    protected function _dimensions($path, $mime)
    {
        global $prefs;

        $ar = explode('_', $path);
        if (count($ar) === 2) {
            $isgal = $ar[0] === 'd';
            $path = $ar[1];
        } else {
            $isgal = true;
        }
        if ($isgal) {
            return '';
        } else {
            $file = \Tiki\FileGallery\File::id($path);
            $size = getimagesize($file->getWrapper()->getReadableFile());
            $str = $size[0] . ' x ' . $size[1];
            // could add more info here?
            return $str;
        }
    }

    /******************** file/dir content *********************/

    /**
     * Return files list in directory.
     *
     * @param  string  $path  dir path
     * @return array
     * @author Dmitry (dio) Levashov
     **/
    protected function _scandir($path)
    {
        return isset($this->dirsCache[$path])
            ? $this->dirsCache[$path]
            : $this->cacheDir($path);
    }

    /**
     * Open file and return file pointer
     *
     * @param  string  $path  file path
     * @param  string  $mode  open file mode (ignored in this driver)
     * @return resource|false
     * @author Dmitry (dio) Levashov
     **/
    protected function _fopen($path, $mode = 'rb')
    {
        global $prefs;

        $fp = $this->tmbPath
            ? @fopen($this->tmpname($path), 'w+')
            : @tmpfile();


        if ($fp) {
            $fileId = $this->pathToId($path);
            $file = \Tiki\FileGallery\File::id($fileId);
            $contents = $file->getContents();

            if ($contents === 'REFERENCE') {
                $attributes = TikiLib::lib('attribute')->get_attributes('file', $file->fileId);
                if ($url = $attributes['tiki.content.url']) {
                    $data = $this->filegallib->get_info_from_url($url);
                    $contents = $data['data'];
                }
            }

            if ($contents) {
                fwrite($fp, $contents);
                rewind($fp);
                return $fp;
            } else {
                $this->_fclose($fp, $path);
            }
        }

        return false;
    }

    /**
     * Close opened file
     *
     * @param  resource  $fp  file pointer
     * @return bool
     * @author Dmitry (dio) Levashov
     **/
    protected function _fclose($fp, $path = '')
    {
        @fclose($fp);
        if ($path) {
            @unlink($this->tmpname($path));
        }
    }

    /********************  file/dir manipulations *************************/

    /**
     * Create dir and return created dir path or false on failed
     *
     * @param  string  $path  parent dir path
     * @param string  $name  new directory name
     * @return string|bool
     * @author Dmitry (dio) Levashov
     **/
    protected function _mkdir($path, $name)
    {
        global $user;

        $parentDirId = $this->pathToId($path);
        $parentPerms = TikiLib::lib('tiki')->get_perm_object($parentDirId, 'file gallery', TikiLib::lib('filegal')->get_file_gallery_info($parentDirId));
        if ($parentPerms['tiki_p_admin_file_galleries'] === 'y' || $parentPerms['tiki_p_create_file_galleries'] === 'y') {
            $parent_info = $this->filegallib->get_file_gallery($parentDirId);

            $gal_info = [];     // replace_file_gallery merges with default

            $gal_info['name'] = $name;
            $gal_info['type'] = $parent_info['type'] === 'user' ? 'user' : 'default';
            $gal_info['user'] = $user;
            $gal_info['parentId'] = $parentDirId;

            $result = $this->filegallib->replace_file_gallery($gal_info);

            if ($result) {
                return 'd_' . $result;
            }
        }
        return false;
    }

    /**
     * Create file and return it's path or false on failed
     *
     * @param  string  $path  parent dir path
     * @param string  $name  new file name
     * @return string|bool
     * @author Dmitry (dio) Levashov
     **/
    protected function _mkfile($path, $name)
    {
    }

    /**
     * Create symlink. FTP driver does not support symlinks.
     *
     * @param  string  $target  link target
     * @param  string  $path    symlink path
     * @return bool
     * @author Dmitry (dio) Levashov
     **/
    protected function _symlink($target, $path, $name)
    {
        return false;
    }

    /**
     * Copy file into another file
     *
     * @param  string  $source     source file path
     * @param  string  $targetDir  target directory path
     * @param  string  $name       new file name
     * @return bool
     **/
    protected function _copy($source, $targetDir, $name)
    {
        $ar = explode('_', $source);
        if (count($ar) === 2) {
            $isgal = $ar[0] === 'd';
            $source = $ar[1];
        } else {
            $isgal = true;
        }
        $name = trim(strip_tags($name));
        if (! $isgal) {
            $srcId = $this->pathToId($source);
        } else {
            return $this->setError(elFinder::ERROR_COPY, $this->_path($source));
        }
        $targetDirId = $this->pathToId($targetDir);
        $targetPerms = TikiLib::lib('tiki')->get_perm_object($targetDirId, 'file gallery', TikiLib::lib('filegal')->get_file_gallery_info($targetDirId));

        $canCopy = ($targetPerms['tiki_p_admin_file_galleries'] === 'y' || $targetPerms['tiki_p_upload_files'] === 'y');

        if ($canCopy) {
            $result = $this->filegallib->duplicate_file($srcId, $targetDirId, $name);
            if ($result) {
                return true;
            }
        }
        return false;
    }

    /**
     * Move file into another parent dir and/or rename.
     * Return new file path or false.
     *
     * @param  string  $source  source file path
     * @param  string  $targetDir  target dir path
     * @param  string  $name    file name
     * @return string|bool
     **/
    protected function _move($source, $targetDir, $name)
    {
        $ar = explode('_', $source);
        if (count($ar) === 2) {
            $isgal = $ar[0] === 'd';
            $source = $ar[1];
        } else {
            $isgal = true;
        }
        $name = trim(strip_tags($name));
        if (! $isgal) {
            $srcDirId = $this->options['accessControlData']['parentIds']['files'][$this->pathToId($source)];
        } else {
            $srcDirId = $this->pathToId($source);
        }
        $srcPerms = TikiLib::lib('tiki')->get_perm_object($srcDirId, 'file gallery', TikiLib::lib('filegal')->get_file_gallery_info($srcDirId));
        $targetDirId = $this->pathToId($targetDir);
        if ($srcDirId == $targetDirId) {
            $targetPerms = $srcPerms;
        } else {
            $targetPerms = TikiLib::lib('tiki')->get_perm_object($targetDirId, 'file gallery', TikiLib::lib('filegal')->get_file_gallery_info($targetDirId));
        }
        $canMove = ($srcPerms['tiki_p_admin_file_galleries'] === 'y' && $targetPerms['tiki_p_admin_file_galleries'] === 'y') ||
                ($srcPerms['tiki_p_remove_files'] === 'y' && $targetPerms['tiki_p_upload_files'] === 'y');

        if ($isgal) {
            if ($canMove) {
                $result = $this->fileGalleriesTable->update(
                    [
                        'name' => $name,
                        'parentId' => $targetDirId,
                    ],
                    ['galleryId' => $srcDirId]
                );
                if ($result) {
                    TikiLib::events()->trigger('tiki.filegallery.update', [
                        'type' => 'file gallery',
                        'object' => $srcDirId,
                    ]);
                    return 'd_' . $srcDirId;
                }
            }
        } else {
            if ($srcPerms['tiki_p_edit_gallery_file'] === 'y' && ($srcDirId !== $targetDirId || $canMove)) {
                $result = $this->filesTable->update(
                    [
                        'name' => $name,
                        'galleryId' => $targetDirId,
                    ],
                    ['fileId' => $this->pathToId($source)]
                );
                if ($result) {
                    TikiLib::events()->trigger('tiki.file.update', [
                            'type' => 'file',
                            'object' => $this->pathToId($source),
                    ]);
                    return 'f_' . $this->pathToId($source);
                }
            }
        }
        return '';
    }

    private function pathToId($path)
    {
        return preg_replace('/[df]_/', '', $path);
    }

    /**
     * Remove file
     *
     * @param  string $path file path
     * @return bool
     * @throws Exception
     */
    protected function _unlink($path)
    {
        $fileId = $this->pathToId($path);
        $galleryId = $this->options['accessControlData']['parentIds']['files'][$fileId];
        $perms = TikiLib::lib('tiki')->get_perm_object($galleryId, 'file gallery', TikiLib::lib('filegal')->get_file_gallery_info($galleryId));
        if ($perms['tiki_p_remove_files'] === 'y') {
            $fileInfo = TikiLib::lib('filegal')->get_file_info($fileId, false, false);
            return $this->filegallib->remove_file($fileInfo);
        } else {
            return false;
        }
    }

    /**
     * Remove dir
     *
     * @param  string $path dir path
     * @return bool
     * @throws Exception
     */
    protected function _rmdir($path)
    {
        $galleryId = $this->pathToId($path);
        $gal_info = TikiLib::lib('filegal')->get_file_gallery_info($galleryId);
        $perms = TikiLib::lib('tiki')->get_perm_object($galleryId, 'file gallery', $gal_info);
        if (
            $perms['tiki_p_admin_file_galleries'] === 'y' ||
                ($gal_info['type'] === 'user' && $perms['tiki_p_create_file_galleries'])
        ) {     // users can create and remove their own gals only
            $result = $this->filegallib->remove_file_gallery($this->pathToId($path), $this->pathToId($path));
            return $result && $result->numRows();
        } else {
            return false;
        }
    }

    /**
     * undocumented function
     *
     * @return void
     **/
    protected function _setContent($path, $fp)
    {
        rewind($fp);
        $fstat = fstat($fp);
        $size = $fstat['size'];
    }

    /**
     * Create new file and write into it from file pointer.
     * Return new file path or false on error.
     *
     * @param  resource $fp file pointer
     * @param  string $dir target dir path
     * @param  string $name file name
     * @param array $stat   file info
     * @return bool|string
     * @throws Exception
     */
    protected function _save($fp, $dir, $name, $stat)
    {
        $this->clearcache();

        $id = $this->_joinPath($dir, $name);
        rewind($fp);
        $size = $stat['size'];

        $data = '';
        while (! feof($fp)) {
            $data .= fread($fp, 8192);
        }
        //fclose($fp);

        $galleryId = $this->pathToId($dir);
        //$image_x=640;
        //$image_y=480;
        $fileId = 0;

        // elFinder assigns standard mime types like application/vnd.ms-word to ms doc, we use application/msword etc in tiki for some obscure reason :(
        if (strpos($stat['mime'], 'application/vnd.ms-') !== false) {
            $stat['mime'] = str_replace('application/vnd.ms-', 'application/ms', $stat['mime']);
        } elseif ($stat['mime'] === 'unknown' || $stat['mime'] === 'application/octet-stream') {
            if (strpos($name, '.h5p') === strlen($name) - 4) {  // cover some Tiki-specific mime types
                $stat['mime'] = 'application/zip';
            }
        }
        $gal_info = TikiLib::lib('filegal')->get_file_gallery_info($galleryId);

        $perms = TikiLib::lib('tiki')->get_perm_object($galleryId, 'file gallery', $gal_info);
        if ($perms['tiki_p_upload_files'] === 'y') {
               //checking if gallery has dimensions restrictions
            $image_x = $gal_info["image_max_size_x"];
            $image_y = $gal_info["image_max_size_y"];

            if (! $image_x) {
                $image_x = null;
            }
            if (! $image_y) {
                $image_y = null;
            }

            $fileId = $this->filegallib->upload_single_file(
                [
                    'galleryId' => $galleryId,
                    'name' => $this->fileGalleriesTable->fetchOne('name', ['galleryId' => $galleryId])
                ],
                $name,
                $size,
                $stat['mime'],
                $data,
                '',
                $image_x,
                $image_y
            );
        }

        if ($fileId) {
            $this->options['accessControlData']['parentIds']['files'][$fileId] = $galleryId;
            return 'f_' . $fileId;
        } else {
            return false;
        }
    }

    /**
     * Get file contents
     *
     * @param  string  $path  file path
     * @return string|false
     * @author Dmitry (dio) Levashov
     **/
    protected function _getContents($path)
    {
    }

    /**
     * Write a string to a file
     *
     * @param  string  $path     file path
     * @param  string  $content  new file content
     * @return bool
     * @author Dmitry (dio) Levashov
     **/
    protected function _filePutContents($path, $content)
    {
    }

    /**
     * Detect available archivers
     * Only support un/zip in tiki filegals so far
     *
     * @return void
     **/
    protected function _checkArchivers()
    {
        global $tiki_p_batch_upload_files;
        if ($tiki_p_batch_upload_files !== 'y') {
            $this->options['archivers'] = $this->options['archive'] = [];
            return;
        }
        $arcs = [
            'create'  => [],
            'extract' => []
            ];

        $arcs['create']['application/zip']  = ['cmd' => 'tikizip', 'argc' => '', 'ext' => 'zip'];
        $arcs['extract']['application/zip'] = ['cmd' => 'tikiunzip', 'argc' => '',  'ext' => 'zip'];

        $this->archivers = $arcs;
    }

    /**
     * Unpack archive
     *
     * @param  string  $path  archive path
     * @param  array   $arc   archiver command and arguments (same as in $this->archivers)
     * @return void
     * @author Dmitry (dio) Levashov
     * @author Alexey Sukhotin
     **/
    protected function _unpack($path, $arc)
    {
        return;
    }

    /**
     * Recursive symlinks search
     *
     * @param  string  $path  file/dir path
     * @return bool
     * @author Dmitry (dio) Levashov
     **/
    protected function _findSymlinks($path)
    {
        return false;
    }

    /**
     * Extract files from archive
     *
     * @param  string  $path  archive path
     * @param  array   $arc   archiver command and arguments (same as in $this->archivers)
     * @return true
     * @author Dmitry (dio) Levashov,
     * @author Alexey Sukhotin
     **/
    protected function _extract($path, $arc)
    {
        $ar = explode('_', $path);
        if (count($ar) === 2) {
            $isgal = $ar[0] === 'd';
            $fileId = $ar[1];
        } else {
            $isgal = true;
        }
        if (! $isgal) {
            $dirId = $this->options['accessControlData']['parentIds']['files'][$this->pathToId($fileId)];
        } else {
            return false;
        }
        $perms = TikiLib::lib('tiki')->get_perm_object($dirId, 'file gallery', TikiLib::lib('filegal')->get_file_gallery_info($dirId));

        if ($perms['tiki_p_upload_files'] === 'y' && $perms['tiki_p_batch_upload_files'] === 'y') {
            global $user, $prefs;
            $errors = null;
            $fp = null;
            $file = \Tiki\FileGallery\File::id($fileId);
            // check max files size
            if ($this->options['maxArcFilesSize'] > 0 && $this->options['maxArcFilesSize'] < $file->filesize) {
                return $this->setError(elFinder::ERROR_ARC_MAXSIZE);
            }
            // write out to a temp file as process_batch_file_upload deletes the filepath file
            $filepath = $this->tmpname($path);
            $filepath = str_replace(TEMP_PATH . '/', TEMP_CACHE_PATH . '/', $filepath); // this one has to be in a different place otherwise unzip fails
            $fp = $this->tmbPath
                ? @fopen($filepath, 'w+')
                : @tmpfile();

            if ($fp) {
                fwrite($fp, $file->getContents());
                fclose($fp);

                $this->filegallib->process_batch_file_upload($dirId, $filepath, $user, '', $errors);

                if ($errors) {
                    return $this->setError(elFinder::ERROR_EXTRACT);
                }
            }
        }

        $dirStr = 'd_' . $dirId;
        $this->clearcache();
        $this->stat($dirStr);
        $ret = $this->_scandir($dirStr);

        return $dirStr;
    }

    /**
     * Create archive and return its path
     *
     * @param  string  $dir    target dir
     * @param  array   $files  files names list
     * @param  string  $name   archive name
     * @param  array   $arc    archiver options
     * @return string|bool
     * @author Dmitry (dio) Levashov,
     * @author Alexey Sukhotin
     **/
    protected function _archive($dir, $files, $name, $arc)
    {
        return false;
    }

    /**
     * Change file mode (chmod)
     *
     * @param  string $path file path
     * @param  string $mode octal string such as '0755'
     * @return bool
     * @author David Bartle,
     **/
    protected function _chmod($path, $mode)
    {
        // Not used in Tiki
    }
}
