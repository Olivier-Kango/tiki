<?php

namespace Tiki\Lib\core\Toolbar;

use TikiLib;

class ToolbarPicker extends ToolbarDialog
{
    private string $styleType;

    public static function fromName($tagName, bool $is_wysiwyg = false, bool $is_html = false, bool $is_markdown = false, string $domElementId = ''): ?ToolbarItem
    {
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
            case 'emoji':
                // TODO once working this will replace feature_smileys
                $wysiwyg = 'Emoji';
                $label = tra('Emojis');
                $iconname = 'laugh-wink';
                $rawList = [];
                $tool_prefs[] = 'feature_smileys';
                $list = [];

                break;
            case 'color':
                $wysiwyg = 'TextColor';
                $label = tra('Foreground color');
                $iconname = 'font-color';
                $styleType = 'color';
                $list = [];

                break;

            case 'bgcolor':
                $label = tra('Background Color');
                $iconname = 'background-color';
                $wysiwyg = 'BGColor';
                $styleType = 'background-color';
                $list = [];

                break;

            default:
                return null;
        }


        $tag = new self();
        $tag->name = $tagName;

        $tag->isMarkdown = $is_markdown;
        $tag->isWysiwyg = $is_wysiwyg;

        $tag->setWysiwygToken($wysiwyg)
            ->setLabel($label)
            ->setIconName(! empty($iconname) ? $iconname : 'help')
            ->setList($list)
            ->setType('Picker')
            ->setClass('qt-picker ' . $tagName)
            ->setMarkdownSyntax($tagName)
            ->setMarkdownWysiwyg($tagName)
            ->setStyleType($styleType)
            ->setDomElementId($domElementId);

        foreach ($tool_prefs as $pref) {
            $tag->addRequiredPreference($pref);
        }

        global $toolbarPickerIndex;
        ++$toolbarPickerIndex;
        $tag->index = $toolbarPickerIndex;
        $tag->singleSpaAppName = "@vue-mf/emoji-picker-" . \Tiki\Utilities\Identifiers::getHttpRequestId() . '_' . $tag->index;
        $tag->singleSpaDomId = "single-spa-application:{$tag->singleSpaAppName}";
        $tag->setupJs();

        return $tag;
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
    public function getOnClick(): string
    {
        // N.B. output is enclosed in double quotes later
        if ($this->name == 'emoji' && $this->isDialogSupported()) {
            return 'displayEmojiPicker(\'' . str_replace('@vue-mf/', '', $this->singleSpaAppName) . '\', \'' . $this->domElementId . '\')';
        } elseif ($this->name === 'color' || $this->name === 'bgcolor') {
            $id = $this->name;
            return "
                const colorPicker = document.querySelector('sl-color-picker#$id');
                colorPicker.trigger.click();
            ";
        } else {
            return 'displayPicker( this, \'' . $this->name . '\', \'' .
                $this->domElementId . '\' )';
        }
    }

    public function setupJs(): void
    {
        static $pickerAdded = false;

        if ($this->name == 'emoji' && $this->isDialogSupported()) {
            TikiLib::lib('header')->add_jsfile('lib/jquery_tiki/tiki-toolbars.js');

            // TODO refactor with \Tiki\Lib\core\Toolbar\ToolbarDialog::setupJs
            TikiLib::lib('header')->add_js_module('
                import "@vue-mf/root-config";
                import "@vue-mf/emoji-picker";
            ');
            $data = get_object_vars($this);
            unset($data['list']);
            $data['pickerId'] = str_replace('@vue-mf/', '', $this->singleSpaAppName);

                        // language=JavaScript
            TikiLib::lib('header')->add_jq_onready('
    window.registerApplication({
        name: ' . json_encode($this->singleSpaAppName) . ',
        app: () => importShim("@vue-mf/emoji-picker"),
        activeWhen: (location) => {
            let condition = true;
            return condition;
        },
        customProps: {
            toolbarObject: ' . json_encode($data) . ',
            settings: {
                defaultColor: "#3B71CA",
                title: "my title",
                emoji: "sunglasses"
            }
        },
    })
    onDOMElementRemoved(' . json_encode($this->singleSpaDomId) . ', function () {
        window.unregisterApplication(' . json_encode($this->singleSpaAppName) . ');
    });');
        } elseif (! $pickerAdded && $this->name === 'specialchar') {
            TikiLib::lib('header')->add_jsfile('lib/jquery_tiki/tiki-toolbars.js');
            $pickerAdded = true;
        }
    }

    public function getWikiHtml(): string
    {
        global $section;

        if ($this->name === 'specialchar') {
            $this->setupPickerJS();
        }

        $html = $this->getSelfLink(
            $this->getOnClick(),
            htmlentities($this->label, ENT_QUOTES, 'UTF-8'),
            $this->getClass()
        );

        if ($this->name === 'emoji' && $this->isDialogSupported()) {
            $html .= $this->getEmojiPicker();
        }

        if ($this->name === 'color' || $this->name === 'bgcolor') {
            $id = $this->name;
            $colorPickerVar = "colorPicker$id";
            $areaId = $this->domElementId;
            $insertScriptValue = $this->name === 'color' ? "'~~' + this.value + ':text~~'" : "'~~text-color-goes-here,' + this.value + ':text~~'";
            "$.sheet.instance[I].cellChangeStyle(styleType, '');";
            TikiLib::lib('header')->add_jq_onready("
                const $colorPickerVar = document.querySelector('#$id');
                $colorPickerVar.trigger.style.cssText = 'visibility: hidden; position: absolute; margin-left: -50px; margin-top: -30px;'; // The spacings used are not totally accurate, but they are good enough visually for now.
                $colorPickerVar.addEventListener('sl-blur', function () {
                    if ('$section' === 'sheet') {
                        $.sheet.instance[$.sheet.I()].cellChangeStyle('$this->styleType', this.value);
                    } else {
                        insertAt('$areaId', $insertScriptValue);
                    }
                });
            ");
            $html .= "<sl-color-picker 
                id='$id'
                value='black'
                swatches='
                    #d0021b; #f5a623; #f8e71c; #8b572a; #7ed321; #417505; #bd10e0; #9013fe;
                    #4a90e2; #50e3c2; #b8e986; #000; #444; #888; #ccc; #fff;
                '
            ></sl-color-picker>";
        }

        return $html;
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
                "tuiToolbarItem$this->markdown_wysiwyg = $.fn.getIcon('$this->iconname').on('click', function () {
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

    public function getEmojiPicker(): string
    {
        return "<div id='" . str_replace('@vue-mf/', '', $this->singleSpaAppName) . "' class='emoji-picker' style='display:none'></div>";
    }
}
