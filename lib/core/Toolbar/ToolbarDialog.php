<?php

namespace Tiki\Lib\core\Toolbar;

use TikiLib;

class ToolbarDialog extends ToolbarItem
{
    private array $list;
    private int $index;
    private string $name;

    public static function fromName(string $tagName): ?ToolbarItem
    {
        global $prefs;

        $tool_prefs = [];

        switch ($tagName) {
            case 'tikilink':
                $label = tra('Wiki Link');
                $iconname = 'link';
                $icon = tra('img/icons/page_link.png');
                $wysiwyg = '';  // cke link dialog now adapted for wiki links
                $list = [
                    tra("Wiki Link"),
                    '<label for="tbWLinkDesc">' . tra("Show this text") . '</label>',
                    '<input type="text" id="tbWLinkDesc" class="ui-widget-content ui-corner-all" style="width: 98%" />',
                    '<label for="tbWLinkPage">' . tra("Link to this page") . '</label>',
                    '<input type="text" id="tbWLinkPage" class="ui-widget-content ui-corner-all" style="width: 98%" />',
                    $prefs['wikiplugin_alink'] == 'y' ? '<label for="tbWLinkAnchor">' . tra(
                            "Anchor"
                        ) . ':</label>' : '',
                    $prefs['wikiplugin_alink'] == 'y' ? '<input type="text" id="tbWLinkAnchor" class="ui-widget-content ui-corner-all" style="width: 98%" />' : '',
                    $prefs['feature_semantic'] == 'y' ? '<label for="tbWLinkRel">' . tra(
                            "Semantic relation"
                        ) . ':</label>' : '',
                    $prefs['feature_semantic'] == 'y' ? '<input type="text" id="tbWLinkRel" class="ui-widget-content ui-corner-all" style="width: 98%" />' : '',
                    '{"open": function () { dialogInternalLinkOpen(area_id); },
                        "buttons": { "' . tra("Cancel") . '": function() { dialogSharedClose(area_id,this); },' .
                    '"' . tra("Insert") . '": function() { dialogInternalLinkInsert(area_id,this); }}}',
                ];

                break;
            case 'objectlink':
                $types = TikiLib::lib('unifiedsearch')->getSupportedTypes();
                $options = '';
                foreach ($types as $type => $title) {
                    $options .= '<option value="' . $type . '">' . $title . '</option>';
                }
                $label = tra('Object Link');
                $iconname = 'link-external-alt';
                $icon = tra('img/icons/page_link.png');
                $wysiwyg = '';

                $smarty = TikiLib::lib('smarty');
                $smarty->loadPlugin('smarty_function_object_selector');
                $object_selector = smarty_function_object_selector([
                                                                       '_id'        => 'tbOLinkObjectSelector',
                                                                       '_class'     => 'ui-widget-content ui-corner-all',
                                                                       //              '_format' => '{title}',
                                                                       '_filter'    => ['type' => ''],
                                                                       '_parent'    => 'tbOLinkObjectType',
                                                                       '_parentkey' => 'type',
                                                                   ],
                                                                   $smarty->getEmptyInternalTemplate());

                $list = [
                    tra('Object Link'),
                    '<label for="tbOLinkDesc">' . tra("Show this text") . '</label>',
                    '<input type="text" id="tbOLinkDesc" />',
                    '<label for="tbOLinkObjectType">' . tra("Types of object") . '</label>',
                    '<select id="tbOLinkObjectType" class="ui-widget-content ui-corner-all" style="width: 98%">' .
                    '<option value="*">' . tra('All') . '</option>' .
                    $options .
                    '</select>',
                    '<label for="tbOLinkObjectSelector">' . tra("Link to this object") . '</label>',
                    $object_selector,
                    //                      '<input type="text" id="tbOLinkObjectSelector" class="ui-widget-content ui-corner-all" style="width: 98%" />',
                    '{"open": function () { dialogObjectLinkOpen(area_id); },
                        "buttons": { "' . tra("Cancel") . '": function() { dialogSharedClose(area_id,this); },' .
                    '"' . tra("Insert") . '": function() { dialogObjectLinkInsert(area_id,this); }}}',
                ];

                break;
            case 'link':
                $wysiwyg = 'Link';
                $label = tra('External Link');
                $iconname = 'link-external';
                $icon = tra('img/icons/world_link.png');
                $list = [
                    tra('External Link'),
                    '<label for="tbLinkDesc">' . tra("Show this text") . '</label>',
                    '<input type="text" id="tbLinkDesc" class="ui-widget-content ui-corner-all" style="width: 98%" />',
                    '<label for="tbLinkURL">' . tra("link to this URL") . '</label>',
                    '<input type="text" id="tbLinkURL" class="ui-widget-content ui-corner-all" style="width: 98%" />',
                    '<label for="tbLinkRel">' . tra("Relation") . ':</label>',
                    '<input type="text" id="tbLinkRel" class="ui-widget-content ui-corner-all" style="width: 98%" />',
                    $prefs['cachepages'] == 'y' ? '<br /><label for="tbLinkNoCache" style="display:inline;">' . tra(
                            "No cache"
                        ) . ':</label>' : '',
                    $prefs['cachepages'] == 'y' ? '<input type="checkbox" id="tbLinkNoCache" class="ui-widget-content ui-corner-all" />' : '',
                    '{"width": 300, "open": function () { dialogExternalLinkOpen( area_id ) },
                        "buttons": { "' . tra("Cancel") . '": function() { dialogSharedClose(area_id,this); },' .
                    '"' . tra("Insert") . '": function() { dialogExternalLinkInsert(area_id,this) }}}',
                ];
                break;

            case 'table':
            case 'tikitable':
                $iconname = 'table';
                $icon = tra('img/icons/table.png');
                $wysiwyg = 'Table';
                $label = tra('Table Builder');
                $list = [
                    tra('Table Builder'),
                    '{"open": function () { dialogTableOpen(area_id,this); },
                        "width": 320, "buttons": { "' . tra(
                        "Cancel"
                    ) . '": function() { dialogSharedClose(area_id,this); },' .
                    '"' . tra("Insert") . '": function() { dialogTableInsert(area_id,this); }}}',
                ];
                break;

            case 'find':
                $icon = tra('img/icons/find.png');
                $iconname = 'search';
                $wysiwyg = 'Find';
                $label = tra('Find Text');
                $list = [
                    tra('Find Text'),
                    '<label>' . tra("Search") . ':</label>',
                    '<input type="text" id="tbFindSearch" class="ui-widget-content ui-corner-all" />',
                    '<label for="tbFindCase" style="display:inline;">' . tra("Case Insensitivity") . ':</label>',
                    '<input type="checkbox" id="tbFindCase" checked="checked" class="ui-widget-content ui-corner-all" />',
                    '<p class="description">' . tra("Note: Uses regular expressions") . '</p>',
                    // TODO add option to not
                    '{"open": function() { dialogFindOpen(area_id); },' .
                    '"buttons": { "' . tra("Close") . '": function() { dialogSharedClose(area_id,this); },' .
                    '"' . tra("Find") . '": function() { dialogFindFind(area_id); }}}',
                ];

                break;

            case 'replace':
                $icon = tra('img/icons/text_replace.png');
                $iconname = 'repeat';
                $wysiwyg = 'Replace';
                $label = tra('Text Replace');
                $tool_prefs[] = 'feature_wiki_replace';

                $list = [
                    tra('Text Replace'),
                    '<label for="tbReplaceSearch">' . tra("Search") . ':</label>',
                    '<input type="text" id="tbReplaceSearch" class="ui-widget-content ui-corner-all" />',
                    '<label for="tbReplaceReplace">' . tra("Replace") . ':</label>',
                    '<input type="text" id="tbReplaceReplace" class="ui-widget-content ui-corner-all clearfix" />',
                    '<label for="tbReplaceCase" style="display:inline;">' . tra("Case Insensitivity") . ':</label>',
                    '<input type="checkbox" id="tbReplaceCase" checked="checked" class="ui-widget-content ui-corner-all" />',
                    '<br /><label for="tbReplaceAll" style="display:inline;">' . tra("Replace All") . ':</label>',
                    '<input type="checkbox" id="tbReplaceAll" checked="checked" class="ui-widget-content ui-corner-all" />',
                    '<p class="description">' . tra("Note: Uses regular expressions") . '</p>',
                    // TODO add option to not
                    '{"open": function() { dialogReplaceOpen(area_id); },' .
                    '"buttons": { "' . tra("Close") . '": function() { dialogSharedClose(area_id,this); },' .
                    '"' . tra("Replace") . '": function() { dialogReplaceReplace(area_id); }}}',
                ];

                break;

            default:
                return null;
        }

        $tag = new self();
        $tag->name = $tagName;
        $tag->setWysiwygToken($wysiwyg)
            ->setLabel($label)
            ->setIconName(! empty($iconname) ? $iconname : 'help')
            ->setIcon(! empty($icon) ? $icon : 'img/icons/shading.png')
            ->setList($list)
            ->setType('Dialog')
            ->setClass('qt-picker');

        foreach ($tool_prefs as $pref) {
            $tag->addRequiredPreference($pref);
        }

        global $toolbarDialogIndex;
        ++$toolbarDialogIndex;
        $tag->index = $toolbarDialogIndex;

        ToolbarDialog::setupJs();

        return $tag;
    }

    public function setList(array $list): ToolbarItem
    {
        $this->list = $list;

        return $this;
    }

    public function getOnClick(): string
    {
        return 'displayDialog( this, ' . $this->index . ', \'' . $this->domElementId . '\')';
    }

    public static function setupJs()
    {
        TikiLib::lib('header')->add_jsfile('lib/jquery_tiki/tiki-toolbars.js');
    }

    public function getWikiHtml(): string
    {
        $headerlib = TikiLib::lib('header');
        $headerlib->add_js(
            "if (! window.dialogData) { window.dialogData = {}; } window.dialogData[$this->index] = "
            . json_encode($this->list) . ";",
            1 + $this->index
        );

        return $this->getSelfLink(
            $this->getOnClick(),
            htmlentities($this->label, ENT_QUOTES, 'UTF-8'),
            $this->getClass()
        );
    }

    public function getWysiwygToken(): string
    {
        if (! empty($this->wysiwyg)) {
            TikiLib::lib('header')->add_js(
                "window.dialogData[$this->index] = " . json_encode($this->list) . ";",
                1 + $this->index
            );
            $onClick = str_replace('\'' . $this->domElementId . '\'', 'editor.name', $this->getOnClick());
            $this->setupCKEditorTool($onClick, $this->wysiwyg, $this->label, $this->icon);
        }
        return $this->wysiwyg;
    }

    public function getWysiwygWikiToken(): string // wysiwyg_htmltowiki
    {
        switch ($this->name) {
            case 'tikilink':
                $this->wysiwyg = 'tikilink';
                break;
            case 'objectlink':
                $this->wysiwyg = 'objectlink';
                break;
            case 'table':
                $this->wysiwyg = 'tikitable';
                break;
            case 'link':
                $this->wysiwyg = 'externallink';
                break;
            default:
                return $this->wysiwyg;
        }

        return $this->getWysiwygToken();
    }
}
