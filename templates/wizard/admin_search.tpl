<div class="d-flex">
    <div class="flex-shrink-0">
        <span class="fa-stack fa-lg" style="width: 100px;" title="Configuration Wizard">
            {icon name='admin_general' iclass='fa-stack-2x'}
            {icon name='magic' iclass='fa-flip-horizontal fa-stack-1x ms-4 mt-4'}
        </span>
    </div>
    <div class="flex-grow-1 ms-3">
        {icon name="admin_search" size=3 iclass="float-sm-end"}
        <div class="row">
            <div class="col-md-6">
                <fieldset>
                    <legend>{tr}Advanced Search{/tr}</legend>
                    {tr}Uses Unified Search Index with a specified search engine{/tr}.
                    {tr}Unified Search is required by a number of other features, e.g. the community friendship network{/tr}
                    {preference name=feature_search visible="always"}
                    <div class="adminoptionboxchild" id="feature_search_childcontainer">
                        {preference name="unified_incremental_update"}
                        {preference name="unified_engine"}
                        <div class="adminoptionboxchild unified_engine_childcontainer elastic">
                            {preference name="unified_elastic_url"}
                            {preference name="unified_elastic_index_prefix"}
                            {preference name="unified_elastic_index_current"}
                        </div>
                        <div class="adminoptionboxchild unified_engine_childcontainer mysql">
                            {preference name="unified_mysql_short_field_names"}
                        </div>
                        {preference name="unified_search_default_operator"}
                    </div>
                </fieldset>
            </div>
            <div class="col-md-6">
                <fieldset>
                    <legend>{tr}Other settings{/tr}</legend>
                    {preference name=search_default_interface_language}
                    {preference name=search_default_where}
                    {if $prefs.feature_file_galleries eq 'y'}<br>
                        <em>{tr}Also see the Search Indexing tab here:{/tr} <a class='rbox-link' target='tikihelp' href='tiki-admin.php?page=fgal'>{tr}File Gallery admin panel{/tr}</a></em>
                    {/if}
                </fieldset>
            </div>
        </div>
        <em>{tr}See also{/tr} <a href="tiki-admin.php?page=search&amp;cookietab=1" target="_blank">{tr}Search admin card{/tr}</a> & <a href="https://doc.tiki.org/Search" target="_blank">{tr}Search in doc.tiki.org{/tr}</a></em>
    </div>
</div>
