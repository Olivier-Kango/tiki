{if isset($contributors_details)}
{tikimodule title=$tpl_module_title name="contributors" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle error=$module_params.error}
    <div class="contributors">
    {foreach from=$contributors_details item=contributor name=contributors}
    <div style="margin-bottom: 17px">
        {$contributor.login|userlink}<span style="float:right">{$contributor.avatar}</span>
        {if !empty($contributor.realName)}<br>{$contributor.realName|escape}{/if}
        {if isset($contributor.country)}<br>{$contributor.login|countryflag} {tr}{$contributor.country|stringfix}{/tr}{/if}
        {if isset($contributor.email)}<br>{$contributor.scrambledEmail}{/if}
        {if !empty($contributor.homePage)}<br><a href="{$contributor.homePage|escape}" class="link" target="_blank">{tr}Homepage{/tr}</a>{/if}
    </div>
    {/foreach}
    {if isset($hiddenContributors)}
    <a href="#">{if $hiddenContributors eq 1}{tr}1 more contributor{/tr}{else}{tr _0=$hiddenContributors}%0 more contributors{/tr}{/if}</a>
    {jq}
    $('div.contributors').each(function() {
        $(this).children('div:gt(4)').hide();
    });
    $('div.contributors > a').on("click", function() {
        $(this).siblings('div').show();
        $(this).hide();
        return false;
    });
    {/jq}
    {/if}
    </div>
{/tikimodule}
{/if}
