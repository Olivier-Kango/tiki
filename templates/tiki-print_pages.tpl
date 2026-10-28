{title}{tr}Print multiple pages{/tr}{/title}

{include file='find.tpl'}

{tabset name='tabs_print_pages'}
    {if $prefs.feature_wiki_structure eq 'y'}
        {tab name="{tr}Structures{/tr}"}
        {* --- tab with structures -- *}
            {if $printstructures}
                <h2>{tr}Selected Structures{/tr}</h2>
                <form id="removeStructureForm" action="tiki-print_pages.php" method="post">
                    {ticket}
                    <input type="hidden" name="printstructures" value="{$form_printstructures|escape}">
                    <input type="hidden" name="find" value="{$find|escape}">
                </form>
                <ul>
                    {foreach $printnamestructures as $page_ref_id => $structureName}
                        <li>
                            {$structureName|escape}
                            <button type="submit" form="removeStructureForm" name="removestructure" value="{$page_ref_id|escape}" class="btn btn-link btn-sm" title="{tr}Remove{/tr}">
                                {icon name='remove'}
                            </button>
                        </li>
                    {/foreach}
                </ul>
                {if $hasExcludedStructurePages}
                    {remarksbox type="warning" title="{tr}Warning{/tr}"}{tr}Some pages of the selected structures will not be included because you do not have permission to view or print them.{/tr}{/remarksbox}
                {/if}
                <form method="get" action="tiki-print_multi_pages.php">
                    {ticket}
                    <input type="hidden" name="printstructures" value="{$form_printstructures|escape}">
                    <input type="hidden" name="find" value="{$find|escape}">
                    <input type="submit" class="btn btn-primary btn-sm" name="print" value="{tr}Print{/tr}">
                </form>

                {if $pdf_export eq 'y'}
                    <form method="get" action="tiki-print_multi_pages.php">
                        {ticket}
                        <input type="hidden" name="printstructures" value="{$form_printstructures|escape}">
                        <input type="hidden" name="find" value="{$find|escape}">
                        <input type="hidden" name="display" value="pdf">
                        <input type="submit" class="btn btn-primary btn-sm" name="print" value="{tr}PDF{/tr}">
                    </form>
                {/if}

                <form action="tiki-print_pages.php" method="post">
                    {ticket}
                    <input type="submit" class="btn btn-primary btn-sm" name="clearstructures" value="{tr}Clear{/tr}">
                </form>
            {/if}

            <h2>{tr}Add Structure{/tr}</h2>
            <form action="tiki-print_pages.php" method="post">
                {ticket}
                <input type="hidden" name="printstructures" value="{$form_printstructures|escape}">
                <input type="hidden" name="find" value="{$find|escape}">
                <input type="hidden" name="addstructure" value="y">
                <div class="mb-3 row">
                    <div class="col-sm-12">
                        <select name="structureId" size="5" onchange="this.form.submit()" class="form-control">
                            {foreach $availableStructures as $structure}
                                <option value="{$structure.page_ref_id|escape}">{$structure.pageName|escape}</option>
                            {foreachelse}
                                <option value="" disabled="disabled">{if $structures}{tr}All structures selected{/tr}{else}{tr}No structures{/tr}{/if}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
            </form>
        {/tab}
    {/if}

    {tab name="{tr}Pages{/tr}"}
    {* --- tab with pages -- *}
        {if $prefs.feature_help eq 'y' and $prefs.feature_elementplus neq 'y'}
            {remarksbox type="tip" title="{tr}Tip{/tr}"}{tr}Use Ctrl+Click to select multiple options{/tr}{/remarksbox}
        {/if}

        {if $prefs.feature_elementplus eq 'y'}
            {jstransfer_list data=$transferPages defaultSelected=$printpages fieldName="transfer_printpages" filterable=true ordering=true sourceListTitle="Available Pages" targetListTitle="Selected Pages"}
            <div class="mt-3 d-flex gap-2">
                <form method="get" action="tiki-print_multi_pages.php">
                    {ticket}
                    <input type="hidden" class="js-printpages" name="printpages" value="{$form_printpages|escape}">
                    <input type="submit" class="btn btn-primary btn-sm" name="print" value="{tr}Print{/tr}">
                </form>
                {if $pdf_export eq 'y'}
                    <form method="get" action="tiki-print_multi_pages.php">
                        {ticket}
                        <input type="hidden" name="display" value="pdf">
                        <input type="hidden" class="js-printpages" name="printpages" value="{$form_printpages|escape}">
                        <input type="submit" class="btn btn-primary btn-sm" name="print" value="{tr}PDF{/tr}">
                    </form>
                {/if}
            </div>
            {jq}
                document.querySelectorAll('el-transfer[field-name="transfer_printpages"]').forEach(function (transfer) {
                    transfer.addEventListener('transfer-change', function (event) {
                        $('input.js-printpages').val(encodeURIComponent(JSON.stringify(event.detail[0].value)));
                    });
                });
            {/jq}
        {else}
        <form action="tiki-print_pages.php" method="post">
            {ticket}
            <input type="hidden" name="printpages" value="{$form_printpages|escape}">
            <input type="hidden" name="find" value="{$find|escape}">
                <table style="width:100%">
                    <tr>
                        <td width="40%">
                            <h2>{tr}Available Pages:{/tr}</h2>
                            <select name="pageName[]" multiple="multiple" size="15" style="width:99%" title="{tr}Available Pages{/tr}" class="form-control">
                                {section name=ix loop=$pages}
                                    {if !in_array($pages[ix].pageName,$printpages)}{* don't show the page as available,if it is already selected *}
                                        <option value="{$pages[ix].pageName|escape}">{$pages[ix].pageName|escape}</option>
                                    {/if}
                                {sectionelse}
                                    <option value="" disabled="disabled">{tr}No pages{/tr}</option>
                                {/section}
                                {if $pages|@count eq $printpages|@count}
                                    <option value="" disabled="">{tr}All pages selected{/tr}</option>
                                {/if}
                            </select>
                        </td>
                        <td style="vertical-align:middle; text-align:center;" width="20%">
                            <div class="mx-auto">
                                {if $pages}
                                    <p><input type="submit" class="btn btn-primary btn-sm" name="addpage" title="{tr}Add Page{/tr}" value="{tr}Add Page{/tr} &gt;"></p>
                                {/if}
                                {if $printpages}
                                    <p><input type="submit" class="btn btn-warning btn-sm" name="removepage" title="{tr}Remove Page{/tr}" value="&lt; {tr}Remove Page{/tr}"></p>
                                    <p><input type="submit" class="btn btn-primary btn-sm" name="clearpages" title="{tr}Clear{/tr}" value="{tr}Clear{/tr}"></p>
                                {/if}
                            </div>

                        </td>
                        <td width="40%">
                            <h2>{tr}Selected Pages:{/tr}</h2>
                            <select name="selectedpages[]" size="15" multiple="multiple" style="width:99%" title="{tr}Selected Pages{/tr}" class="form-control">
                                {section name=ix loop=$printpages}
                                    <option value="{$smarty.section.ix.index}">{$printpages[ix]}</option>
                                {sectionelse}
                                    <option value="">{tr}No pages selected.{/tr}</option>
                                {/section}
                            </select>
                        </td>
                    </tr>
                </table>
        </form>

        {if $printpages}
            <div style="float:right;margin-right:20%;">
                <form method="get" action="tiki-print_multi_pages.php">
                    {ticket}
                    <input type="hidden" name="printpages" value="{$form_printpages|escape}">
                    <input type="submit" class="btn btn-primary btn-sm mt-2" name="print" title="{tr}Print{/tr}" value="{tr}Print{/tr}">
                </form>
                {if $pdf_export eq 'y'}
                    <form method="get" action="tiki-print_multi_pages.php">
                        {ticket}
                        <input type="hidden" name="display" value="pdf">
                        <input type="hidden" name="printpages" value="{$form_printpages|escape}">
                        <input type="submit" class="btn btn-primary btn-sm mt-2" name="print" title="{tr}PDF{/tr}" value="{tr}PDF{/tr}">
                    </form>
                {/if}
            </div>
        {/if}
        {/if}
        {if $prefs.feature_wiki_structure eq 'y'}
            <form action="tiki-print_pages.php" method="post">
                {ticket}
                <input type="hidden" class="js-printpages" name="printpages" value="{$form_printpages|escape}">
                <input type="hidden" name="find" value="{$find|escape}">
                <h2>{tr}Add Pages from Structures:{/tr}</h2>
                <div class="mb-3 row">
                    <div class="col-sm-12">
                        <select name="structureId" size="5" style="width:99%" border="1" class="form-control" required>
                            {section name=ix loop=$structures}
                                {if !in_array($structures[ix].page_ref_id,$printstructures)}
                                    <option value="{$structures[ix].page_ref_id|escape}">{$structures[ix].pageName}</option>
                                {/if}
                            {sectionelse}
                                <option value="" disabled="disabled">{tr}No structures{/tr}</option>
                            {/section}
                        </select>
                    </div>
                </div>
                <div class="mb-3 row">
                    <div class="col-sm-12">
                        <input type="submit" class="btn btn-primary btn-sm" name="addstructurepages" value="{tr}Add Pages from Structures{/tr}">
                    </div>
                </div>
            </form>
        {/if}
    {/tab}

{/tabset}
