<?php
// CAREFUL: patch for header with  charset iso-8859-1 for tiki
//
// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2001 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Richard Heyes <richard@phpguru.org>                         |
// +----------------------------------------------------------------------+
require_once ('PEAR.php');

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"],basename(__FILE__)) !== false) {
  header("location: index.php");
}

/**
*  +----------------------------- IMPORTANT ------------------------------+
*  | Usage of this class compared to native php extensions such as        |
*  | mailparse or imap, is slow and may be feature deficient. If available|
*  | you are STRONGLY recommended to use the php extensions.              |
*  +----------------------------------------------------------------------+
*
* Mime Decoding class
*
* This class will parse a raw mime email and return
* the structure. Returned structure is similar to 
* that returned by imap_fetchstructure().
*
* USAGE: (assume $input is your raw email)
*
* $decode = new Mail_mimeDecode($input, "\r\n");
* $structure = $decode->decode();
* print_r($structure);
*
* Or statically:
*
* $params['input'] = $input;
* $structure = Mail_mimeDecode::decode($params);
* print_r($structure);
*
* TODO:
*  - Implement further content types, eg. multipart/parallel,
*    perhaps even message/partial.
*
* @author  Richard Heyes <richard@phpguru.org>
* @version $Revision: 1.8 $
* @package Mail
*/
class Mail_mimeDecode extends PEAR {

	/**
	 * The raw email to decode
	 * @var    string
	 */
	var $_input;

	/**
	 * The header part of the input
	 * @var    string
	 */
	var $_header;

	/**
	 * The body part of the input
	 * @var    string
	 */
	var $_body;

	/**
	 * If an error occurs, this is used to store the message
	 * @var    string
	 */
	var $_error;

	/**
	 * Flag to determine whether to include bodies in the
	 * returned object.
	 * @var    boolean
	 */
	var $_include_bodies;

	/**
	 * Flag to determine whether to decode bodies
	 * @var    boolean
	 */
	var $_decode_bodies;

	/**
	 * Flag to determine whether to decode headers
	 * @var    boolean
	 */
	var $_decode_headers;

	/**
	 * Variable to hold the line end type.
	 * @var    string
	 */
	var $_crlf;

	/**
	 * Constructor.
	 * 
	 * Sets up the object, initialise the variables, and splits and
	 * stores the header and body of the input.
	 *
	 * @param string The input to decode
	 * @param string CRLF type to use (CRLF/LF/CR)
	 * @access public
	 */
	function Mail_mimeDecode($input, $crlf = "\r\n") {
		$this->_crlf = $crlf;

		list($header, $body) = $this->_splitBodyHeader($input);
		$this->_input = $input;
		$this->_header = $header;
		$this->_body = $body;
		$this->_decode_bodies = false;
		$this->_include_bodies = true;
	}

	/**
	 * Begins the decoding process. If called statically
	 * it will create an object and call the decode() method
	 * of it.
	 * 
	 * @param array An array of various parameters that determine
	 *              various things:
	 *              include_bodies - Whether to include the body in the returned
	 *                               object.
	 *              decode_bodies  - Whether to decode the bodies
	 *                               of the parts. (Transfer encoding)
	 *              decode_headers - Whether to decode headers
	 *              input          - If called statically, this will be treated
	 *                               as the input
	 *              crlf           - If called statically, this will be used as
	 *                               the crlf value.
	 * @return object Decoded results
	 * @access public
	 */
	function decode($params = null) {

		// Have we been called statically? If so, create an object and pass details to that.
		if (!isset($this)AND isset($params['input'])) {
			if (isset($params['crlf']))
				$obj = new Mail_mimeDecode($params['input'], $params['crlf']);
			else
				$obj = new Mail_mimeDecode($params['input']);

			$structure = $obj->decode($params);

		// Called statically but no input
		} elseif (!isset($this)) {
			return $this->raiseError('Called statically and no input given');

		// Called via an object
		} else {
			$this->_include_bodies = isset($params['include_bodies']) ? $params['include_bodies'] : false;

			$this->_decode_bodies = isset($params['decode_bodies']) ? $params['decode_bodies'] : false;
			$this->_decode_headers = isset($params['decode_headers']) ? $params['decode_headers'] : false;

			$structure = $this->_decode($this->_header, $this->_body);

			if ($structure === false)
				$structure = $this->raiseError($this->_error);
		}

		return $structure;
	}

	/**
	 * Performs the decoding. Decodes the body string passed to it
	 * If it finds certain content-types it will call itself in a
	 * recursive fashion
	 * 
	 * @param string Header section
	 * @param string Body section
	 * @return object Results of decoding process
	 * @access private
	 */
	function _decode($headers, $body, $default_ctype = 'text/plain') {
		$return = new stdClass;

		$headers = $this->_parseHeaders($headers);

		foreach ($headers as $value) {
			if (isset($return->headers[strtolower($value['name'])])AND !is_array($return->headers[strtolower($value['name'])])) {
				$return->headers[strtolower($value['name'])] = array($return->headers[strtolower($value['name'])]);

				$return->headers[strtolower($value['name'])][] = $value['value'];
			} elseif (isset($return->headers[strtolower($value['name'])])) {
				$return->headers[strtolower($value['name'])][] = $value['value'];
			} else {
				$return->headers[strtolower($value['name'])] = $value['value'];
			}
		}

		reset ($headers);

		while (list($key, $value) = each($headers)) {
			$headers[$key]['name'] = strtolower($headers[$key]['name']);

			switch ($headers[$key]['name']) {
			case 'content-type':
				$content_type = $this->_parseHeaderValue($headers[$key]['value']);

				if (preg_match('/([0-9a-z+.-]+)\/([0-9a-z+.-]+)/i', $content_type['value'], $regs)) {
					$return->ctype_primary = $regs[1];

					$return->ctype_secondary = $regs[2];
				}

				if (isset($content_type['other'])) {
					while (list($p_name, $p_value) = each($content_type['other'])) {
						$return->ctype_parameters[$p_name] = $p_value;
					}
				}

				break;

			case 'content-disposition':
				$content_disposition = $this->_parseHeaderValue($headers[$key]['value']);

				$return->disposition = $content_disposition['value'];

				if (isset($content_disposition['other'])) {
					while (list($p_name, $p_value) = each($content_disposition['other'])) {
						$return->d_parameters[$p_name] = $p_value;
					}
				}

				break;

			case 'content-transfer-encoding':
				$content_transfer_encoding = $this->_parseHeaderValue($headers[$key]['value']);

				break;
			}
		}

		if (isset($content_type)) {
			switch (strtolower($content_type['value'])) {
			case 'text/plain':
				$encoding = isset($content_transfer_encoding) ? $content_transfer_encoding['value'] : '7bit';

				$this->_include_bodies ? $return->body = ($this->_decode_bodies ? $this->_decodeBody($body, $encoding) : $body)
					: null;
				break;

			case 'text/html':
				$encoding = isset($content_transfer_encoding) ? $content_transfer_encoding['value'] : '7bit';

				$this->_include_bodies ? $return->body = ($this->_decode_bodies ? $this->_decodeBody($body, $encoding) : $body)
					: null;
				break;

			case 'multipart/signed': // PGP
			case 'multipart/digest':
			case 'multipart/alternative':
			case 'multipart/related':
			case 'multipart/mixed':
				if (!isset($content_type['other']['boundary'])) {
					$this->_error = 'No boundary found for ' . $content_type['value'] . ' part';

					return false;
				}

				$default_ctype = (strtolower($content_type['value']) === 'multipart/digest') ? 'message/rfc822' : 'text/plain';

				$parts = $this->_boundarySplit($body, $content_type['other']['boundary']);

				for ($i = 0; $i < count($parts); $i++) {
					list($part_header, $part_body) = $this->_splitBodyHeader($parts[$i]);

					$part = $this->_decode($part_header, $part_body, $default_ctype);

					if ($part === false)
						$part = $this->raiseError($this->_error);

					$return->parts[] = $part;
				}

				break;

			case 'message/rfc822':
				$obj = new Mail_mimeDecode($body, $this->_crlf);

				$return->parts[] = $obj->decode(array('include_bodies' => $this->_include_bodies));
				unset ($obj);
				break;

			default:
				if (!isset($content_transfer_encoding['value']))
					$content_transfer_encoding['value'] = '7bit';

				$this->_include_bodies ?
					$return->body = ($this->_decode_bodies ? $this->_decodeBody($body, $content_transfer_encoding['value']) : $body)
					: null;
				break;
			}
		} else {
			$ctype = explode('/', $default_ctype);

			$return->ctype_primary = $ctype[0];
			$return->ctype_secondary = $ctype[1];
			$this->_include_bodies ? $return->body = ($this->_decode_bodies ? $this->_decodeBody($body) : $body) : null;
		}

		return $return;
	}

	/**
	 * Given a string containing a header and body
	 * section, this function will split them (at the first
	 * blank line) and return them.
	 * 
	 * @param string Input to split apart
	 * @return array Contains header and body section
	 * @access private
	 */
	function _splitBodyHeader($input) {
		$pos = strpos($input, $this->_crlf . $this->_crlf);

		if ($pos === false) {
			$this->_crlf = "\n";

			$pos = strpos($input, $this->_crlf . $this->_crlf);

			if ($pos === false) {
				$this->_error = 'Could not split header and body';

				return false;
			}
		}

		$header = substr($input, 0, $pos);
		$body = substr($input, $pos + (2 * strlen($this->_crlf)));

		return array(
			$header,
			$body
		);
	}

	/**
	 * Parse headers given in $input and return
	 * as assoc array.
	 * 
	 * @param string Headers to parse
	 * @return array Contains parsed headers
	 * @access private
	 */
	function _parseHeaders($input) {
		if ($input !== '') {
			// Unfold the input
			$input = preg_replace('/' . $this->_crlf . "(\t| )/", ' ', $input);

			$headers = explode($this->_crlf, trim($input));

			foreach ($headers as $value) {
				$hdr_name = substr($value, 0, $pos = strpos($value, ':'));

				$hdr_value = substr($value, $pos + 1);

				if ($hdr_value[0] == ' ')
					$hdr_value = substr($hdr_value, 1);

				$return[] = array(
					'name' => $hdr_name,
					'value' => $this->_decode_headers ? $this->_decodeHeader($hdr_value) : $hdr_value
				);
			}
		} else {
			$return = array();
		}

		return $return;
	}

	/**
	 * Function to parse a header value,
	 * extract first part, and any secondary
	 * parts (after ;) This function is not as
	 * robust as it could be. Eg. header comments
	 * in the wrong place will probably break it.
	 * 
	 * @param string Header value to parse
	 * @return array Contains parsed result
	 * @access private
	 */
	function _parseHeaderValue($input) {
		if (($pos = strpos($input, ';')) !== false) {
			$return['value'] = trim(substr($input, 0, $pos));

			$input = trim(substr($input, $pos + 1));

			if (strlen($input) > 0) {
				preg_match_all('/(([[:alnum:]]+)="?([^"]*)"?\s?;?)+/i', $input, $matches);

				for ($i = 0; $i < count($matches[2]); $i++) {
					$return['other'][strtolower($matches[2][$i])] = $matches[3][$i];
				}
			}
		} else {
			$return['value'] = trim($input);
		}

		return $return;
	}

	/**
	 * This function splits the input based
	 * on the given boundary
	 * 
	 * @param string Input to parse
	 * @return array Contains array of resulting mime parts
	 * @access private
	 */
	function _boundarySplit($input, $boundary) {
		$tmp = explode('--' . $boundary, $input);

		for ($i = 1; $i < count($tmp) - 1; $i++) {
			$parts[] = $tmp[$i];
		}

		return $parts;
	}

	/**
	 * Given a header, this function will decode it
	 * according to RFC2047. Probably not *exactly*
	 * conformant, but it does pass all the given
	 * examples (in RFC2047).
	 *
	 * @param string Input header value to decode
	 * @return string Decoded header value
	 * @access private
	 */
	function _decodeHeader($input) {
		// Remove white space between encoded-words
		$input = preg_replace('/(=\?[^?]+\?(Q|B)\?[^?]*\?=)( |' . "\t|" . $this->_crlf . ')+=\?/', '\1=?', $input);

		// For each encoded-word...
		while (preg_match('/(=\?([^?]+)\?(Q|B)\?([^?]*)\?=)/', $input, $matches)) {
			$encoded = $matches[1];

			$charset = $matches[2];
			$encoding = $matches[3];
			$text = $matches[4];

			switch ($encoding) {
			case 'B':
				$text = base64_decode($text);

				break;

			case 'Q':
				$text = str_replace('_', ' ', $text);

				preg_match_all('/=([A-F0-9]{2})/', $text, $matches);

				foreach ($matches[1] as $value)
					$text = str_replace('=' . $value, chr(hexdec($value)), $text);

				break;
			}
			if ($charset == "iso-8859-1")	// patch specifique tiki
				$text = utf8_encode($text);

			$input = str_replace($encoded, $text, $input);
		}

		return $input;
	}

	/**
	 * Given a body string and an encoding type, 
	 * this function will decode and return it.
	 *
	 * @param  string Input body to decode
	 * @param  string Encoding type to use.
	 * @return string Decoded body
	 * @access private
	 */
	function _decodeBody($input, $encoding = '7bit') {
		switch ($encoding) {
		case '7bit':
			return $input;

			break;

		case 'quoted-printable':
			return $this->_quotedPrintableDecode($input);

			break;

		case 'base64':
			return base64_decode($input);

			break;

		default:
			return $input;
		}
	}

	/**
	 * Given a quoted-printable string, this
	 * function will decode and return it.
	 *
	 * @param  string Input body to decode
	 * @return string Decoded body
	 * @access private
	 */
	function _quotedPrintableDecode($input) {
		// Remove soft line breaks
		$input = preg_replace("/=\r?\n/", '', $input);

		// Replace encoded characters
		if (preg_match_all('/=[A-Z0-9]{2}/', $input, $matches)) {
			$matches = array_unique($matches[0]);

			foreach ($matches as $value) {
				$input = str_replace($value, chr(hexdec(substr($value, 1))), $input);
			}
		}

		return $input;
	}

	/**
 * Checks the input for uuencoded files and returns
 * an array of them. Can be called statically, eg:
 *
 * $files =& Mail_mimeDecode::uudecode($some_text);
 *
 * It will check for the begin 666 ... end syntax
 * however and won't just blindly decode whatever you
 * pass it.
 *
 * @param  string Input body to look for attahcments in
 * @return array  Decoded bodies, filenames and permissions
 * @access public
 * @author Unknown
 */
	function &uudecode($input) {
		// Find all uuencoded sections
		preg_match_all("/begin ([0-7]{3}) (.+)\r?\n(.+)\r?\nend/Us", $input, $matches);

		for ($j = 0; $j < count($matches[3]); $j++) {
			$str = $matches[3][$j];

			$filename = $matches[2][$j];
			$fileperm = $matches[1][$j];

			$file = '';
			$str = preg_split("/\r?\n/", trim($str));
			$strlen = count($str);

			for ($i = 0; $i < $strlen; $i++) {
				$pos = 1;

				$d = 0;
				$len = (int)(((ord(substr($str[$i], 0, 1)) - 32) - ' ') & 077);

				while (($d + 3 <= $len) AND ($pos + 4 <= strlen($str[$i]))) {
					$c0 = (ord(substr($str[$i], $pos, 1)) ^ 0x20);

					$c1 = (ord(substr($str[$i], $pos + 1, 1)) ^ 0x20);
					$c2 = (ord(substr($str[$i], $pos + 2, 1)) ^ 0x20);
					$c3 = (ord(substr($str[$i], $pos + 3, 1)) ^ 0x20);
					$file .= chr(((($c0 - ' ') & 077) << 2) | ((($c1 - ' ') & 077) >> 4));

					$file .= chr(((($c1 - ' ') & 077) << 4) | ((($c2 - ' ') & 077) >> 2));

					$file .= chr(((($c2 - ' ') & 077) << 6) | (($c3 - ' ') & 077));

					$pos += 4;
					$d += 3;
				}

				if (($d + 2 <= $len) && ($pos + 3 <= strlen($str[$i]))) {
					$c0 = (ord(substr($str[$i], $pos, 1)) ^ 0x20);

					$c1 = (ord(substr($str[$i], $pos + 1, 1)) ^ 0x20);
					$c2 = (ord(substr($str[$i], $pos + 2, 1)) ^ 0x20);
					$file .= chr(((($c0 - ' ') & 077) << 2) | ((($c1 - ' ') & 077) >> 4));

					$file .= chr(((($c1 - ' ') & 077) << 4) | ((($c2 - ' ') & 077) >> 2));

					$pos += 3;
					$d += 2;
				}

				if (($d + 1 <= $len) && ($pos + 2 <= strlen($str[$i]))) {
					$c0 = (ord(substr($str[$i], $pos, 1)) ^ 0x20);

					$c1 = (ord(substr($str[$i], $pos + 1, 1)) ^ 0x20);
					$file .= chr(((($c0 - ' ') & 077) << 2) | ((($c1 - ' ') & 077) >> 4));
				}
			}

			$files[] = array(
				'filename' => $filename,
				'fileperm' => $fileperm,
				'filedata' => $file
			);
		}

		return $files;
	}

	/**
 * Returns a xml copy of the output of
 * Mail_mimeDecode::decode. Pass the output in as the
 * argument. This function can be called statically. Eg:
 *
 * $output = $obj->decode();
 * $xml    = Mail_mimeDecode::getXML($output);
 *
 * The DTD used for this should have been in the package. Or
 * alternatively you can get it from cvs, or here:
 * http://www.phpguru.org/xmail/xmail.dtd.
 *
 * @param  object Input to convert to xml. This should be the
 *                output of the Mail_mimeDecode::decode function
 * @return string XML version of input
 * @access public
 */
	function getXML($input) {
		$crlf = "\r\n";

		$output = '<?xml version=\'1.0\'?>' . $crlf . '<!DOCTYPE email SYSTEM "http://www.phpguru.org/xmail/xmail.dtd">' . $crlf . '<email>' . $crlf . Mail_mimeDecode::_getXML($input). '</email>';

		return $output;
	}

	/**
 * Function that does the actual conversion to xml. Does a single
 * mimepart at a time.
 *
 * @param  object  Input to convert to xml. This is a mimepart object.
 *                 It may or may not contain subparts.
 * @param  integer Number of tabs to indent
 * @return string  XML version of input
 * @access private
 */
	function _getXML($input, $indent = 1) {
		$htab = "\t";

		$crlf = "\r\n";
		$output = '';
		$headers = &$input->headers;

		foreach ($headers as $hdr_name => $hdr_value) {

			// Multiple headers with this name
			if (is_array($headers[$hdr_name])) {
				for ($i = 0; $i < count($hdr_value); $i++) {
					$output .= Mail_mimeDecode::_getXML_helper($hdr_name, $hdr_value[$i], $indent);
				}

			// Only one header of this sort
			} else {
				$output .= Mail_mimeDecode::_getXML_helper($hdr_name, $hdr_value, $indent);
			}
		}

		if (!empty($input->parts)) {
			for ($i = 0; $i < count($input->parts); $i++) {
				$output .= $crlf . str_repeat($htab, $indent). '<mimepart>' . $crlf . Mail_mimeDecode::_getXML($input->parts[$i], $indent + 1). str_repeat($htab, $indent). '</mimepart>' . $crlf;
			}
		} else {
			$output .= $crlf . str_repeat($htab, $indent). '<body><![CDATA[' . $input->body . ']]></body>' . $crlf;
		}

		return $output;
	}

	/**
 * Helper function to _getXML(). Returns xml of a header.
 *
 * @param  string  Name of header
 * @param  string  Value of header
 * @param  integer Number of tabs to indent
 * @return string  XML version of input
 * @access private
 */
	function _getXML_helper($hdr_name, $hdr_value, $indent) {
		$htab = "\t";

		$crlf = "\r\n";
		$return = '';

		$new_hdr_value = ($hdr_name != 'received') ? Mail_mimeDecode::_parseHeaderValue($hdr_value) : array('value' => $hdr_value);
		$new_hdr_name = str_replace(' ', '-', ucwords(str_replace('-', ' ', $hdr_name)));

		// Sort out any parameters
		if (!empty($new_hdr_value['other'])) {
			foreach ($new_hdr_value['other'] as $paramname => $paramvalue) {
				$params[] = str_repeat($htab, $indent). $htab . '<parameter>' . $crlf . str_repeat($htab, $indent). $htab . $htab . '<paramname>' . htmlspecialchars($paramname). '</paramname>' . $crlf . str_repeat($htab, $indent). $htab . $htab . '<paramvalue>' . htmlspecialchars($paramvalue). '</paramvalue>' . $crlf . str_repeat($htab, $indent). $htab . '</parameter>' . $crlf;
			}

			$params = implode('', $params);
		} else {
			$params = '';
		}

		$return = str_repeat($htab, $indent). '<header>' . $crlf . str_repeat($htab, $indent). $htab . '<headername>' . htmlspecialchars($new_hdr_name). '</headername>' . $crlf . str_repeat($htab, $indent). $htab . '<headervalue>' . htmlspecialchars($new_hdr_value['value']). '</headervalue>' . $crlf . $params . str_repeat($htab, $indent). '</header>' . $crlf;

		return $return;
	}
} // End of class

?>
