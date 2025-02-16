<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Class Services_Edit_Controller
 *
 * Controller for various editing based services, wiki/html conversion, preview, inline editing etc
 *
 */
class Services_Edit_Controller
{
    public function setUp()
    {
        Services_Exception_Disabled::check('feature_wiki');
    }

    /**
     * Returns the section for use with certain features like banning
     * @return string
     */
    public function getSection()
    {
        return 'wiki page';
    }

    public function action_towiki($input)
    {
        $res = TikiLib::lib('edit')->parseToWiki($input->data->none());

        return [
            'data' => $res,
        ];
    }

    public function action_tohtml($input)
    {
        $wysiwyg = $input->allowhtml->int() ? true : false;
        $res = TikiLib::lib('edit')->parseToWysiwyg($input->data->none(), false, $wysiwyg, ['wysiwyg' => $wysiwyg]);

        return [
            'data' => $res,
        ];
    }

    public function action_inlinesave($input)
    {
        global $user;

        Services_Exception_Disabled::check('wysiwyg_inline_editing');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $pageName = $input->page->text();
            $info = TikiLib::lib('tiki')->get_page_info($pageName);
            $data = $input->data->none();

            // Check if HTML format is allowed
            if ($info['is_html']) {
                // Save as HTML
                $edit_data = TikiLib::lib('edit')->partialParseWysiwygToWiki($data);
                $is_html = '1';
            } else {
                // Convert HTML to wiki and save as wiki
                $edit_data = TikiLib::lib('edit')->parseToWiki($data);
                $is_html = null;
            }

            $edit_comment = tra('Inline editor update');
            $res = TikiLib::lib('tiki')->update_page($pageName, $edit_data, $edit_comment, $user, $_SERVER['REMOTE_ADDR']);

            return [
                'data' => $res,
            ];
        }
    }

    public function action_preview($input)
    {

        Services_Exception_Disabled::check('feature_warn_on_edit');

        global $user, $prefs, $page;
        $tikilib = TikiLib::lib('tiki');

        $autoSaveIdParts = explode(':', $input->autoSaveId->text());    // user, section, object id
        foreach ($autoSaveIdParts as & $part) {
            $part = urldecode($part);
        }

        $page = $autoSaveIdParts[2] ?? '';    // plugins use global $page for approval

        if (! Perms::get('wiki page', $page)->edit || $user != TikiLib::lib('service')->internal('semaphore', 'get_user', ['object_id' => $page, 'check' => 1])) {
            return '';
        }

        $info = $tikilib->get_page_info($page, false);
        if (empty($info)) {
            $info = [       // new page
                'data' => '',
            ];
        }

        $info['is_html'] = $input->allowHtml->int();

        if (! isset($info['wysiwyg']) && isset($_SESSION['wysiwyg'])) {
            $info['wysiwyg'] = $_SESSION['wysiwyg'];
        }
        $options = [
            'is_html' => $info['is_html'],
            'preview_mode' => true,
            'process_wiki_paragraphs' => ($prefs['wysiwyg_htmltowiki'] === 'y' || $info['wysiwyg'] == 'n'),
            'page' => $page,
            'is_markdown' => $input->is_markdown->int()
        ];

        if (count($autoSaveIdParts) === 3 && ! empty($user) && $user === $autoSaveIdParts[0] && $autoSaveIdParts[1] === 'wiki_page') {
            $editlib = TikiLib::lib('edit');
            $smarty = TikiLib::lib('smarty');
            $wikilib = TikiLib::lib('wiki');

            $smarty->assign('inPage', $input->inPage->int() ? true : false);

            $parserlib = TikiLib::lib('parser');
            if ($input->inPage->int()) {
                $diffstyle = $input->diff_style->text();
                if (! $diffstyle) { // use previously set diff_style
                    $diffstyle = getCookie('preview_diff_style', 'preview', '');
                }
                $data = $editlib->partialParseWysiwygToWiki(
                    TikiLib::lib('autosave')->get_autosave($input->editor_id->text(), $input->autoSaveId->text())
                );
                $data = $tikilib->convertAbsoluteLinksToRelative($data);
                if ($input->is_markdown->int()) {
                    $data = "{syntax type=markdown}\r\n$data";
                }
                TikiLib::lib('smarty')->assign('diff_style', $diffstyle);
                if ($diffstyle) {
                    if (! empty($info['created'])) {
                        $info = $tikilib->get_page_info($page); // get page with data this time
                    }
                    if ($input->hdr->int()) {       // TODO refactor with code in editpage
                        if ($input->hdr->int() === 0) {
                            list($real_start, $real_len) = $tikilib->get_wiki_section($info['data'], 1);
                            $real_len = $real_start;
                            $real_start = 0;
                        } else {
                            list($real_start, $real_len) = $tikilib->get_wiki_section($info['data'], $input->hdr->int());
                        }
                        $info['data'] = substr($info['data'], $real_start, $real_len);
                    }
                    require_once('lib/diff/difflib.php');
                    if ($info['is_html'] == 1) {
                        $diffold = $tikilib->htmldecode($info['data']);
                    } else {
                        $diffold = $info['data'];
                    }
                    if ($info['is_html']) {
                        $diffnew = $tikilib->htmldecode($data);
                    } else {
                        $diffnew = $data;
                    }
                    if ($diffstyle === 'htmldiff') {
                        $diffnew = $parserlib->parse_data($diffnew, $options);
                        $diffold = $parserlib->parse_data($diffold, $options);
                    }
                    $data = diff2($diffold, $diffnew, $diffstyle);
                    $smarty->assign_by_ref('diffdata', $data);

                    $smarty->assign('translation_mode', 'y');   // disables the headings etc
                    $smarty->assign('show_version_info', 'n');  // disables the headings etc
                    $data = $smarty->fetch('pagehistory.tpl');
                } else {
                    $data = $parserlib->parse_data($data, $options);
                }
                $parsed = $data;
            } else {                    // popup window
                TikiLib::lib('header')->add_js(
                    '
function get_new_preview() {
    $("body").css("opacity", 0.6);
    location.reload(true);
}
$(window).on("load", function(){
    if (typeof opener != "undefined") {
        opener.ajaxPreviewWindow = this;
    }
}).on("pagehide", function(){
    if (typeof opener.ajaxPreviewWindow != "undefined") {
        opener.ajaxPreviewWindow = null;
    }
});
'
                );
                $smarty->assign('headtitle', tra('Preview'));
                $data = '<div class="container"><div class="row row-middle"><div class="col-sm-12"><div class="wikitext">';
                if (TikiLib::lib('autosave')->has_autosave($input->editor_id->text(), $input->autoSaveId->text())) {
                    $data .= $parserlib->parse_data(
                        $editlib->partialParseWysiwygToWiki(
                            TikiLib::lib('autosave')->get_autosave($input->editor_id->text(), $input->autoSaveId->text())
                        ),
                        $options
                    );
                } else {
                    if ($autoSaveIdParts[1] == 'wiki_page') {
                        $canBeRefreshed = false;
                        $data .= $wikilib->get_parse($autoSaveIdParts[2], $canBeRefreshed);
                    }
                }
                $data .= '</div></div></div></div>';
                $smarty->assign_by_ref('mid_data', $data);
                $smarty->assign('mid', '');
                $parsed = $smarty->fetch("tiki_full.tpl");

                $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';   // to fool Services_Broker into putputting full page
            }

            if ($prefs['feature_wiki_footnotes'] === 'y') {
                $footnote = $input->footnote->text();
                if ($footnote) {
                    $footnote = $parserlib->parse_data($footnote);
                } else {
                    $footnote = $wikilib->get_footnote($user, $page);
                }
            } else {
                $footnote = '';
            }

            if ($input->show_preview->int()) {
                try {
                    require_once 'lib/pdflib.php';
                    $generator = new PdfGenerator($prefs['print_pdf_from_url']);
                    if (! empty($generator->error)) {
                        return ['error' => $generator->error];
                    }
                    $pdfToken = md5($page . time() . uniqid('', true));
                    $content = $parsed . $footnote;
                    $pdf = $generator->getPdf('tiki-print.php', ['page' => $page, 'pdf_token' => $pdfToken], $content);
                    return ['pdf' => base64_encode($pdf)];
                } catch (Exception $e) {
                    return ['error' => tr($e->getMessage())];
                }
            }

            return ['parsed' => $parsed, 'parsed_footnote' => $footnote];
        }
    }

    public function action_help($input)
    {
        global $prefs;

        $smarty = TikiLib::lib('smarty');

        $help_sections = [];

        if ($input->wiki->int()) {
            $help_sections[] = [
                'id' => 'wiki-help',
                'title' => tr('Wiki Syntax Help'),
                'content' => $smarty->fetch('tiki-edit_help.tpl'),
            ];
        }

        if ($input->markdown->int()) {
            $help_sections[] = [
                'id' => 'wiki-help',
                'title' => tr('Mardown Syntax Help'),
                'content' => $smarty->fetch('tiki-edit_help_markdown.tpl'),
            ];
        }

        if ($input->markdown_wysiwyg->int()) {
            $help_sections[] = [
                'id' => 'wiki-help',
                'title' => tr('Mardown Syntax Help'),
                'content' => $smarty->fetch('tiki-edit_help_markdown_wysiwyg.tpl'),
            ];
        }

        if ($input->wysiwyg->int()) {
            $help_sections[] = [
                'id' => 'wysiwyg-help',
                'title' => tr('WYSIWYG Help'),
                'content' => $smarty->fetch('tiki-edit_help_wysiwyg.tpl'),
            ];
        }

        if ($input->plugins->int()) {
            $areaId = $input->areaId->word();
            $wikilib = TikiLib::lib('wiki');
            $plugins = $wikilib->list_plugins(true, $areaId);

            $smarty->assign('plugins', $plugins);
            $help_sections[] = [
                'id' => 'plugin-help',
                'title' => tr('Plugins'),
                'content' => $smarty->fetch('tiki-edit_help_plugins.tpl'),
            ];

            if ($prefs['wikiplugin_list_gui'] === 'y') {
                TikiLib::lib('header')
                    ->add_jsfile('lib/jquery_tiki/pluginedit_list.js')
                    ->add_jsfile('public/generated/js/vendor_dist/nestedsortable/jquery.mjs.nestedSortable.js');
            }
        }

        if ($input->sheet->int()) {
            $help_sections[] = [
                'id' => 'sheet-help',
                'title' => tr('Spreadsheet Help'),
                'content' => $smarty->fetch('tiki-edit_help_sheet.tpl'),
            ];
        }

        $title = tr('Edit Help');
        if (count($help_sections) === 1) {
            $title = $help_sections[0]['title'];
        }

        return [
            'title' => $title,
            'help_sections' => $help_sections,
        ];
    }

    public function action_editor_settings($input)
    {
        return [
            'title' => tr('Editor Settings'),
            'domId' => $input->domId->word(),
        ];
    }

    public function action_inline_dialog($input)
    {
        $smarty = TikiLib::lib('smarty');

        $display = [];
        foreach ($input->fields as $field) {
            $html = smarty_function_service_inline($field->fetch->text(), $smarty->getEmptyInternalTemplate());
            $display[] = [
                'label' => $field->label->text(),
                'field' => new Tiki_Render_Editable($html, [
                    'layout' => 'dialog',
                    'object_store_url' => $field->store->text(),
                ]),
            ];
        }

        return [
            'title' => tr('Edit'),
            'fields' => $display,
        ];
    }

    /**
     * Convert syntax between markdown and tiki
     *
     * @param JitFilter $input
     *
     * @return string the converted markup
     */
    public function action_convert_syntax(JitFilter $input): string
    {
        $converted = $input->data->wikicontent();

        try {
            $converted = TikiLib::lib('edit')->convertWikiSyntax(
                $converted,
                $input->syntax->word(),
                $input->page->word()
            );
        } catch (Exception $e) {
            Feedback::error($e->getMessage());
        }

        $converted = '{syntax type="' . $input->syntax->word() . '" editor="' . $input->editor->word() . '"} ' . $converted;

        return $converted;
    }

    /**
     * Update state of checkbox in markdown wiki page
     *
     * @param JitFilter $input
     *
     * @return int new state of checkbox
     */
    public function actionUpdateChecklistItem(JitFilter $input): array
    {
        global $user;

        $allowedWikiParsed = [
            'articles' => ['heading', 'body'],
            'calendar event' => ['description'],
            'comments' => ['data', 'title'],
            'post' => ['excerpt', 'data'],
            'surveys' => ['description'],
            'tracker' => ['description'],
            'trackerfield' => ['description'],
            'trackeritem' => ['value'],
            'trackeritemattachments' => ['longdesc'],
            'wiki page' => ['data'],
        ];

        $tikilib = TikiLib::lib('tiki');
        $objectlib = TikiLib::lib('object');
        $editlib = TikiLib::lib('edit');

        $objectId = $input->objectId->text();
        $objectType = $input->objectType->text();
        $fieldName = $input->fieldName->text();
        $checkboxNum = $input->checkboxNum->int();

        if (! $objectType || ! $objectId || ! $checkboxNum || ! $fieldName || ! in_array($objectType, array_keys($allowedWikiParsed)) || ! in_array($fieldName, $allowedWikiParsed[$objectType])) {
            throw new Services_Exception(tr('Missing parameters'));
        }

        if (! $tikilib->user_has_perm_on_object($user, $objectId, $objectType, $objectlib->get_needed_perm($objectType, 'edit'))) {
            throw new Services_Exception_Denied(tr('You do not have permission to edit "%0"', tr($objectType)));
        }

        list($dbName, $dbKey) = $objectlib->deprecatedGetDBFor($objectType);

        if (is_array($dbKey)) {
            $params = array_combine($dbKey, $objectId);
        } else {
            $params = [$dbKey => $objectId];
        }

        $data = TikiDb::get()
            ->table($dbName)
            ->fetchColumn($fieldName, $params);

        if (empty($data)) {
            throw new Services_Exception(tr('Item not found'));
        }
        list($data, $state) = $editlib->toggleCheckbox($data[0], $checkboxNum);

        if ($objectType == 'trackeritem') {
            $objectId = $params;
        }

        try {
            $objectlib->setRawData(
                $objectType,
                $objectId,
                [$fieldName => $data]
            );
        } catch (Exception $e) {
            throw new Services_Exception(tr('Failed to update checkbox'));
        }

        return [
            'state' => $state
        ];
    }
}
