<form action="tiki-admin.php?page=maps" method="post" class="admin">
    {ticket}

    <div class="row">
        <div class="mb-3 col-lg-12 clearfix">
            {include file='admin/include_apply_top.tpl'}
        </div>
    </div>

    <fieldset>
        <legend class="h3">{tr}Settings{/tr}</legend>

        {preference name=geo_enabled visible="always"}
        {preference name=mapzone}

        {if $prefs.geo_enabled eq 'y'}

            {preference name=geo_tilesets}
            {preference name=geo_google_streetview}
            <div class="adminoptionboxchild" id="geo_google_streetview_childcontainer">
                {preference name=geo_google_streetview_overlay}
            </div>

            {preference name=geo_locate_blogpost}
            {preference name=geo_locate_wiki}
            {preference name=gmap_page_list}
            {preference name=geo_locate_article}
            {preference name=gmap_article_list}
            {preference name=wikiplugin_map}
            {preference name=trackerfield_location}

            {preference name=gmap_key}
            {preference name=geo_bingmaps_key}
            {preference name=geo_nextzen_key}
            {preference name=geo_zoomlevel_to_found_location}

        {/if}

        {preference name=gdaltindex}
        {preference name=ogr2ogr}

    </fieldset>

    <fieldset class="admin">
        <legend class="h3">{tr}Defaults{/tr}</legend>
        {preference name=gmap_defaultx}
        {preference name=gmap_defaulty}
        {preference name=gmap_defaultz}
        {preference name=default_map}
    </fieldset>

    {include file='admin/include_apply_bottom.tpl'}
</form>
