{* build link *}
{capture assign=link}
    {include 'fgal_file_link_attributes.tpl' disableTplLogging=true}
{/capture}

{math equation="x + 6" x=$thumbnail_size assign=thumbnailcontener_size}

{* thumbnail actions wrench *}
{capture name="thumbactions"}
    {if ($prefs.fgal_show_thumbactions eq 'y' or $show_details eq 'y')}
    <div class="thumbactions mt-3">
        {if !isset($gal_info.show_action) or $gal_info.show_action neq 'n'}
            {if ( $prefs.use_context_menu_icon eq 'y' or $prefs.use_context_menu_text eq 'y' )}
                <a class="fgalname tips" title="{tr}Actions{/tr}" href="#" {popup fullhtml="1" text={include file='fgal_context_menu.tpl' menu_icon=$prefs.use_context_menu_icon menu_text=$prefs.use_context_menu_text changes=$smarty.section.changes.index} trigger="click"}>
                    {icon name='wrench' alt="{tr}Actions{/tr}"}
                </a>
            {else}
                {include file='fgal_context_menu.tpl'}
            {/if}
        {/if}
    </div> {* thumbactions *}
    {/if}
{/capture}
<div class="d-flex flex-wrap align-items-start">
    <div class="flex-shrink-0">
        {if !empty($file)}
            {include file='fgal_thumbnailframe.tpl'}
            {if $show_infos eq 'y'}
                <div class="thumbinfos">
                    {$smarty.capture.thumbactions}
                </div>
            {/if}
        {/if}
    </div>
    <div class="flex-grow-1 ms-3">
        {*<div class='box-data'>*}
            {if !empty($file)}
                {include file='file_properties_table.tpl'}
            {else}
                {if $view == 'page'}
                    <div>
                        <b>{tr}No records found{/tr}</b>
                    </div>
                {/if}
            {/if}
        {*</div>*}
    </div>
</div> {* thumbnailcontener *}
    {if !empty($file)}
        {include file='tiki-upload_file_progress.tpl' fileId=$file.id name=$file.filename}
    {/if}

    {if isset($metarray) and $metarray|count gt 0}
        <br>
        <div class="text-start">
            {remarksbox type="tip" title="{tr}Metadata{/tr}"}
                {include file='metadata/meta_view_tabs.tpl'}
            {/remarksbox}
        </div>
    {/if}
