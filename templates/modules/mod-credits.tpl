{tikimodule error=$module_params.error title=$tpl_module_title name="credits" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}
    {foreach key=id item=data from=$tiki_user_credits}
            <div>
                {$data.display_text|escape}:
                {if !empty($data.empty)}
                {section name=used loop=$data.discreet_used}{icon name='long-arrow-up' iclass='header_comptebarre text-danger'}{/section}{section name=remain loop=$data.discreet_remain}{icon name='long-arrow-up' iclass='header_comptebarre text-danger'}{/section} <span class="textes_comptevert"><font color='red'>{$data.used|default:0}</font></span>/{$data.total|default:0} {$data.unit_text|escape}
                    {tr}empty{/tr}
                {elseif $data.low}
                {section name=used loop=$data.discreet_used}{icon name='long-arrow-up' iclass='header_comptebarre text-warning'}{/section}{section name=remain loop=$data.discreet_remain}{icon name='long-arrow-up' iclass='header_comptebarre text-warning'}{/section} <span class="textes_comptevert"><font color='yellow'>{$data.used|default:0}</font></span>/{$data.total|default:0} {$data.unit_text|escape}
                    {tr}low{/tr}
                {else}
                    {section name=used loop=$data.discreet_used}{icon name='long-arrow-up' iclass='header_comptebarre text-success'}{/section}{section name=remain loop=$data.discreet_remain}{icon name='long-arrow-up' iclass='header_comptebarre text-success'}{/section} <span class="textes_comptevert">{$data.used|default:0}</span>/{$data.total|default:0} {$data.unit_text|escape}
                {/if}
            </div>
    {/foreach}
{/tikimodule}
