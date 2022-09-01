{* $Id$ *}
<form class="admin" method="post" action="tiki-admin.php?page=mautic" role="form" class="form">
    {ticket}
    <div class="t_navbar mb-4 clearfix">
        {include file='admin/include_apply_top.tpl'}
    </div>
    <fieldset>
        <legend>{tr}Main Settings{/tr}</legend>
        {preference name=site_mautic_url}
        {preference name=site_mautic_tracking_script_location}
        {preference name=site_mautic_tracking_image}
        {preference name=site_mautic_logged_users}
    </fieldset>
    <fieldset>
        <legend>{tr}Credentials{/tr}</legend>
        {preference name=site_mautic_username}
        {preference name=site_mautic_password}
    </fieldset>
    {include file='admin/include_apply_bottom.tpl'}
</form>