{if $prefs.feature_freetags eq 'y' && $tiki_p_freetags_tag eq 'y'}
    <div class="mb-3 row">
        <label class="{if empty($labelColClass)}col-md-4{else}{$labelColClass}{/if} col-form-label" for="tagBox">{tr}Tags{/tr}</label>
        <div class="{if empty($inputColClass)}col-md-8{else}{$inputColClass}{/if}">
{jq notonready=true}
    function addTag(tag) {
        document.getElementById('tagBox').value = document.getElementById('tagBox').value + ' ' + tag;
    }
{/jq}
            <div id="freetager">
                <input type="text" id="tagBox" class="form-control" name="freetag_string" value="{$taglist|escape}">
                {foreach from=$tag_suggestion item=t}
                    {capture name=tagurl}{if (strstr($t, ' '))}"{$t}"{else}{$t}{/if}{/capture}
                    <a href="javascript:addTag('{$smarty.capture.tagurl|escape:'javascript'|escape}');" onclick="javascript:needToConfirm=false">{$t|escape}</a>
                {/foreach}
                {if $prefs.feature_help eq 'y'}
                    <div class="form-text">
                        {tr}Put tags separated by spaces. For tags with more than one word, use no spaces and put words together or enclose them with double quotes.{/tr}
                    </div>
                {/if}
            </div>
        </div>
    </div>
    {if $prefs.feature_multilingual eq 'y' && $prefs.freetags_multilingual eq 'y' && $blog eq 'y'}
        <div class="mb-3 row">
            <label for="" class="{if empty($labelColClass)}col-md-4{else}{$labelColClass}{/if} col-form-label">{tr}Language{/tr}</label>
            <div class="{if empty($inputColClass)}col-md-8{else}{$inputColClass}{/if}">
                <select name="lang" class="form-control">
                    <option value="">{tr}All{/tr}</option>
                        {section name=ix loop=$languages}
                            <option value="{$languages[ix].value|escape}"{if $lang eq $languages[ix].value} selected="selected"{/if}>{$languages[ix].name}</option>
                        {/section}
                </select>
            </div>
        </div>
    {/if}
{/if}
