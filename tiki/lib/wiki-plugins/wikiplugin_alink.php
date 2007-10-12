<?php
/*
 * Tiki-Wiki ALINK plugin. Go to http://www.TikiMODS.com for more Tikiwiki Plugins.
 *
 * DESCRIPTION: Puts a link to an anchor in a wiki page. Use in conjunction with the ANAME plugin, which sets the location and name of the anchor.
 * 
 * INSTALLATION: Just put this file into your Tikiwiki site's lib/wiki-plugins folder.
 * 
 * USAGE SYNTAX:
 * 
 * 	{ALINK(
 *		aname=>anchorname	the name of the anchor you created using the ANAME plugin!
 * 	)}
 *	 	yourlinktext		the text of the link
 *	 {ALINK}
 *
 * EXAMPLE:  {ALINK(aname=myanchor)}click here{ALINK}
 * 
  */


function wikiplugin_alink_help() {
        return tra("Puts a link to an anchor in a wiki page. Use in conjunction with the ANAME plugin, which sets the location and name of the anchor").":<br />~np~{ALINK(aname=>anchorname,pagename=>Wiki Page Name)}".tra("linktext")."{ALINK}~/np~<br />pagename is optional; if it is not present, links into the current file.";
}

function wikiplugin_alink($data, $params)
{
        global $multilinguallib, $tikilib, $prefs;

	if( ! isset( $multilinguallib ) || !is_object($multilinguallib) ) {
		include_once('lib/multilingual/multilinguallib.php');// must be done even in feature_multilingual not set
	}
        extract ($params, EXTR_SKIP);

	if (!isset($aname)) {
		return ("<b>missing parameter for aname</b><br />");
	}

	if( isset($pagename) && $pagename ) {
	    // Stolen, with some modifications, from tikilib.php line 4717-4723
	    if( $desc = $tikilib->page_exists_desc($pagename) )
	    {
		// to choose the best page language
		$bestLang = ($prefs['feature_multilingual'] == 'y' && $prefs['feature_best_language'] == 'y')? "&amp;bl" : ""; 
		// $bestLang = $prefs['feature_best_language'] == 'y' ? "&amp;bl" : ""; 

		return "<a title=\"$desc\" href='tiki-index.php?page=" . urlencode($pagename) . 
			$bestLang .  "#" . $aname .  "' class='wiki'>$data</a>";
	    } else {
		return $data . '<a href="tiki-editpage.php?page=' . urlencode($pagename) . 
			'" title="' . tra("Create page:") . ' ' . urlencode($pagename) . 
			'"  class="wiki wikinew">?</a>';
	    }

	} else {
	    return "<A HREF=\"#$aname\">$data</A>";
	}
}

?>
