{title admpage="wiki" help="Using Wiki Pages#Listing_Pages"}{tr}Pages{/tr}{/title}

{tabset name='tabs_wikipages'}
    {tab name="{tr}List Wiki Pages{/tr}"}
        <h2>{tr}List Wiki Pages{/tr}</h2>
        {if !$ts.enabled}
            <div class="clearfix">
                {include autocomplete='pagename' file='find.tpl' find_show_languages='y' find_show_languages_excluded='y' find_show_categories_multi='y' find_show_num_rows='y' find_in="{tr}Page name{/tr}" }
            </div>
        {else}
            {include file='find.tpl' map_only='y'}
        {/if}
        <form name="checkform" method="get">
            <input type="hidden" name="offset" value="{$offset|escape}">
            <input type="hidden" name="sort_mode" value="{$sort_mode|escape}">
            <input type="hidden" name="find" value="{$find|escape}">
            <input type="hidden" name="maxRecords" value="{$maxRecords|escape}">
        </form>
        {if isset($mapview) and $mapview}
            {wikiplugin _name="map" scope=".listpagesmap .geolocated" width="400" height="400"}{/wikiplugin}
        {/if}
        <div id="tiki-listpages-content">
            {if $aliases}
                <div class="aliases">
                    <strong>{tr}Page aliases found:{/tr}</strong>
                    <ul>
                    {foreach from=$aliases item=alias}
                        <li>
                            <a href="{$alias.parsedAlias|sefurl}" title="{$alias.fromPage|escape}" class="alias">{$alias.toPage|escape}</a>
                        </li>
                    {/foreach}
                    </ul>
                </div>
            {/if}
            {include file='tiki-listpages_content.tpl' clean='n'}
        </div>
    {/tab}

    {if $tiki_p_edit == 'y'}
        {tab name="{tr}Create a Wiki Page{/tr}"}
            <h2>{tr}Create a Wiki Page{/tr}</h2><br>
            <div>
                <form method="get" action="tiki-editpage.php">
                    <div class="mb-3 row">
                        <label class="col-form-label col-sm-3" for="pagename">{tr}Name of new page{/tr}</label>
                        <div class="col-sm-9">
                            <input class="form-control" id="pagename" type="text" name="page">
                            {jq}
                                $("input[name=page]").on("keyup", function () {
                                    var length = $(this).val().length;
                                    if(length > 158) {
                                        alert("You have reached the number of characters allowed (158 max) for the page name field");
                                    }
                                });
                            {/jq}
                        </div>
                    </div>
                    {if $prefs.namespace_enabled == 'y' && $prefs.namespace_default}
                    <div class="mb-3 row">
                        <label class="col-sm-3 form-check-label">{tr _0=$prefs.namespace_default}Create page within %0{/tr}</label>
                        <div class="col-sm-9">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="namespace" value="{$prefs.namespace_default|escape}" checked="checked">
                            </div>
                        </div>
                    </div>
                    {/if}
                    <div class="mb-3 row">
                        <div class="col-form-label col-sm-3"></div>
                        <div class="col-sm-9">
                            <input class="btn btn-primary" type="submit" name="quickedit" value="{tr}Create Page{/tr}">
                        </div>
                    </div>

                </form>
            </div>
        {/tab}
    {/if}

{/tabset}
