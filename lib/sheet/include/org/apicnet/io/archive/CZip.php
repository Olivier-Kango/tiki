<?php

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"],basename(__FILE__)) !== false) {
  header("location: index.php");
  exit;
}


# 
# PHPZip v1.2 by Sext (sext@neud.net) 2002-11-18
#     (Changed: 2003-03-01)
# 
# Makes zip archive
#
# Based on "Zip file creation class", uses zLib
# Modification pour prendre en compte la décompression d'un zip  (c) @PICNet 2004
#

APIC::import("org.apicnet.io.File");

class CZip {

    public function __construct(){}

    public function Zip($dir, $zipfilename){
        if (@function_exists('gzcompress')) {

            $file = new File($zipfilename, TRUE);
            if ($file->exists()) {
                $file->delFile();
                $file->createFile();
            }

            $curdir = getcwd();
            if (is_array($dir)) {
                    $filelist = $dir;
            } else {
                $filelist = $this -> GetFileList($dir);
            }


            if ((!empty($dir))&&(!is_array($dir))&&(file_exists($dir))) chdir($dir);
            else chdir($curdir);

            if (count($filelist)>0) {
                $root = dirname ($filelist[0]);
                foreach($filelist as $filename) {
                    if (is_file($filename)) {
                        $fd = fopen ($filename, "r");
                        $content = fread ($fd, filesize ($filename));
                        fclose ($fd);


                        if (is_array($dir)) {
                            $dirname  = dirname($filename);
                            $filename = basename($filename);
                            if ($dirname != $root) $filename = str_replace($root."/", "", $dirname)."/".$filename;
                        }

                        $this -> addFile($content, $filename);
                    }
                }
                $out = $this -> file();

                chdir($curdir);
                $fp = fopen($zipfilename, "w");
                fwrite($fp, $out, strlen($out));
                fclose($fp);
            }
            return 1;
        }
        else return 0;
    }

    public function GetFileList($dir){
        if (file_exists($dir)) {
            $args = func_get_args();
            $pref = $args[1];

            $dh = opendir($dir);
            while($files = readdir($dh)) {
                if (($files!=".")&&($files!="..")) {
                    if (is_dir($dir.$files)) {
                        $curdir = getcwd();
                        chdir($dir.$files);
                        $file = array_merge($file, $this -> GetFileList("", "$pref$files/"));
                        chdir($curdir);
                    }
                    else $file[]=$pref.$files;
                }
            }
            closedir($dh);
        }
        return $file;
    }

    public $datasec      = array();
    public $ctrl_dir     = array();
    public $eof_ctrl_dir = "\x50\x4b\x05\x06\x00\x00\x00\x00";
    public $old_offset   = 0;

    /**
     * Converts an Unix timestamp to a four byte DOS date and time format (date
     * in high two bytes, time in low two bytes allowing magnitude comparison).
     *
     * @param  integer  the current Unix timestamp
     *
     * @return integer  the current date in a four byte DOS format
     *
     * @access private
     */
    public function unix2DosTime($unixtime = 0) {
        $timearray = ($unixtime == 0) ? getdate() : getdate($unixtime);

        if ($timearray['year'] < 1980) {
            $timearray['year']    = 1980;
            $timearray['mon']     = 1;
            $timearray['mday']    = 1;
            $timearray['hours']   = 0;
            $timearray['minutes'] = 0;
            $timearray['seconds'] = 0;
        } // end if

        return (($timearray['year'] - 1980) << 25) | ($timearray['mon'] << 21) | ($timearray['mday'] << 16) |
                ($timearray['hours'] << 11) | ($timearray['minutes'] << 5) | ($timearray['seconds'] >> 1);
    }


    /**
     * Adds "file" to archive
     *
     * @param  string   file contents
     * @param  string   name of the file in the archive (may contains the path)
     * @param  integer  the current timestamp
     *
     * @access public
     */
    public function addFile($data, $name, $time = 0){
        $name     = str_replace('\\', '/', $name);

        $dtime    = dechex($this->unix2DosTime($time));
        $hexdtime = '\x' . $dtime[6] . $dtime[7]
                  . '\x' . $dtime[4] . $dtime[5]
                  . '\x' . $dtime[2] . $dtime[3]
                  . '\x' . $dtime[0] . $dtime[1];
        eval('$hexdtime = "' . $hexdtime . '";');

        $fr   = "\x50\x4b\x03\x04";
        $fr   .= "\x14\x00";            // ver needed to extract
        $fr   .= "\x00\x00";            // gen purpose bit flag
        $fr   .= "\x08\x00";            // compression method
        $fr   .= $hexdtime;             // last mod time and date

        // "local file header" segment
        $unc_len = strlen($data);
        $crc     = crc32($data);
        $zdata   = gzcompress($data);
        $c_len   = strlen($zdata);
        $zdata   = substr(substr($zdata, 0, strlen($zdata) - 4), 2); // fix crc bug
        $fr      .= pack('V', $crc);             // crc32
        $fr      .= pack('V', $c_len);           // compressed filesize
        $fr      .= pack('V', $unc_len);         // uncompressed filesize
        $fr      .= pack('v', strlen($name));    // length of filename
        $fr      .= pack('v', 0);                // extra field length
        $fr      .= $name;

        // "file data" segment
        $fr .= $zdata;

        // "data descriptor" segment (optional but necessary if archive is not
        // served as file)
        $fr .= pack('V', $crc);                 // crc32
        $fr .= pack('V', $c_len);               // compressed filesize
        $fr .= pack('V', $unc_len);             // uncompressed filesize

        // add this entry to array
        $this -> datasec[] = $fr;
        $new_offset        = strlen(implode('', $this->datasec));

        // now add to central directory record
        $cdrec = "\x50\x4b\x01\x02";
        $cdrec .= "\x00\x00";                // version made by
        $cdrec .= "\x14\x00";                // version needed to extract
        $cdrec .= "\x00\x00";                // gen purpose bit flag
        $cdrec .= "\x08\x00";                // compression method
        $cdrec .= $hexdtime;                 // last mod time & date
        $cdrec .= pack('V', $crc);           // crc32
        $cdrec .= pack('V', $c_len);         // compressed filesize
        $cdrec .= pack('V', $unc_len);       // uncompressed filesize
        $cdrec .= pack('v', strlen($name) ); // length of filename
        $cdrec .= pack('v', 0 );             // extra field length
        $cdrec .= pack('v', 0 );             // file comment length
        $cdrec .= pack('v', 0 );             // disk number start
        $cdrec .= pack('v', 0 );             // internal file attributes
        $cdrec .= pack('V', 32 );            // external file attributes - 'archive' bit set

        $cdrec .= pack('V', $this -> old_offset ); // relative offset of local header
        $this -> old_offset = $new_offset;

        $cdrec .= $name;

        // optional extra field, file comment goes here
        // save to central directory
        $this -> ctrl_dir[] = $cdrec;
    }


    /**
     * Dumps out file
     *
     * @return  string  the zipped file
     *
     * @access public
     */
    public function file(){
        $data    = implode('', $this -> datasec);
        $ctrldir = implode('', $this -> ctrl_dir);

        return
            $data .
            $ctrldir .
            $this -> eof_ctrl_dir .
            pack('v', sizeof($this -> ctrl_dir)) .  // total # of entries "on this disk"
            pack('v', sizeof($this -> ctrl_dir)) .  // total # of entries overall
            pack('V', strlen($ctrldir)) .           // size of central dir
            pack('V', strlen($data)) .              // offset to start of central dir
            "\x00\x00";                             // .zip file comment length
    }

    public function createDir($dir){
        if (preg_match("/(\/$)/i", $dir)) @mkdir (substr($dir, 0, strlen($dir) - 1));
        else @mkdir ($dir);
    }


    public function createFile($file, $data){
        $file = new File($file, TRUE);
        if ($file->exists()) {
            $file->delFile();
            $file->createFile();
        }
        $file->writeData($data);
    }


    public function extract($dir, $zipfilename){
        $zip = new ZipArchive();
        if ($zip->open($zipfilename) === TRUE) {
            $this->createDir($dir);
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                $fileinfo = pathinfo($filename);

                if ($fileinfo['dirname'] !== '.') {
                    $this->createDir($dir . '/' . $fileinfo['dirname']);
                }

                if (!empty($fileinfo['basename'])) {
                    $contents = $zip->getFromIndex($i);
                    $this->createFile($dir . '/' . $filename, $contents);
                }
            }
            $zip->close();
        }
    }
}
