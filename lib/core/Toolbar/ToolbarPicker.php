<?php

namespace Tiki\Lib\core\Toolbar;

use TikiLib;

class ToolbarPicker extends ToolbarItem
{
    private array $list;
    private string $name;
    private int $index;
    private string $styleType;

    public static function fromName($tagName): ?ToolbarItem
    {
        global $section;
        $headerlib = TikiLib::lib('header');

        $tool_prefs = [];
        $styleType = '';

        switch ($tagName) {
            case 'specialchar':
                $wysiwyg = 'SpecialChar';
                $label = tra('Special Characters');
                $iconname = 'keyboard';
                // Line taken from DokuWiki + some added chars for Tiki
                $list = explode(
                    ' ',
                    'À à Á á Â â Ã ã Ä ä Ǎ ǎ Ă ă Å å Ā ā Ą ą Æ æ Ć ć Ç ç Č č Ĉ ĉ Ċ ċ Ð đ ð Ď ď È è É é Ê ê Ë ë Ě ě Ē ē Ė ė Ę ę Ģ ģ Ĝ ĝ Ğ ğ Ġ ġ Ĥ ĥ Ì ì Í í Î î Ï ï Ǐ ǐ Ī ī İ ı Į į Ĵ ĵ Ķ ķ Ĺ ĺ Ļ ļ Ľ ľ Ł ł Ŀ ŀ Ń ń Ñ ñ Ņ ņ Ň ň Ò ò Ó ó Ô ô Õ õ Ö ö Ǒ ǒ Ō ō Ő ő Œ œ Ø ø Ŕ ŕ Ŗ ŗ Ř ř Ś ś Ş ş Š š Ŝ ŝ Ţ ţ Ť ť Ù ù Ú ú Û û Ü ü Ǔ ǔ Ŭ ŭ Ū ū Ů ů ǖ ǘ ǚ ǜ Ų ų Ű ű Ŵ ŵ Ý ý Ÿ ÿ Ŷ ŷ Ź ź Ž ž Ż ż Þ þ ß Ħ ħ ¿ ¡ ¢ £ ¤ ¥ € ¦ § ª ¬ ¯ ° ± ÷ ‰ ¼ ½ ¾ ¹ ² ³ µ ¶ † ‡ · • º ∀ ∂ ∃ Ə ə ∅ ∇ ∈ ∉ ∋ ∏ ∑ ‾ − ∗ √ ∝ ∞ ∠ ∧ ∨ ∩ ∪ ∫ ∴ ∼ ≅ ≈ ≠ ≡ ≤ ≥ ⊂ ⊃ ⊄ ⊆ ⊇ ⊕ ⊗ ⊥ ⋅ ◊ ℘ ℑ ℜ ℵ ♠ ♣ ♥ ♦ 𝛼 𝛽 𝛤 𝛾 𝛥 𝛿 𝜀 𝜁 𝛨 𝜂 𝛩 𝜃 𝜄 𝜅 𝛬 𝜆 𝜇 𝜈 𝛯 𝜉 𝛱 𝜋 𝛳 𝜍 𝛴 𝜎 𝜏 𝜐 𝛷 𝜑 Χ 𝜒 𝛹 𝜓 𝛺 𝜔 𝛻 𝜕 ★ ☆ ☎ ☚ ☛ ☜ ☝ ☞ ☟ ☹ ☺ ✔ ✘ × „ “ ” ‚ ‘ ’ « » ‹ › — – … ← ↑ → ↓ ↔ ⇐ ⇑ ⇒ ⇓ ⇔ © ™ ® ′ ″ ^ @ % ~ | [ ] { } * #'
                );
                $list = array_combine($list, $list);
                break;
            case 'smiley':
                $wysiwyg = 'Emoji';
                $label = tra('Smileys');
                $iconname = 'smile';
                $rawList = [
                    'biggrin',
                    'confused',
                    'cool',
                    'cry',
                    'eek',
                    'evil',
                    'exclaim',
                    'frown',
                    'idea',
                    'lol',
                    'mad',
                    'mrgreen',
                    'neutral',
                    'question',
                    'razz',
                    'redface',
                    'rolleyes',
                    'sad',
                    'smile',
                    'surprised',
                    'twisted',
                    'wink',
                    'arrow',
                    'santa',
                ];
                $tool_prefs[] = 'feature_smileys';

                $list = [];
                foreach ($rawList as $smiley) {
                    $tra = htmlentities(tra($smiley), ENT_QUOTES, 'UTF-8');
                    $list["(:$smiley:)"] = '<img src="' . $headerlib->convert_cdn(
                        'img/smiles/icon_' . $smiley . '.gif'
                    ) . '" alt="' . $tra . '" title="' . $tra . '" width="15" height="15" />';
                }

                break;
            case 'color':
                $wysiwyg = 'TextColor';
                $label = tra('Foreground color');
                $iconname = 'font-color';
                $rawList = [];
                $styleType = 'color';

                $hex = ['0', '3', '6', '8', '9', 'C', 'F'];
                $count_hex = count($hex);

                for ($r = 0; $r < $count_hex; $r += 2) { // red
                    for ($g = 0; $g < $count_hex; $g += 2) { // green
                        for ($b = 0; $b < $count_hex; $b += 2) { // blue
                            $color = $hex[$r] . $hex[$g] . $hex[$b];
                            $rawList[] = $color;
                        }
                    }
                }

                $list = [];
                foreach ($rawList as $color) {
                    $list["~~#$color:text~~"] = "<span style='background-color: #$color' title='#$color' />&nbsp;</span>";
                }

                if ($section == 'sheet') {
                    $list['reset'] = "<span title=':" . tra(
                        "Reset Colors"
                    ) . "' class='toolbars-picker-reset' reset='true'>" . tra("Reset") . "</span>";
                }

                break;

            case 'bgcolor':
                $label = tra('Background Color');
                $iconname = 'background-color';
                $wysiwyg = 'BGColor';
                $styleType = 'background-color';
                $rawList = [];

                $hex = ['0', '3', '6', '8', '9', 'C', 'F'];
                $count_hex = count($hex);

                for ($r = 0; $r < $count_hex; $r += 2) { // red
                    for ($g = 0; $g < $count_hex; $g += 2) { // green
                        for ($b = 0; $b < $count_hex; $b += 2) { // blue
                            $color = $hex[$r] . $hex[$g] . $hex[$b];
                            $rawList[] = $color;
                        }
                    }
                }

                $list = [];
                foreach ($rawList as $color) {
                    $list["~~black,#$color:text~~"] = "<span style='background-color: #$color' title='#$color' />&nbsp;</span>";
                }
                if ($section == 'sheet') {
                    $list['reset'] = "<span title='" . tra(
                        "Reset Colors"
                    ) . "' class='toolbars-picker-reset' reset='true'>" . tra("Reset") . "</span>";
                }

                break;

            default:
                return null;
        }

        $tag = new self();
        $tag->setWysiwygToken($wysiwyg)
            ->setLabel($label)
            ->setIconName(! empty($iconname) ? $iconname : 'help')
            ->setList($list)
            ->setType('Picker')
            ->setClass('qt-picker')
            ->setName($tagName)
            ->setMarkdownSyntax($tagName)
            ->setMarkdownWysiwyg($tagName)
            ->setStyleType($styleType);

        foreach ($tool_prefs as $pref) {
            $tag->addRequiredPreference($pref);
        }

        global $toolbarPickerIndex;
        ++$toolbarPickerIndex;
        $tag->index = $toolbarPickerIndex;
        ToolbarPicker::setupJs();

        return $tag;
    }

    public function setName(string $name): ToolbarItem
    {
        $this->name = $name;

        return $this;
    }


    public function getWysiwygWikiToken(): string // wysiwyg_htmltowiki
    {
        switch ($this->wysiwyg) {
            case 'BGColor':
            case 'TextColor':
            case 'SpecialChar':
            case 'Emoji':
                return $this->wysiwyg;
            default:
                return '';
        }
    }


    public function setList(array $list): ToolbarItem
    {
        $this->list = $list;

        return $this;
    }


    public function getOnClick(): string
    {
        global $section;
        if ($section == 'sheet') {
            return 'displayPicker( this, \'' . $this->name . '\', \'' . $this->domElementId . '\', true, \'' . $this->styleType . '\' )';   // is enclosed in double quotes later
        } else {
            return 'displayPicker( this, \'' . $this->name . '\', \'' . $this->domElementId . '\' )';   // is enclosed in double quotes later
        }
    }

    private static function setupJs(): void
    {
        static $pickerAdded = false;

        if (! $pickerAdded) {
            TikiLib::lib('header')->add_jsfile('lib/jquery_tiki/tiki-toolbars.js');
            $pickerAdded = true;
        }
    }

    public function getWikiHtml(): string
    {
        $this->setupPickerJS();

        return $this->getSelfLink(
            $this->getOnClick(),
            htmlentities($this->label, ENT_QUOTES, 'UTF-8'),
            $this->getClass()
        );
    }

    public function getMarkdownHtml(): string
    {
        if (in_array($this->name, ['specialchar', 'smiley'])) {
            return $this->getWikiHtml();
        } else {
            return '';
        }
    }


    public function getMarkdownWysiwyg(): string
    {
        if (in_array($this->name, ['specialchar', 'smiley'])) {
            $this->setupPickerJS();

            \TikiLib::lib('header')->add_jq_onready(
                "tuiToolbarItem$this->markdown_wysiwyg = $.fn.getIcon('$this->iconname').click(function () {
                        {$this->getOnClick()}
                    }).get(0);"
            );

            $item = [
                'name'    => $this->markdown,
                'tooltip' => $this->label,
                'el'      => "%~tuiToolbarItem{$this->markdown_wysiwyg}~%",
            ];
            return json_encode($item);
        }
        return '';
    }

    protected function setStyleType(string $type): ?ToolbarItem
    {
        $this->styleType = $type;

        return $this;
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function setupPickerJS(): void
    {
        TikiLib::lib('header')->add_js(
            "if (! window.pickerData) { window.pickerData = {}; } window.pickerData['$this->name'] = " . str_replace(
                '\/',
                '/',
                json_encode($this->list)
            ) . ";"
        );
    }
}
