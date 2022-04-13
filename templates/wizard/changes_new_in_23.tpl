{* $Id$ *}

<div class="d-flex">
    <div class="me-4">
            <span class="float-start fa-stack fa-lg margin-right-18em" alt="{tr}Changes Wizard{/tr}" title="Changes Wizard">
            <i class="fas fa-arrow-circle-up fa-stack-2x"></i>
            <i class="fas fa-flip-horizontal fa-magic fa-stack-1x ms-4 mt-4"></i>
            </span>
    </div>
    <br/><br/><br/>
    <div class="flex-grow-1 ms-3">
        {tr}Main new and improved features and settings in Tiki 23.{/tr}
        <a href="https://doc.tiki.org/Tiki23" target="tikihelp" class="tikihelp text-info" title="{tr}Tiki23:{/tr}
            {tr}It is a Standard Term Support (STS) version.{/tr}
            {tr}It will be supported until Tiki 24.1 is released.{/tr}
            {tr}Some internal libraries and optional external packages have been upgraded or replaced by more updated ones.{/tr}
            <br/><br/>
            {tr}Click to read more{/tr}
        ">
            {icon name="help" size=1}
        </a>
        <fieldset class="mb-3 w-100 clearfix featurelist">
            <legend>{tr}New Features{/tr}</legend>
            {preference name='feature_machine_learning'}
            {preference name='feature_socialnetworks'}
            {preference name='theme_unified_admin_backend'}
            <fieldset class="mb-3 w-100 clearfix featurelist">
                <legend>{tr}New Wiki Plugins{/tr}</legend>
                {preference name=wikiplugin_autotoc}
                {preference name=wikiplugin_qr}
                {preference name=wikiplugin_signature}
            </fieldset>
        </fieldset>
        <fieldset class="mb-3 w-100 clearfix featurelist">
            <legend>{tr}Improved Plugins{/tr}</legend>
            {preference name=wikiplugin_diagram}
            {preference name=wikiplugin_listexecute}
        </fieldset>
        <fieldset class="mb-3 w-100 clearfix featurelist">
            <legend>{tr}Improved Menu System{/tr}</legend>
            {preference name=jquery_smartmenus_enable}
            {preference name=jquery_smartmenus_collapsible_behavior}
            {preference name=jquery_smartmenus_open_close_click}
        </fieldset>
        <fieldset class="mb-3 w-100 clearfix featurelist">
            <legend>{tr}Web monetization{/tr}</legend>
            {preference name=webmonetization_enabled}
            {preference name=webmonetization_all_website}
            {preference name=webmonetization_always_default}
            {preference name=webmonetization_default_payment_pointer}
            {preference name=webmonetization_default_paywall_text}
        </fieldset>
        <fieldset class="mb-3 w-100 clearfix featurelist">
            <legend>{tr}Other Extended Features{/tr}</legend>
            {preference name=feature_notify_users_mention}
            {preference name=ajax_edit_previews}
            {preference name=fgal_use_record_rtc_screen}
            {preference name=scheduler_delay}
            <div class="adminoption mb-3 row">
                <label class="col-sm-3 col-form-label"><b>{tr}Trackers{/tr}</b>:</label>
                <div class="offset-sm-1 col-sm-11">
                    {tr}Email folders Tracker Field.{/tr}
                    <a href="https://doc.tiki.org/Email-folders-Tracker-Field">{tr}More Information{/tr}...</a><br/><br/>
                </div>
                <div class="offset-sm-1 col-sm-11">
                    {tr}Open Database Connectivity (ODBC) support added to Tracker Tabular.{/tr}
                    <a href="https://doc.tiki.org/ODBC">{tr}More Information{/tr}...</a><br/><br/>
                </div>
                <div class="offset-sm-1 col-sm-11">
                    {tr}System Trackers.{/tr}
                    <a href="https://doc.tiki.org/System-Trackers">{tr}More Information{/tr}...</a><br/><br/>
                </div>
            </div>
            <div class="adminoption mb-3 row">
                <label class="col-sm-3 col-form-label"><b>{tr}Others{/tr}</b>:</label>
                <div class="offset-sm-1 col-sm-11">
                    {tr}OpenID Connect has been added as an authentication layer on top of OAuth 2.0.{/tr}
                    <a href="https://doc.tiki.org/OpenID-Connect">{tr}More Information{/tr}...</a><br/><br/>
                </div>
                <div class="offset-sm-1 col-sm-11">
                    {tr}Three new themes have been added — Morph, Quartz, and Vapor.{/tr}
                    {tr}A new Unified Admin Backend UI / Theme was added for improved usability for new tiki site admins.{/tr}
                    <a href="https://doc.tiki.org/Tiki23#Themes">{tr}More Information{/tr}...</a><br/><br/>
                </div>
            </div>
        </fieldset>
        <i>{tr}And many more improvements{/tr}.
            {tr}See the full list of changes.{/tr}</i>
        <a href="https://doc.tiki.org/Tiki23" target="tikihelp" class="tikihelp" title="{tr}Tiki23:{/tr}
            {tr}Click to read more{/tr}
        ">
            {icon name="help" size=1}
        </a>
    </div>
</div>
