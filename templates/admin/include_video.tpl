<form action="tiki-admin.php?page=video" method="post">
    {ticket}

    {tabset name="admin_video"}

        {tab name="{tr}Kaltura{/tr}"}
            <br>
            {remarksbox type="info" title="{tr}Kaltura Registration{/tr}"}
                {tr}To get a Kaltura Partner ID:{/tr} {tr}Setup your own instance of Kaltura Community Edition (CE){/tr} or <a href="http://corp.kaltura.com/about/signup" class="alert-link">{tr}get an account via Kaltura.com{/tr}</a>
            {/remarksbox}

            {button _text="{tr}List Media{/tr}" href="tiki-list_kaltura_entries.php"}
            {if $kaltura_legacyremix eq 'y'}{button _text="{tr}List Remix Entries{/tr}" href="tiki-list_kaltura_entries.php?list=mix"}{/if}

            <div class="row">
                <div class="mb-3 col-lg-12 clearfix">
                    {include file='admin/include_apply_top.tpl'}
                </div>
            </div>

            <fieldset>
                <legend class="h3">{tr}Activate the feature{/tr}</legend>
                {preference name=feature_kaltura visible="always"}
            </fieldset>

            <fieldset>
                <legend class="h3">{tr}Plugin to embed in pages{/tr}</legend>
                {preference name=wikiplugin_kaltura}
            </fieldset>

            <fieldset>
                <legend class="h3">{tr}Enable related tracker field types{/tr}</legend>
                {preference name=trackerfield_kaltura}
            </fieldset>

            <fieldset>
                <legend class="h3">{tr}Kaltura / Tiki config{/tr}</legend>
                {preference name=kaltura_kServiceUrl}
            </fieldset>

            <fieldset>
                <legend class="h3">{tr}Kaltura partner settings{/tr}</legend>
                {preference name=kaltura_partnerId}
                {preference name=kaltura_adminSecret}
                {preference name=kaltura_secret}
            </fieldset>

            <br>

            <fieldset>
                <legend class="h3">{tr}Kaltura dynamic player{/tr}</legend>
                {preference name=kaltura_kdpUIConf}
                {preference name=kaltura_kdpEditUIConf}
                {$kplayerlist}
            </fieldset>

            <br>

            <fieldset>
                <legend class="h3">{tr}Legacy support{/tr}</legend>
                {preference name=kaltura_legacyremix}
            </fieldset>

            <br>
        {/tab}
        
        {tab name="{tr}PeerTube{/tr}"}
            <br>
            {remarksbox type="info" title="{tr}PeerTube Registration{/tr}"}
                {tr}To get a PeerTube account, set up your own instance or register at a PeerTube server.{/tr}
            {/remarksbox}
            {button _text="{tr}List Videos{/tr}" href="tiki-list_peertube_entries.php"}
            <div class="row">
                <div class="mb-3 col-lg-12 clearfix">
                    {include file='admin/include_apply_top.tpl'}
                </div>
            </div>
            <fieldset>
                <legend class="h3">{tr}Activate the feature{/tr}</legend>
                {preference name=feature_peertube visible="always"}
            </fieldset>
            <br>
            <fieldset>
                <legend class="h3">{tr}Plugin to embed in pages{/tr}</legend>
                {preference name=wikiplugin_peertube}
            </fieldset>
            <br>
            <fieldset>
                <legend class="h3">{tr}Enable related tracker field types{/tr}</legend>
                {preference name=trackerfield_peertube}
            </fieldset>
            <br>
            <fieldset>
                <legend class="h3">{tr}PeerTube settings{/tr}</legend>
                {remarksbox type="tip" title="{tr}Tip{/tr}"}
                    {tr}Ensure the PeerTube service URL and credentials are correct. Test the connection after saving.{/tr}
                {/remarksbox}
                {preference name=peertube_service_url}
                {preference name=peertube_client_id}
                {preference name=peertube_client_secret}
                {preference name=peertube_username}
                {preference name=peertube_password}
                {preference name=peertube_player_id}
                <div class="col-sm-12">
                    {remarksbox type="note" title="{tr}Note{/tr}"}
                        {tr}The PeerTube player ID is optional and only needed for custom player configurations.{/tr}
                    {/remarksbox}
                </div>
            </fieldset>
            <br>
        {/tab}
    {/tabset}
    {include file='admin/include_apply_bottom.tpl'}
</form>
