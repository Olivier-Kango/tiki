<dl class="row mx-0">
    {if $tracker_info.showStatus eq 'y' or ($tracker_info.showStatusAdminOnly eq 'y' and $tiki_p_admin_trackers eq 'y')}
        {assign var=ustatus value=$info.status|default:"p"}
        <dt title="{tr}Status{/tr}" class="col-sm-3">{tr}Status{/tr}</dt>
        <dd class="col-sm-9">
            {icon name=$status_types.$ustatus.iconname}
            {$status_types.$ustatus.label}
        </dd>
    {/if}
    {assign var=stick value="n"}

    {foreach from=$fields key=ix item=cur_field}
        {if !($tracker_info.doNotShowEmptyField eq 'y' and isset($cur_field.field_is_empty) and $cur_field.field_is_empty) and $cur_field.visibleInViewMode eq 'y'}

            {if $cur_field.type eq 'h'}
                </dl>
                {$level = $cur_field.options_map.level}
                {if $level lt 1}{$level = 3}{/if}
                <h{$level}>{$cur_field.name|tra|escape}</h{$level}>
                {if !empty($cur_field.description)}
                    <p>{$cur_field.description|tra|escape}</p>
                {/if}
                <dl class="row mx-0">
            {* Make adjustments for line breaks *}
            {elseif ($cur_field.type eq 't' and $cur_field.options_array[0] eq '0') or
                ($cur_field.type eq 'a' and $cur_field.options_array[8] eq '0') or
                ($cur_field.type eq 'n' and $cur_field.options_array[0] eq '0') or
                ($cur_field.type eq 'b' and $cur_field.options_array[0] eq '0')
            }
                <dt title="{$cur_field.name|tra|escape}" class="col-sm-3">{$cur_field.name|tra|escape}</dt>
                <dd class="col-sm-9" style="word-wrap: break-word;">{trackeroutput field=$cur_field item=$item_info showlinks=n list_mode=n}</dd>
            {else}
                <dt title="{$cur_field.name|tra|escape}" class="col-sm-3">{$cur_field.name|tra|escape}</dt>
                <dd class="col-sm-9" style="word-wrap: break-word;">{trackeroutput field=$cur_field item=$item_info showlinks=n list_mode=n}</dd>
            {/if}
        {/if}
    {/foreach}
    {if $tracker_info.showCreatedView eq 'y'}
        <dt title="{tr}Created{/tr}" class="col-sm-3">{tr}Created{/tr}</dt>
        <dd class="col-sm-9">{$info.created|tiki_long_datetime}{if $tracker_info.showCreatedBy eq 'y'}<br>{tr}by{/tr} {if $prefs.user_show_realnames eq 'y'}{if empty($info.createdBy)}Unknown{else}{$info.createdBy|username}{/if}{else}{if empty($info.createdBy)}Unknown{else}{$info.createdBy}{/if}{/if}{/if}</dd>
    {/if}
    {if $tracker_info.showLastModifView eq 'y'}
        <dt title="{tr}LastModif{/tr}" class="col-sm-3">{tr}LastModif{/tr}</dt>
        <dd class="col-sm-9">{$info.lastModif|tiki_long_datetime}{if $tracker_info.showLastModifBy eq 'y'}<br>{tr}by{/tr} {if $prefs.user_show_realnames eq 'y'}{if empty($info.lastModifBy)}Unknown{else}{$info.lastModifBy|username}{/if}{else}{if empty($info.lastModifBy)}Unknown{else}{$info.lastModifBy}{/if}{/if}{/if}</dd>
    {/if}
</dl>
