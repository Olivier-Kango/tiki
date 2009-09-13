<?php

//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER["SCRIPT_NAME"],basename(__FILE__)) !== false) {
  header("location: index.php");
  exit;
}

/*
 * smarty_block_textarea : add a textarea to a template.
 *
 * special params:
 *    _toolbars: if set to 'y', display toolbars above the textarea
 *    _enlarge: if set to 'y', display the enlarge buttons above the textarea
 *
 * usage: {textarea id='my_area' name='my_area'}{tr}My Text{/tr}{/textarea}
 *
 */

function smarty_block_textarea($params, $content, &$smarty, $repeat) {
	global $prefs, $headerlib, $smarty;
	if ( $repeat ) return;

	// some defaults
	$params['_toolbars'] = isset($params['_toolbars']) ? $params['_toolbars'] : 'y';
	if ( $prefs['javascript_enabled'] != 'y') $params['_toolbars'] = 'n';

	if (!isset($params['_wysiwyg'])) {	// should not be set usually(?)
		include_once 'lib/setup/editmode.php';
		$params['_wysiwyg'] = $_SESSION['wysiwyg'];
	}
	
	$params['rows'] = isset($params['rows']) ? $params['rows'] : 20;
	$params['cols'] = isset($params['cols']) ? $params['cols'] : 80;
	$params['name'] = isset($params['name']) ? $params['name'] : 'edit';
	$params['id'] = isset($params['id']) ? $params['id'] : 'editwiki';
	
	if ( isset($params['_zoom']) && $params['_zoom'] == 'n' ) {
		$feature_template_zoom_orig = $prefs['feature_template_zoom'];
		$prefs['feature_template_zoom'] = 'n';
	}
	if ( ! isset($params['_section']) ) {
		global $section;
		$params['_section'] = $section ? $section: 'wiki page';
	}
	if ( ! isset($params['style']) ) $params['style'] = 'width:99%';
	$html = '';
	$html .= '<input type="hidden" name="mode_wysiwyg" value="" /><input type="hidden" name="mode_normal" value="" />';
		

	if ( $params['_wysiwyg'] == 'y' ) {
//		{editform Meat=$pagedata InstanceName='edit' ToolbarSet="Tiki"}
		global $url_path;
		include_once 'lib/tikifck.php';
		if (!isset($params['name']))       $params['name'] = 'fckedit';
		$fcked = new TikiFCK($params['name']);
		
		if (isset($content))			$fcked->Meat = $content;
		if (isset($params['Width']))	$fcked->Width = $params['Width'];
		if (isset($params['Height']))	$fcked->Height = $params['Height'];
		if ($prefs['feature_ajax'] == 'y' && $prefs['feature_ajax_autosave'] == 'y') {
			$fcked->Config['autoSaveSelf'] = htmlentities($_SERVER['REQUEST_URI']);
		}
		if (isset($params['ToolbarSet'])) {
			$fcked->ToolbarSet = $params['ToolbarSet'];
		} else {
			$fcked->ToolbarSet = 'Tiki';
		}
		if ($prefs['feature_detect_language'] == 'y') {
			$fcked->Config['AutoDetectLanguage'] = true;
		} else {
			$fcked->Config['AutoDetectLanguage'] = false;
		}
		$fcked->Config['DefaultLanguage'] = $prefs['language'];
		$fcked->Config['CustomConfigurationsPath'] = $url_path.'setup_fckeditor.php'.(isset($params['_section']) ? '?section='.urlencode($params['_section']) : '');
		$html .= $fcked->CreateHtml();
		
		$html .= '<input type="hidden" name="wysiwyg" value="y" />';
		
		// fix for Safari which refuses to make the edit box 100% height
		$h = str_replace('px','', $fcked->Height);
		if ($h) { $headerlib->add_js('
var fckEditorInstances = new Array();
function FCKeditor_OnComplete( editorInstance ) {
	fckEditorInstances[fckEditorInstances.length] = editorInstance;
	if (jQuery.browser.safari) {
		var fckbod = $jq("#'.$params['name'].'___Frame").contents().find("body");
		var h = '.$h.' - fckbod.find("#xToolbar").height() - 5;
		fckbod.find("#xEditingArea").height(h);
	}
};'); }
	} else {
		
		// setup for wiki editor
		
		$textarea_attributes = '';
		foreach ( $params as $k => $v ) {
			if ( $k == 'id' || $k == 'name' || $k == 'class' ) {
				$smarty->assign('textarea_'.$k, $v);
			} elseif ( $k[0] != '_' ) {
				$textarea_attributes .= ' '.$k.'="'.$v.'"';
			}
		}

		if ( $textarea_attributes != '' ) {
			$smarty->assign('textarea_attributes', $textarea_attributes);
		}
		$smarty->assign_by_ref('pagedata', $content);
		
		if (!$textarea_id) { $textarea_id = $params['id']; }

		$html .= $smarty->fetch('wiki_edit.tpl');

		$html .= "\n".'<input type="hidden" name="rows" value="'.$params['rows'].'"/>'
			."\n".'<input type="hidden" name="cols" value="'.$params['cols'].'"/>'
			."\n".'<input type="hidden" name="wysiwyg" value="n" />';


		if ( isset($params['_zoom']) && $params['_zoom'] == 'n' ) {
			$prefs['feature_template_zoom'] = $feature_template_zoom_orig;
		}
		
		if ($prefs['feature_ajax'] == 'y' && $prefs['feature_ajax_autosave'] == 'y') {
			$headerlib->add_jq_onready("register_id('$textarea_id');auto_save();");
		}
		
	}	// wiki or wysiwyg


// Display edit time out

	$js = "
// edit timeout warnings
function editTimerTick() {
	editTimeElapsedSoFar++;
	
	var seconds = editTimeoutSeconds - editTimeElapsedSoFar;
	
	if (editTimerWarnings == 0 && seconds <= 60) {
		alert('".tra('Your edit session will expire in').' 1 '.tra('minute').'.'.
				tra('You must PREVIEW or SAVE your work now, to avoid losing your edits.')."');
		editTimerWarnings++;
	} else if (seconds <= 0) {
		clearInterval(editTimeoutIntervalId);
	}
	
	window.status = '".tra('Your edit session will expire in:')."' + Math.floor(seconds / 60) + ': ' + ((seconds % 60 < 10) ? '0' : '') + (seconds % 60);
	if (seconds % 60 == 0 && \$jq('#edittimeout')) {
		\$jq('#edittimeout').text(Math.floor(seconds / 60));
	}
}

function confirmExit() {
	if (typeof fckEditorInstances != 'undefined' && fckEditorInstances.length > 0) {
		for(ed in fckEditorInstances) {
			if (fckEditorInstances[ed].IsDirty()) {
				editorDirty = true;
				break;
			}
		}
	}
	if (needToConfirm && editorDirty) {
		return '".tra('You are about to leave this page. If you have made any changes without Saving, your changes will be lost.  Are you sure you want to exit this page?')."';
	}
}

window.onbeforeunload = confirmExit;
\$jq('document').ready( function() {
	editTimeoutIntervalId = setInterval(editTimerTick, 1000);
	\$jq('fieldset.tabcontent input, fieldset.tabcontent textarea, fieldset.tabcontent select').change( function () { if (!editorDirty) { editorDirty = true; } });
	
});

var needToConfirm = true;
var editorDirty = ".(isset($_REQUEST["preview"]) ? 'true' : 'false').";
var editTimeoutSeconds = ".ini_get('session.gc_maxlifetime').";
var editTimeElapsedSoFar = 0;
var editTimeoutIntervalId;
var editTimerWarnings = 0;
// end edit timeout warnings
";
	$headerlib->add_js($js);
	$headerlib->add_js('function switchEditor(mode, form) {
	needToConfirm=false;
	var w;
	if (mode=="wysiwyg") {
		$jq(form).find("input[name=mode_wysiwyg]").val("y");
		$jq(form).find("input[name=wysiwyg]").val("y");
	} else {
		$jq(form).find("input[name=mode_normal]").val("y");
		$jq(form).find("input[name=wysiwyg]").val("n");
	}
	form.submit();
}');

	return $html;
}
