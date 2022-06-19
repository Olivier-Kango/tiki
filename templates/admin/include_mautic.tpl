{* $Id$ *}
<form class="admin" method="post" action="tiki-admin.php?page=mautic" role="form" class="form">
    {ticket}
    <div class="t_navbar mb-4 clearfix">
        {include file='admin/include_apply_top.tpl'}
    </div>
    <fieldset>
        <legend>{tr}Main Settings{/tr}</legend>
        <div class="adminoptionbox clearfix">
            <div class="adminoption form-group row">
                <label for="site_mautic_url_paths" class="col-form-label col-md-4">
                    {tr}Mautic URL{/tr}
                </label>
                <div class="col-md-8">
                    <input type="text" class="form-control" id="site_mautic_url" name="site_mautic_url" value="{$prefs.site_mautic_url|escape}" />
                    <span class="form-text">
                        {tr}Put here the Mautic URL{/tr}
                    </span>
                </div>
            </div>
        </div>
        {preference name=site_mautic_tracking_script_location}
        {preference name=site_mautic_tracking_image}
        {preference name=site_mautic_logged_users}
    </fieldset>
    <fieldset>
        <legend>{tr}Credentials{/tr}</legend>
        <div class="adminoptionbox clearfix">
            <div class="adminoption form-group row">
                <label for="site_mautic_username" class="col-form-label col-md-4">
                    {tr}Username{/tr}
                </label>
                <div class="col-md-8">
                    <input type="text" class="form-control" id="site_mautic_username" name="site_mautic_username" value="{$prefs.site_mautic_username|escape}" />
                    <span class="form-text">
                        {tr}Put mautic username here{/tr}
                    </span>
                </div>
            </div>
            <div class="adminoption form-group row">
                <label for="site_mautic_password" class="col-form-label col-md-4">
                    {tr}Password{/tr}
                </label>
                <div class="col-md-8">
                    <input type="password" class="form-control" id="c" name="site_mautic_password" value="{$prefs.site_mautic_password|escape}" />
                    <span class="form-text">
                        {tr}Put mautic password here{/tr}
                    </span>
                </div>
            </div>
        </div>
    </fieldset>
    {include file='admin/include_apply_bottom.tpl'}
</form>