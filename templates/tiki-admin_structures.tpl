{title help="Structures"}{tr}Structures{/tr}{/title}

{if $tiki_p_admin eq 'y'}
    <div class="t_navbar mb-4">
        <a role="link" href="tiki-import_xml_zip.php" class="btn btn-link" title="{tr}XML Zip Import{/tr}">
            {icon name="zip"} {tr}XML Zip Import{/tr}
        </a>
    </div>
{/if}

{if $just_created neq 'n' && $tiki_p_edit_structures == 'y'}
    {remarksbox type="feedback" title="{tr}Feedback{/tr}"}
        {tr}Structure created:{/tr} <a class='alert-link' href='tiki-edit_structure.php?page_ref_id={$just_created}'>{$just_created_name|escape}</a> <a class='alert-link tips' href='tiki-index.php?page={$just_created_name|escape:"url"}' title=":{tr}View Page{/tr}">{icon name="view"}</a>
    {/remarksbox}
{/if}

{if $askremove eq 'y'}
    {remarksbox type='confirm' title="{tr}Please Confirm{/tr}"}
        {tr}You will remove structure:{/tr} {$removename|escape}<br>
        {button href="?rremove=$remove&amp;page=$removename" _text="{tr}Destroy the structure leaving the wiki pages{/tr}"}
        {if $tiki_p_remove == 'y'}
            {button href="?rremovex=$remove&amp;page=$removename" _text="{tr}Destroy the structure and remove the pages{/tr}"}
        {/if}
    {/remarksbox}
{/if}

{if count($alert_in_st) > 0}
    {remarksbox type='warning' title="{tr}Warning{/tr}"}
        {tr}Note that the following pages are also part of another structure. Make sure that access permissions (if any) do not conflict:{/tr}
        {foreach from=$alert_in_st item=thest}
            &nbsp;&nbsp;<a class='tablename alert-link' href='tiki-index.php?page={$thest|escape:"url"}' target="_blank">{$thest}</a>
        {/foreach}
    {/remarksbox}
{/if}

{if count($alert_categorized) > 0}
    {remarksbox type='feedback' title="{tr}Feedback{/tr}"}
        {tr}The following pages have automatically been categorized with the same categories as the structure:{/tr}
        {foreach from=$alert_categorized item=thecat}
            &nbsp;&nbsp;<a class='tablename alert-link' href='tiki-index.php?page={$thecat|escape:"url"}' target="_blank">{$thecat}</a>
        {/foreach}
    {/remarksbox}
{/if}

{if count($alert_to_remove_cats) > 0}
    {remarksbox type='warning' title="{tr}Warning{/tr}"}
        {tr}The following pages have categories but the structure has none. You may wish to uncategorize them to be consistent:{/tr}
        {foreach from=$alert_to_remove_cats item=thecat}
            &nbsp;&nbsp;<a class='tablename alert-link' href='tiki-index.php?page={$thecat|escape:"url"}' target="_blank">{$thecat}</a>
        {/foreach}
    {/remarksbox}
{/if}

{if count($alert_to_remove_extra_cats) > 0}
    {remarksbox type='warning' title="{tr}Warning{/tr}"}
        {tr}The following pages are in categories that the structure is not in. You may wish to recategorize them in order to be consistent:{/tr}
        {foreach from=$alert_to_remove_extra_cats item=theextracat}
            &nbsp;&nbsp;<a class='tablename alert-link' href='tiki-index.php?page={$theextracat|escape:"url"}' target="_blank">{$theextracat}</a>
        {/foreach}
    {/remarksbox}
{/if}

{if !empty($error)}
    {remarksbox type='warning' title="{tr}Error{/tr}"}
        {$error|escape}
    {/remarksbox}
{/if}

{tabset}
    {tab name="{tr}Existing Structures{/tr}"}
        <h2>{tr}Existing Structures{/tr}</h2>
        {if $channels or ($find ne '')}
            <div class="clearfix">
                {include file='find.tpl' find_show_languages='y' find_show_categories='y' find_show_num_rows='y'}
            </div>
        {/if}
        <form class="form">
            {ticket}
            <div class="{if $js}table-responsive{/if}"> {* table-responsive class cuts off css drop-down menus *}
                <table class="table table-striped table-hover">
                    <tr>
                        {if $tiki_p_admin eq 'y'}<th width="15">{select_all checkbox_names='action[]'}</th>{/if}
                        <th>{tr}Structure{/tr}</th>
                        <th></th>
                    </tr>

                    {section loop=$channels name=ix}
                        <tr>
                            {if $tiki_p_admin eq 'y'}
                                <td class="checkbox-cell">
                                    <input type="checkbox" class="form-check-input" name="action[]" value='{$channels[ix].page_ref_id}' aria-label="Select">
                                </td>
                            {/if}
                            <td class="text">
                                <a class="tips" href="tiki-edit_structure.php?page_ref_id={$channels[ix].page_ref_id}" title=":{tr}View Structure{/tr}">
                                    {$channels[ix].pageName|escape}
                                    {if $channels[ix].page_alias}
                                        ({$channels[ix].page_alias|escape})
                                    {/if}
                                </a>
                            </td>
                            <td class="action">
                                {if $prefs.lock_wiki_structures eq 'y'}
                                    {lock type='wiki structure' object=$channels[ix].pageName}
                                {/if}

                                {actions}
                                    {strip}
                                        <action>
                                            <a href="tiki-edit_structure.php?page_ref_id={$channels[ix].page_ref_id}">
                                                {icon name="information" _menu_text='y' _menu_icon='y' alt="{tr}View structure{/tr}"}
                                            </a>
                                        </action>
                                        <action>
                                            <a href='{sefurl page=$channels[ix].pageName structure=$channels[ix].pageName page_ref_id=$channels[ix].page_ref_id}'>
                                                {icon name="view" _menu_text='y' _menu_icon='y' alt="{tr}View page{/tr}"}
                                            </a>
                                        </action>
                                        {if $channels[ix].admin_structure == 'y' or $tiki_p_admin == 'y'} {* A check for object perm tiki_p_assign_perm_wiki_page if not admin is needed here for WYSIWYCA *}
                                            <action>
                                                {permission_link mode=text objectType='wiki page' type='wiki structure' id=$channels[ix].pageName title=$channels[ix].pageName}
                                            </action>
                                        {/if}

                                        {if $prefs.feature_wiki_export eq 'y' and $channels[ix].admin_structure eq 'y'}
                                            <action>
                                                <a href="tiki-admin_structures.php?export={$channels[ix].page_ref_id|escape:"url"}">
                                                    {icon name="export" _menu_text='y' _menu_icon='y' alt="{tr}Export pages{/tr}"}
                                                </a>
                                            </action>
                                        {/if}

                                        {if $pdf_export eq 'y'}
                                            <action>
                                                <a href="tiki-print_multi_pages.php?printstructures=%255B%2522{$channels[ix].page_ref_id}%2522%255D&amp;display=pdf&amp;print=pdf">
                                                    {icon name='pdf' _menu_text='y' _menu_icon='y' alt="{tr}PDF{/tr}"}
                                                </a>
                                            </action>
                                        {/if}

                                        {if $channels[ix].edit_structure == 'y' or $channels[ix].admin_structure == 'y'}
                                            <action>
                                                <a href="tiki-admin_structures.php?export_tree={$channels[ix].page_ref_id|escape:"url"}">
                                                    {icon name="structure" _menu_text='y' _menu_icon='y' alt="{tr}Dump tree{/tr}"}
                                                </a>
                                            </action>
                                        {/if}

                                        {if $channels[ix].admin_structure == 'y'}
                                            <action>
                                                <form action="tiki-admin_structures.php" method="post">
                                                    {ticket}
                                                    <input type="hidden" name="remove" value="{$channels[ix].page_ref_id}">
                                                    <button type="submit" class="btn btn-link px-0 pt-0" onclick="confirmPopup()">
                                                        {icon name='remove' _menu_text='y' _menu_icon='y' alt="{tr}Remove{/tr}"}
                                                    </button>
                                                </form>
                                            </action>
                                        {/if}

                                        {if $prefs.feature_create_webhelp == 'y' && $channels[ix].edit_structure == 'y'}
                                            <action>
                                                <a href="tiki-create_webhelp.php?struct={$channels[ix].page_ref_id|escape:"url"}">
                                                    {icon name="help" _menu_text='y' _menu_icon='y' alt="{tr}Create WebHelp{/tr}"}
                                                </a>
                                            </action>
                                        {/if}

                                        {if $prefs.feature_create_webhelp == 'y' && $channels[ix].webhelp eq 'y'}
                                            <action>
                                                <a href="whelp/{$channels[ix].pageName}/index.html">
                                                    {icon name="documentation" _menu_text='y' _menu_icon='y' alt="{tr}View WebHelp{/tr}"}
                                                </a>
                                            </action>
                                        {/if}

                                        {if $channels[ix].admin_structure eq 'y'}
                                            <action>
                                                <a href="tiki-admin_structures.php?zip={$channels[ix].page_ref_id|escape:"url"}">
                                                    {icon name="zip" _menu_text='y' _menu_icon='y' alt="{tr}XML Zip{/tr}"}
                                                </a>
                                            </action>
                                        {/if}
                                    {/strip}
                                {/actions}
                            </td>
                        </tr>
                    {sectionelse}
                        {if $tiki_p_admin eq 'y'}{norecords _colspan=3}{else}{norecords _colspan=2}{/if}
                    {/section}
                </table>
            </div>

            {if $tiki_p_admin eq 'y'}
                <div class="mb-3 row mx-0">
                    <label for="batchaction" class="col-form-label">{tr}Perform action with selected{/tr}</label>
                    <div class="input-group col-sm-6">
                        <select name="batchaction" class="form-control">
                            <option value="">{tr}...{/tr}</option>
                            <option value="delete">{tr}Delete{/tr}</option>
                            <option value="delete_with_page">{tr}Delete with the pages{/tr}</option>
                        </select>
                        <input type="submit" class="btn btn-primary" name="act" value="{tr}Ok{/tr}">
                    </div>
                </div>
            </form>
        {/if}

        {pagination_links cant=$cant step=$maxRecords offset=$offset}{/pagination_links}
    {/tab}

    {if $tiki_p_edit_structures == 'y'}
        {tab name="{tr}Create Structure{/tr}"}
            <h2>{tr}Create Structure{/tr}</h2>
            <form action="tiki-admin_structures.php" method="post">
                {ticket}
                <div class="mb-3 row">
                    <label class="col-form-label col-md-3" for="name">{tr}Structure{/tr}</label>
                    <div class="col-md-9">
                        <input type="text" name="name" id="name" class="form-control">
                    </div>
                </div>
                <div class="mb-3 row">
                    <label class="col-form-label col-md-3" for="alias">{tr}Alias{/tr}</label>
                    <div class="col-md-9">
                        <input type="text" name="alias" id="alias" class="form-control">
                    </div>
                </div>
                <div class="mb-3 row">
                    <label class="col-form-label col-md-3" for="tree">{tr}Tree{/tr}</label>
                    <div class="col-md-9">
                        <textarea rows="5" cols="60" id="tree" name="tree" class="form-control"></textarea>
                        <div class="form-text">{tr}Use single spaces to indent structure levels{/tr}</div>
                    </div>
                </div>
                {if $prefs.lock_wiki_structures eq 'y'}
                    <div class="mb-3 row">
                        <label class="col-sm-3 col-form-label">{tr}Lock{/tr}</label>
                        <div class="col-sm-9">
                            {lock type='wiki structure' object=0}
                        </div>
                    </div>
                {/if}
                {if $prefs.feature_categories eq 'y'}
                    {include file='categorize.tpl'}
                {/if}
                <div class="mb-3 row">
                    <div class="submit col-md-9 offset-md-3">
                        <input type="submit" class="btn btn-primary" value="{tr}Create New Structure{/tr}" name="create">
                    </div>
                </div>
            </form>
        {/tab}
    {/if}
{/tabset}
