<?php
/**
 * This redirects to the site's root to prevent directory browsing.
 *  
 * @ignore 
 * @package TikiWiki 
 * @copyright (c) Copyright 2002-2013 by authors of the Tiki Wiki CMS Groupware Project
 * @licence Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
 */
// $Id$

if (! file_exists('vendor/autoload.php')) {
	$title="Tiki Installer missing third party software files";
	$content="<p>Your Tiki is not completely installed because Composer has not been run to fetch package dependencies.</p>";
	$content.="<p>You need to run <b>sh setup.sh</b> from the command line.</p>";
	$content.="<p>See <a href='http://dev.tiki.org/Composer' target='_blank' >http://dev.tiki.org/Composer</a> for details.</p>";
	createPage($title, $content);
	exit;
}

require_once ('tiki-setup.php');
if ( ! headers_sent($header_file, $header_line) ) {
	// rfc2616 wants this to have an absolute URI
	header('Location: '.$base_url.$prefs['tikiIndex']);
} else {
	echo "Header already sent in ".$header_file." at line ".$header_line;
	exit();
}

/**
 * creates the HTML page to be displayed.
 * 
 * Tiki may not have been installed when we reach here, so we can't use our templating system yet. 
 * This needs to be done before tiki-setup.php is called because tiki-setup.php produces a message formatted for command-line only
 * 
 * @param string $title   page Title
 * @param mixed  $content page Content
 */
function createPage($title, $content)
{
	echo <<<END
<!DOCTYPE html 
	PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link type="text/css" rel="stylesheet" href="styles/fivealive.css" />
		<title>$title</title>
	</head>
	<body class="tiki_wiki fixed_width">
		<div id="fixedwidth" class="fixedwidth">
			<div class="header_outer">
				<div class="header_container">
					<div class="clearfix fixedwidth header_fixedwidth">
						<header id="header" class="header">
							<div class="content clearfix modules" id="top_modules" style="display: table; width: 990px;">
								<div class="sitelogo">
									<img alt="Site Logo" src="img/tiki/Tiki_WCG.png" style="margin-bottom: 10px;" />
								</div>
							</div>
						</header>
					</div>	
				</div>
			</div>
			<div class="middle_outer">
				<div name="middle" class="fixedwidth">
					<div id="tiki-top" class="clearfix">
						<h1 style="font-size: 30px; line-height: 30px; color: #fff; text-shadow: 3px 2px 0 #781437; margin: 8px 0 0 10px; padding: 0;">
							$title
						</h1>
					</div>
				</div>
				<div id="middle" style="width: 960px; text-align: left; ">
					$content
				</div>
			</div>
		</div>
		<footer id="footer" class="footer" style="margin-top: 50px;">
			<div class="footer_liner">
				<div class="footerbgtrap fixedwidth" style="padding: 10px 0;">
					<a href="http://tiki.org" target="_blank" title="Powered by Tiki Wiki CMS Groupware"><img src="img/tiki/tikibutton.png" alt="Powered by Tiki Wiki CMS Groupware" /></a>
				</div>
			</div>
		</footer>
	</body>
</html>
END;
	die;
}
