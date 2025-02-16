<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
    header('location: index.php');
    exit;
}

/**
 * GZIP stuff.
 *
 * Note that we use gzopen()/gzwrite() instead of gzcompress() even if
 * gzcompress() is available. Gzcompress() puts out data with
 * different headers --- in particular it includes an "adler-32"
 * checksum rather than a "CRC32" checksum. Since we need the CRC-32
 * checksum, and since not all PHP's have gzcompress(), we'll just
 * stick with gzopen().
 */
function gzip_cleanup()
{
    global $gzip_tmpfile;

    if ($gzip_tmpfile) {
        @unlink($gzip_tmpfile);
    }
}

function gzip_tempnam()
{
    global $gzip_tmpfile;

    if (! $gzip_tmpfile) {
        //FIXME: does this work on non-unix machines?
        $gzip_tmpfile = tempnam('/tmp', 'wkzip');

        register_shutdown_function('gzip_cleanup');
    }

    return $gzip_tmpfile;
}

function gzip_compress($data)
{
    $filename = gzip_tempnam();

    if (! ($fp = gzopen($filename, 'wb'))) {
        throw new Exception(sprintf('%s failed', 'gzopen'));
    }

    gzwrite($fp, $data, strlen($data));

    if (! gzclose($fp)) {
        throw new Exception(sprintf('%s failed', 'gzclose'));
    }

    $size = filesize($filename);

    if (! ($fp = fopen($filename, 'rb'))) {
        throw new Exception(sprintf('%s failed', 'fopen'));
    }

    if (! ($z = fread($fp, $size)) || strlen($z) != $size) {
        throw new Exception(sprintf('%s failed', 'fread'));
    }

    if (! fclose($fp)) {
        throw new Exception(sprintf('%s failed', 'fclose'));
    }

    unlink($filename);
    return $z;
}

function gzip_uncompress($data)
{
    $filename = gzip_tempnam();

    if (! ($fp = fopen($filename, 'wb'))) {
        throw new Exception(sprintf('%s failed', 'fopen'));
    }

    fwrite($fp, $data, strlen($data));

    if (! fclose($fp)) {
        throw new Exception(sprintf('%s failed', 'fclose'));
    }

    if (! ($fp = gzopen($filename, 'rb'))) {
        throw new Exception(sprintf('%s failed', 'gzopen'));
    }

    $unz = '';

    while ($buf = gzread($fp, 4096)) {
        $unz .= $buf;
    }

    if (! gzclose($fp)) {
        throw new Exception(sprintf("%s failed", 'gzclose'));
    }

    unlink($filename);
    return $unz;
}

/**
 * CRC32 computation. Hacked from Info-zip's zip-2.3 source code.
 */
function zip_crc32($str, $crc = 0)
{
    static $zip_crc_table;

    if (empty($zip_crc_table)) {
        /* NOTE: The range of PHP ints seems to be -0x80000000 to 0x7fffffff.
         * So, had to munge these constants.
         */
        $zip_crc_table = [
                0x00000000,
                0x77073096,
                -0x11f19ed4,
                -0x66f6ae46,
                0x076dc419,
                0x706af48f,
                -0x169c5acb,
                -0x619b6a5d,
                0x0edb8832,
                0x79dcb8a4,
                -0x1f2a16e2,
                -0x682d2678,
                0x09b64c2b,
                0x7eb17cbd,
                -0x1847d2f9,
                -0x6f40e26f,
                0x1db71064,
                0x6ab020f2,
                -0x0c468eb8,
                -0x7b41be22,
                0x1adad47d,
                0x6ddde4eb,
                -0x0b2b4aaf,
                -0x7c2c7a39,
                0x136c9856,
                0x646ba8c0,
                -0x029d0686,
                -0x759a3614,
                0x14015c4f,
                0x63066cd9,
                -0x05f0c29d,
                -0x72f7f20b,
                0x3b6e20c8,
                0x4c69105e,
                -0x2a9fbe1c,
                -0x5d988e8e,
                0x3c03e4d1,
                0x4b04d447,
                -0x2df27a03,
                -0x5af54a95,
                0x35b5a8fa,
                0x42b2986c,
                -0x2444362a,
                -0x534306c0,
                0x32d86ce3,
                0x45df5c75,
                -0x2329f231,
                -0x542ec2a7,
                0x26d930ac,
                0x51de003a,
                -0x3728ae80,
                -0x402f9eea,
                0x21b4f4b5,
                0x56b3c423,
                -0x30456a67,
                -0x47425af1,
                0x2802b89e,
                0x5f058808,
                -0x39f3264e,
                -0x4ef416dc,
                0x2f6f7c87,
                0x58684c11,
                -0x3e9ee255,
                -0x4999d2c3,
                0x76dc4190,
                0x01db7106,
                -0x672ddf44,
                -0x102aefd6,
                0x71b18589,
                0x06b6b51f,
                -0x60401b5b,
                -0x17472bcd,
                0x7807c9a2,
                0x0f00f934,
                -0x69f65772,
                -0x1ef167e8,
                0x7f6a0dbb,
                0x086d3d2d,
                -0x6e9b9369,
                -0x199ca3ff,
                0x6b6b51f4,
                0x1c6c6162,
                -0x7a9acf28,
                -0x0d9dffb2,
                0x6c0695ed,
                0x1b01a57b,
                -0x7df70b3f,
                -0x0af03ba9,
                0x65b0d9c6,
                0x12b7e950,
                -0x74414716,
                -0x03467784,
                0x62dd1ddf,
                0x15da2d49,
                -0x732c830d,
                -0x042bb39b,
                0x4db26158,
                0x3ab551ce,
                -0x5c43ff8c,
                -0x2b44cf1e,
                0x4adfa541,
                0x3dd895d7,
                -0x5b2e3b93,
                -0x2c290b05,
                0x4369e96a,
                0x346ed9fc,
                -0x529877ba,
                -0x259f4730,
                0x44042d73,
                0x33031de5,
                -0x55f5b3a1,
                -0x22f28337,
                0x5005713c,
                0x270241aa,
                -0x41f4eff0,
                -0x36f3df7a,
                0x5768b525,
                0x206f85b3,
                -0x46992bf7,
                -0x319e1b61,
                0x5edef90e,
                0x29d9c998,
                -0x4f2f67de,
                -0x3828574c,
                0x59b33d17,
                0x2eb40d81,
                -0x4842a3c5,
                -0x3f459353,
                -0x12477ce0,
                -0x65404c4a,
                0x03b6e20c,
                0x74b1d29a,
                -0x152ab8c7,
                -0x622d8851,
                0x04db2615,
                0x73dc1683,
                -0x1c9cf4ee,
                -0x6b9bc47c,
                0x0d6d6a3e,
                0x7a6a5aa8,
                -0x1bf130f5,
                -0x6cf60063,
                0x0a00ae27,
                0x7d079eb1,
                -0x0ff06cbc,
                -0x78f75c2e,
                0x1e01f268,
                0x6906c2fe,
                -0x089da8a3,
                -0x7f9a9835,
                0x196c3671,
                0x6e6b06e7,
                -0x012be48a,
                -0x762cd420,
                0x10da7a5a,
                0x67dd4acc,
                -0x06462091,
                -0x71411007,
                0x17b7be43,
                0x60b08ed5,
                -0x29295c18,
                -0x5e2e6c82,
                0x38d8c2c4,
                0x4fdff252,
                -0x2e44980f,
                -0x5943a899,
                0x3fb506dd,
                0x48b2364b,
                -0x27f2d426,
                -0x50f5e4b4,
                0x36034af6,
                0x41047a60,
                -0x209f103d,
                -0x579820ab,
                0x316e8eef,
                0x4669be79,
                -0x349e4c74,
                -0x43997ce6,
                0x256fd2a0,
                0x5268e236,
                -0x33f3886b,
                -0x44f4b8fd,
                0x220216b9,
                0x5505262f,
                -0x3a45c442,
                -0x4d42f4d8,
                0x2bb45a92,
                0x5cb36a04,
                -0x3d280059,
                -0x4a2f30cf,
                0x2cd99e8b,
                0x5bdeae1d,
                -0x649b3d50,
                -0x139c0dda,
                0x756aa39c,
                0x026d930a,
                -0x63f6f957,
                -0x14f1c9c1,
                0x72076785,
                0x05005713,
                -0x6a40b57e,
                -0x1d4785ec,
                0x7bb12bae,
                0x0cb61b38,
                -0x6d2d7165,
                -0x1a2a41f3,
                0x7cdcefb7,
                0x0bdbdf21,
                -0x792c2d2c,
                -0x0e2b1dbe,
                0x68ddb3f8,
                0x1fda836e,
                -0x7e41e933,
                -0x0946d9a5,
                0x6fb077e1,
                0x18b74777,
                -0x77f7a51a,
                -0x00f09590,
                0x66063bca,
                0x11010b5c,
                -0x709a6101,
                -0x079d5197,
                0x616bffd3,
                0x166ccf45,
                -0x5ff51d88,
                -0x28f22d12,
                0x4e048354,
                0x3903b3c2,
                -0x5898d99f,
                -0x2f9fe909,
                0x4969474d,
                0x3e6e77db,
                -0x512e95b6,
                -0x2629a524,
                0x40df0b66,
                0x37d83bf0,
                -0x564351ad,
                -0x2144613b,
                0x47b2cf7f,
                0x30b5ffe9,
                -0x42420de4,
                -0x35453d76,
                0x53b39330,
                0x24b4a3a6,
                -0x452fc9fb,
                -0x3228f96d,
                0x54de5729,
                0x23d967bf,
                -0x4c9985d2,
                -0x3b9eb548,
                0x5d681b02,
                0x2a6f2b94,
                -0x4bf441c9,
                -0x3cf3715f,
                0x5a05df1b,
                0x2d02ef8d
                    ];
    }

    $crc = ~$crc;

    for ($i = 0, $istrlen_str = strlen($str); $i < $istrlen_str; $i++) {
        $crc = ($zip_crc_table[($crc ^ ord($str[$i])) & 0xff] ^ (($crc >> 8) & 0xffffff));
    }

    return ~$crc;
}

define('GZIP_MAGIC', "\037\213");
define('GZIP_DEFLATE', 010);

function zip_deflate($content)
{
    // Compress content, and suck information from gzip header.
    $z = gzip_compress($content);

    // Suck OS type byte from gzip header. FIXME: this smells bad.
    extract(unpack("a2magic/Ccomp_type/Cflags/@9/Cos_type", $z));

    if ($magic != GZIP_MAGIC) {
        throw new Exception(sprintf('Bad %s', 'gzip magic'));
    }

    if ($comp_type != GZIP_DEFLATE) {
        throw new Exception(sprintf('Bad %s', 'gzip comp type'));
    }

    if (($flags & 0x3e) != 0) {
        throw new Exception(sprintf('Bad %s', sprintf('flags (0x%02x)', $flags)));
    }

    $gz_header_len = 10;
    $gz_data_len = strlen($z) - $gz_header_len - 8;

    if ($gz_data_len < 0) {
        throw new Exception('Gzip output is invalid, not enough data.');
    }

    extract(unpack('Vcrc32', substr($z, $gz_header_len + $gz_data_len)));

    return [
            substr($z, $gz_header_len, $gz_data_len), // gzipped data
            $crc32,                                   // crc
            $os_type                                  // OS type
    ];
}

function zip_inflate($data, $crc32, $uncomp_size)
{
    if (! function_exists('gzopen')) {
        global $request;

        $request->finish(_("Can't inflate data: zlib support not enabled in this PHP"));
    }

    // Reconstruct gzip header and ungzip the data.
    $mtime = time(); //(Bogus mtime)

    return gzip_uncompress(pack("a2CxV@10", GZIP_MAGIC, GZIP_DEFLATE, $mtime) . $data . pack('VV', $crc32, $uncomp_size));
}

function unixtime2dostime($unix_time)
{
    if ($unix_time % 1) {
        $unix_time++; // Round up to even seconds.
    }

    list($year, $month, $mday, $hour, $min, $sec) = explode(' ', TikiLib::date_format('%Y %m %e %H %M %S', $unix_time));

    if ($year < 1980) {
        list($year, $month, $mday, $hour, $min, $sec) = [
                1980,
                1,
                1,
                0,
                0,
                0
                ];
    }

    $dosdate = (($year - 1980) << 9) | ($month << 5) | $mday;
    $dostime = ($hour << 11) | ($min << 5) | ($sec >> 1);

    return [
            $dosdate,
            $dostime
            ];
}

function dostime2unixtime($dosdate, $dostime)
{
    $mday = $dosdate & 0x1f;

    $month = ($dosdate >> 5) & 0x0f;
    $year = 1980 + (($dosdate >> 9) & 0x7f);

    $sec = ($dostime & 0x1f) * 2;
    $min = ($dostime >> 5) & 0x3f;
    $hour = ($dostime >> 11) & 0x1f;

    return TikiLib::make_time($hour, $min, $sec, $month, $mday, $year);
}

/**
 * Class for zipfile creation.
 */
define('ZIP_DEFLATE', GZIP_DEFLATE);
define('ZIP_STORE', 0);
define('ZIP_CENTHEAD_MAGIC', "PK\001\002");
define('ZIP_LOCHEAD_MAGIC', "PK\003\004");
define('ZIP_ENDDIR_MAGIC', "PK\005\006");

class ZipWriter
{
    public $comment;
    public $nfiles;
    public $dir;
    public $offset;
    public function __construct($comment = '', $zipname = 'archive.zip')
    {
        $this->comment = $comment;

        $this->nfiles = 0;
        $this->dir = ''; // "Central directory block"
        $this->offset = 0; // Current file position.

        $zipname = addslashes($zipname);
        header("Content-Type: application/zip; name=\"$zipname\"");
        header("Content-Disposition: attachment; filename=\"$zipname\"");
    }

    public function addRegularFile($filename, $content, $attrib = false)
    {
        if (! $attrib) {
            $attrib = [];
        }

        $size = strlen($content);

        if (function_exists('gzopen')) {
            list($data, $crc32, $os_type) = zip_deflate($content);

            if (strlen($data) < $size) {
                $content = $data;   // Use compressed data.

                $comp_type = ZIP_DEFLATE;
            } else {
                unset($crc32);  // force plain store.
            }
        }

        if (! isset($crc32)) {
            $comp_type = ZIP_STORE;

            $crc32 = zip_crc32($content);
        }

        if (! empty($attrib['write_protected'])) {
            $atx = (0100444 << 16) | 1; // S_IFREG + read permissions to everybody.
        } else {
            $atx = (0100644 << 16); // Add owner write perms.
        }

        $ati = $attrib['is_ascii'] ? 1 : 0;

        if (empty($attrib['mtime'])) {
            $attrib['mtime'] = time();
        }

        list($mod_date, $mod_time) = unixtime2dostime($attrib['mtime']);

        // Construct parts common to "Local file header" and "Central
        // directory file header."
        if (! isset($attrib['extra_field'])) {
            $attrib['extra_field'] = '';
        }

        if (! isset($attrib['file_comment'])) {
            $attrib['file_comment'] = '';
        }

        $head = pack(
            "vvvvvVVVvv",
            20, // Version needed to extract (FIXME: is this right?)
            0, // Gen purp bit flag
            $comp_type,
            $mod_time,
            $mod_date,
            $crc32,
            strlen($content),
            $size,
            strlen($filename),
            strlen($attrib['extra_field'])
        );

        // Construct the "Local file header"
        $lheader = ZIP_LOCHEAD_MAGIC . $head . $filename . $attrib['extra_field'];

        // Construct the "central directory file header"
        $this->dir .= pack("a4CC", ZIP_CENTHEAD_MAGIC, 23, $os_type);
        $this->dir .= $head;
        $this->dir .= pack(
            "vvvVV",
            strlen($attrib['file_comment']),
            0, // Disk number start
            $ati, // Internal file attributes
            $atx, // External file attributes
            $this->offset // Relative offset of local header
        );
        $this->dir .= $filename . $attrib['extra_field'] . $attrib['file_comment'];

        // Output the "Local file header" and file contents.
        echo $lheader;
        echo $content;

        $this->offset += strlen($lheader) + strlen($content);
        $this->nfiles++;
    }

    public function finish()
    {
        // Output the central directory
        echo $this->dir;

        // Construct the "End of central directory record"
        echo ZIP_ENDDIR_MAGIC;
        echo pack(
            "vvvvVVv",
            0, // Number of this disk.
            0, // Number of disk with start of c dir
            $this->nfiles, // Number entries on this disk
            $this->nfiles, // Number entries
            strlen($this->dir), // Size of central directory
            $this->offset, // Offset of central directory
            strlen($this->comment)
        );
        echo $this->comment;
    }
}

/**
 * Class for reading zip files.
 *
 * BUGS:
 *
 * Many of the ExitWiki()'s should probably be warn()'s (eg. CRC mismatch).
 *
 * Only a subset of zip formats is recognized. (I think that
 * unsupported formats will be recognized as such rather than silently
 * munged.)
 *
 * We don't read the central directory. This means we don't see the
 * file attributes (text? read-only?), or file comments.
 *
 * Right now we ignore the file mod date and time, since we don't need it.
 */
class ZipReader
{
    public $fp;
    public function __construct($zipfile)
    {
        if (! is_string($zipfile)) {
            $this->fp = $zipfile; // File already open
        } elseif (! ($this->fp = fopen($zipfile, "rb"))) {
            throw new Exception(sprintf(_("Can't open zip file '%s' for reading"), $zipfile));
        }
    }

    protected function read($nbytes)
    {
        $chunk = fread($this->fp, $nbytes);

        if (strlen($chunk) != $nbytes) {
            throw new Exception("Unexpected EOF in zip file");
        }

        return $chunk;
    }

    public function done()
    {
        fclose($this->fp);

        return false;
    }

    public function readFile()
    {
        $head = $this->read(30);

        extract(
            unpack(
                'a4magic/vreq_version/vflags/vcomp_type'
                . '/vmod_time/vmod_date'
                . '/Vcrc32/Vcomp_size/Vuncomp_size'
                . '/vfilename_len/vextrafld_len',
                $head
            )
        );

        //FIXME: we should probably check $req_version.
        $attrib['mtime'] = dostime2unixtime($mod_date, $mod_time);

        if ($magic != ZIP_LOCHEAD_MAGIC) {
            if ($magic != ZIP_CENTHEAD_MAGIC) {
                // FIXME: better message?
                ExitWiki(sprintf('Bad header type: %s', $magic));
            }

            return $this->done();
        }

        if (($flags & 0x21) != 0) {
            ExitWiki('Encryption and/or zip patches not supported.');
        }

        if (($flags & 0x08) != 0) {
            // FIXME: better message?
            ExitWiki('Postponed CRC not yet supported.');
        }

        $filename = $this->read($filename_len);

        if ($extrafld_len != 0) {
            $attrib['extra_field'] = $this->read($extrafld_len);
        }

        $data = $this->read($comp_size);

        if ($comp_type == ZIP_DEFLATE) {
            $data = zip_inflate($data, $crc32, $uncomp_size);
        } elseif ($comp_type == ZIP_STORE) {
            $crc = zip_crc32($data);

            if ($crc32 != $crc) {
                ExitWiki(sprintf('CRC mismatch %x != %x', $crc, $crc32));
            }
        } else {
            ExitWiki(sprintf('Compression method %s unsupported', $comp_method));
        }

        if (strlen($data) != $uncomp_size) {
            ExitWiki(sprintf('Uncompressed size mismatch %d != %d', strlen($data), $uncomp_size));
        }

        return [
                $filename,
                $data,
                $attrib
                ];
    }
}

/**
 * Routines for Mime mailification of pages.
 */
//FIXME: these should go elsewhere (libmime?).

/**
 * Routines for quoted-printable en/decoding.
 */
function QuotedPrintableEncode($string)
{
    // Quote special characters in line.
    $quoted = '';

    while ($string) {
        // The complicated regexp is to force quoting of trailing spaces.
        preg_match('/^([ !-<>-~]*)(?:([!-<>-~]$)|(.))/s', $string, $match);

        $quoted .= $match[1] . $match[2];

        if (! empty($match[3])) {
            $quoted .= sprintf('=%02X', ord($match[3]));
        }

        $string = substr($string, strlen($match[0]));
    }

    // Split line.
    // This splits the line (preferably after white-space) into lines
    // which are no longer than 76 chars (after adding trailing '=' for
    // soft line break, but before adding \r\n.)
    return preg_replace('/(?=.{77})(.{10,74}[ \t]|.{71,73}[^=][^=])/s', "\\1=\r\n", $quoted);
}

function QuotedPrintableDecode($string)
{
    // Eliminate soft line-breaks.
    $string = preg_replace('/=[ \t\r]*\n/', '', $string);

    return quoted_printable_decode($string);
}

define('MIME_TOKEN_REGEXP', "[-!#-'*+.0-9A-Z^-~]+");

function MimeContentTypeHeader($type, $subtype, $params)
{
    $header = "Content-Type: $type/$subtype";

    reset($params);

    foreach ($params as $key => $val) {
        //FIXME: what about non-ascii printables in $val?
        if (! preg_match('/^' . MIME_TOKEN_REGEXP . '$/', $val)) {
            $val = '"' . addslashes($val) . '"';
        }

        $header .= ";\r\n  $key=$val";
    }

    return "$header\r\n";
}

function MimeMultipart($parts)
{
    global $mime_multipart_count;

    // The string "=_" can not occur in quoted-printable encoded data.
    $boundary = "=_multipart_boundary_" . ++$mime_multipart_count;

    $head = MimeContentTypeHeader('multipart', 'mixed', ['boundary' => $boundary]);

    $sep = "\r\n--$boundary\r\n";

    return $head . $sep . implode($sep, $parts) . "\r\n--$boundary--\r\n";
}

/**
 * For reference see:
 * http://www.nacs.uci.edu/indiv/ehood/MIME/2045/rfc2045.html
 * http://www.faqs.org/rfcs/rfc2045.html
 * (RFC 1521 has been superceeded by RFC 2045 & others).
 *
 * Also see http://www.faqs.org/rfcs/rfc2822.html
 *
 *
 * Notes on content-transfer-encoding.
 *
 * "7bit" means short lines of US-ASCII.
 * "8bit" means short lines of octets with (possibly) the high-order bit set.
 * "binary" means lines are not necessarily short enough for SMTP
 * transport, and non-ASCII characters may be present.
 *
 * Only "7bit", "quoted-printable", and "base64" are universally safe
 * for transport via e-mail. (Though many MTAs can/will be configured to
 * automatically convert encodings to a safe type if they receive
 * mail encoded in '8bit' and/or 'binary' encodings.
 */
function MimeifyPageRevision($page)
{
    //$page = $revision->getPage();
    // FIXME: add 'hits' to $params
    $params = [
            'pagename' => $page['pageName'],
            'flags' => '',
            'author' => $page['user'],
            'version' => $page['version'],
            'lastmodified' => $page['lastModif']
            ];

    $params['author_id'] = $page['ip'];
    $params['summary'] = $page['comment'];

    if (isset($page['hits'])) {
        $params['hits'] = $page['hits'];
    }

    $params['description'] = $page['description'];

    $params['charset'] = 'utf-8';

    // Non-US-ASCII is not allowed in Mime headers (at least not without
    // special handling) --- so we urlencode all parameter values.
    foreach ($params as $key => $val) {
        $params[$key] = rawurlencode($val);
    }

    $out = MimeContentTypeHeader('application', 'x-tikiwiki', $params);
    $out .= sprintf("Content-Transfer-Encoding: %s\r\n", 'binary');
    $out .= "\r\n";
    $lines = explode("\n", $page["data"]);

    foreach ($lines as $line) {
        // This is a dirty hack to allow saving binary text files. See above.
        $line = rtrim($line);

        $out .= "$line\r\n";
    }

    return $out;
}

/**
 * Routines for parsing Mime-ified phpwiki pages.
 */
function ParseRFC822Headers(&$string)
{
    if (preg_match("/^From (.*)\r?\n/", $string, $match)) {
        $headers['from '] = preg_replace('/^\s+|\s+$/', '', $match[1]);

        $string = substr($string, strlen($match[0]));
    }

    while (preg_match('/^([!-9;-~]+) [ \t]* : [ \t]* ' . '( .* \r?\n (?: [ \t] .* \r?\n)* )/x', $string, $match)) {
        $headers[strtolower($match[1])] = preg_replace('/^\s+|\s+$/', '', $match[2]);

        $string = substr($string, strlen($match[0]));
    }

    if (empty($headers)) {
        return false;
    }

    if (! preg_match("/^\r?\n/", $string, $match)) {
        // No blank line after headers.
        return false;
    }

    $string = substr($string, strlen($match[0]));

    return $headers;
}

function ParseMimeContentType($string)
{
    // FIXME: Remove (RFC822 style comments).

    // Get type/subtype
    if (! preg_match(':^\s*(' . MIME_TOKEN_REGEXP . ')\s*' . '/' . '\s*(' . MIME_TOKEN_REGEXP . ')\s*:x', $string, $match)) {
        ExitWiki(sprintf("Bad %s", 'MIME content-type'));
    }

    $type = strtolower($match[1]);
    $subtype = strtolower($match[2]);
    $string = substr($string, strlen($match[0]));

    $param = [];

    while (preg_match('/^;\s*(' . MIME_TOKEN_REGEXP . ')\s*=\s*' . '(?:(' . MIME_TOKEN_REGEXP . ')|"((?:[^"\\\\]|\\.)*)") \s*/sx', $string, $match)) {
        //" <--kludge for brain-dead syntax coloring
        if (strlen($match[2])) {
            $val = $match[2];
        } else {
            $val = preg_replace('/[\\\\](.)/s', '\\1', $match[3]);
        }

        $param[strtolower($match[1])] = $val;

        $string = substr($string, strlen($match[0]));
    }

    return [$type, $subtype,$param];
}

function ParseMimeMultipart($data, $boundary)
{
    if (! $boundary) {
        ExitWiki('No boundary?');
    }

    $boundary = preg_quote($boundary);

    while (preg_match("/^(|.*?\n)--$boundary((?:--)?)[^\n]*\n/s", $data, $match)) {
        $data = substr($data, strlen($match[0]));

        if (! isset($parts)) {
            $parts = []; // First time through: discard leading chaff
        } else {
            if ($content = ParseMimeifiedPages($match[1])) {
                foreach ($content as $p) {
                    $parts[] = $p;
                }
            }
        }

        if ($match[2]) {
            return $parts; // End boundary found.
        }
    }

    ExitWiki('No end boundary?');
}

function GenerateFootnotesFromRefs($params)
{
    $footnotes = [];

    reset($params);

    foreach ($params as $p => $reference) {
        if (preg_match('/^ref([1-9][0-9]*)$/', $p, $m)) {
            $footnotes[$m[1]] = sprintf(_("[%d] See [%s]"), $m[1], rawurldecode($reference));
        }
    }

    if (count($footnotes) > 0) {
        ksort($footnotes);

        return "-----\n" . "!" . _("References") . "\n" . join("\n%%%\n", $footnotes) . "\n";
    } else {
        return '';
    }
}

// Convert references in meta-data to footnotes.
// Only zip archives generated by phpwiki 1.2.x or earlier should have
// references.
function ParseMimeifiedPages($data)
{
    if (! ($headers = ParseRFC822Headers($data)) || empty($headers['content-type'])) {
        //trigger_error( sprintf(_("Can't find %s"),'content-type header'),
        //  E_USER_WARNING );
        return false;
    }

    $typeheader = $headers['content-type'];

    if (! (list($type, $subtype, $params) = ParseMimeContentType($typeheader))) {
        trigger_error(sprintf("Can't parse %s: (%s)", 'content-type', $typeheader), E_USER_WARNING);

        return false;
    }

    if ("$type/$subtype" == 'multipart/mixed') {
        return ParseMimeMultipart($data, $params['boundary']);
    } elseif ("$type/$subtype" != 'application/x-phpwiki') {
        trigger_error(sprintf("Bad %s", "content-type: $type/$subtype"), E_USER_WARNING);

        return false;
    }

    // FIXME: more sanity checking?
    $page = [];
    $pagedata = [];
    $versiondata = [];

    foreach ($params as $key => $value) {
        if (empty($value)) {
            continue;
        }

        $value = rawurldecode($value);

        switch ($key) {
            case 'pagename':
            case 'version':
                $page[$key] = $value;
                break;

            case 'flags':
                if (preg_match('/PAGE_LOCKED/', $value)) {
                    $pagedata['locked'] = 'yes';
                }
                break;

            case 'created':
            case 'hits':
                $pagedata[$key] = $value;
                break;

            case 'lastmodified':
                $versiondata['mtime'] = $value;
                break;

            case 'author':
            case 'author_id':
            case 'summary':
            case 'markup':
                $versiondata[$key] = $value;
                break;
        }
    }

    // FIXME: do we need to try harder to find a pagename if we
    // haven't got one yet?
    if (! isset($versiondata['author'])) {
        global $request;

        $user = $request->getUser();
        $versiondata['author'] = $user->getId(); //FIXME:?
    }

    $encoding = strtolower($headers['content-transfer-encoding']);

    if ($encoding == 'quoted-printable') {
        $data = QuotedPrintableDecode($data);
    } elseif ($encoding && $encoding != 'binary') {
        ExitWiki(sprintf("Unknown %s", 'encoding type: $encoding'));
    }

    $data .= GenerateFootnotesFromRefs($params);

    $page['content'] = preg_replace('/[ \t\r]*\n/', "\n", chop($data));
    $page['pagedata'] = $pagedata;
    $page['versiondata'] = $versiondata;

    return [$page];
}
// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
