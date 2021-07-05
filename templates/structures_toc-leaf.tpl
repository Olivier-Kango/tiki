{strip}
    <li class="row {if $toc_type eq 'fancy'}fancy{elseif $toc_type eq 'admin'} admin{/if}toclevel"{if $toc_type eq 'admin'} id="node_{$structure_tree.page_ref_id}" data-id="{$structure_tree.page_ref_id}"{/if}>
        <div class="col-sm-12">
            {if $numbering}
                <span class="prefix">{$structure_tree.prefix}&nbsp;</span>
            {/if}
            {if $showdesc and $structure_tree.description and $toc_type neq 'admin'}
                <span class="description">{$structure_tree.description|escape} :&nbsp;</span>
            {/if}
            <label>
                <a href={if $toc_type eq 'admin'}
                            "#"
                        {else}
                            "{sefurl page=$structure_tree.pageName structure=$structurePageName page_ref_id=$structure_tree.page_ref_id}"
                        {/if}
                        class="link" title="
                    {if $showdesc}
                        {if $structure_tree.page_alias}
                            {$structure_tree.page_alias|escape}
                        {else}
                            {$structure_tree.pageName|escape}
                        {/if}
                    {else}
                        {$structure_tree.description|escape}
                    {/if}">
                    {if $hilite}<strong>{/if}
                    {if $structure_tree.page_alias and $toc_type neq 'admin'}
                        {$structure_tree.page_alias|escape}
                    {else}
                        {$structure_tree.short_pageName|escape}
                    {/if}
                    {if $hilite}</strong>{/if}
                </a>
            </label>
            {if $toc_type eq 'admin'}
                <div class="actions input-group input-group-sm mb-2">
                    <div class="input-group-append">
                        <span class="input-group-text">{icon name='sort'}</span>
                    </div>
                    <input type="text" class="page-alias-input form-control" value="{$structure_tree.page_alias|escape}" placeholder="{tr}Page alias...{/tr}">
                    <div class="input-group-append">
                        {self_link _script='tiki-index.php' page=$structure_tree.pageName structure=$structure_name _class="tips input-group-text" _title=":{tr}View{/tr}" _noauto="y"}
                            {icon name="view"}
                        {/self_link}

                        {if $tiki_p_watch_structure eq 'y'}
                            {if !$structure_tree.event}
                                {self_link page_ref_id=$structure_tree.page_ref_id watch_object=$structure_tree.page_ref_id watch_action=add page=$structure_tree.pageName _class="tips input-group-text" _title=":{tr}Monitor the Sub-Structure{/tr}"}
                                    {icon name="watch"}
                                {/self_link}
                            {else}
                                {self_link page_ref_id=$structure_tree.page_ref_id watch_object=$structure_tree.page_ref_id watch_action=remove _class="tips input-group-text" _title=":{tr}Stop Monitoring the Sub-Structure{/tr}"}
                                    {icon name="stop-watching"}
                                {/self_link}
                            {/if}
                        {/if}

                        {if $structure_tree.editable}
                            {if $structure_tree.flag == 'L'}
                                <div class="input-group-text">
                                    {capture assign=title}{tr _0=$structure_tree.user}locked by %0{/tr}{/capture}
                                    {icon name='lock' alt="{tr}Locked{/tr}" title=$title}
                                </div>
                            {else}
                                {self_link _script='tiki-editpage.php' page=$structure_tree.pageName _class='tips input-group-text' _title=':{tr}Edit page{/tr}'}
                                    {icon name="edit"}
                                {/self_link}
                            {/if}
                            {if empty($page)}
                                {self_link _onclick="addNewPage(this);return false;" _class="tips input-group-text" _title=":{tr}Add new child page{/tr}"}
                                    {icon name="add"}
                                {/self_link}
                                {self_link _onclick="movePageToStructure(this);return false;" _class="tips input-group-text" _title=":{tr}Move{/tr}"}
                                    {icon name="caret-right"}
                                {/self_link}
                                {self_link page_ref_id=$structure_tree.page_ref_id remove=$structure_tree.page_ref_id _class="tips input-group-text text-danger" _title=":{tr}Delete{/tr}"}
                                    {icon name="delete"}
                                {/self_link}
                            {/if}
                        {/if}
                    </div>
                </div>
            {/if}
            {if (!$showdesc and $toc_type eq 'fancy') or ($showdesc and $toc_type eq 'admin' and !empty($structure_tree.description))} : <span class="description">{$structure_tree.description|escape}</span>{/if}
        </div>
    {* no </li> here *}
{/strip}