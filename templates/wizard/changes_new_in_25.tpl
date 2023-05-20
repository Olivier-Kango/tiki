<div class="d-flex">
    <div class="me-4">
            <span class="float-start fa-stack fa-lg margin-right-18em" alt="{tr}Changes Wizard{/tr}" title="Changes Wizard">
            <i class="fas fa-arrow-circle-up fa-stack-2x"></i>
            <i class="fas fa-flip-horizontal fa-magic fa-stack-1x ms-4 mt-4"></i>
            </span>
    </div>
    <br/><br/><br/>
    <div class="flex-grow-1 ms-3">
        {tr}Main new and improved features and settings in Tiki 25.{/tr}
        <a href="https://doc.tiki.org/Tiki25" target="tikihelp" class="tikihelp text-info" title="{tr}Tiki25:{/tr}
            {tr}It is a Standard Term Support (STS) version.{/tr}
            {tr}It will be supported until Tiki 26.1 is released.{/tr}
            {tr}Some internal libraries and optional external packages have been upgraded or replaced by more updated ones.{/tr}
        <br/><br/>
            {tr}Click to read more{/tr}
        ">
            {icon name="help" size=1}
        </a>
        <fieldset class="mb-3 w-100 clearfix featurelist">
            <legend>{tr}New Features{/tr}</legend>
            {preference name=email_detect_disposable}
            {preference name=tracker_system_bounces}
            <div class="adminoptionboxchild" id="tracker_system_bounces_childcontainer">
                {preference name=tracker_system_bounces_tracker}
                {preference name=tracker_system_bounces_mailbox}
                {preference name=tracker_system_bounces_emailfolder}
                {preference name=tracker_system_bounces_soft_total}
                {preference name=tracker_system_bounces_hard_total}
                {preference name=tracker_system_bounces_blacklisted}
            </div>
            <fieldset class="mb-3 w-100 clearfix featurelist">
                <legend>{tr}New Wiki Plugins{/tr}</legend>
                {preference name=wikiplugin_accordion}
                {preference name=wikiplugin_countup}
            </fieldset>
        </fieldset>
        <fieldset class="mb-3 w-100 clearfix featurelist">
            <legend>{tr}Improved Plugins{/tr}</legend>
            {preference name=wikiplugin_customsearch}
            {preference name=wikiplugin_list}
            {preference name=wikiplugin_listexecute}
        </fieldset>
        <fieldset class="mb-3 w-100 clearfix featurelist">
            <legend>{tr}Other Extended Features{/tr}</legend>
            <div class="adminoption mb-3 row">
                <div class="offset-sm-0 col-sm-12">
                    {tr}Email filters{/tr}
                    <a href="https://doc.tiki.org/Email-filters">{tr}More Information{/tr}...</a><br/><br/>
                </div>
                <div class="offset-sm-0 col-sm-12">
                    {tr}Federated timesheets{/tr}
                    <a href="https://doc.tiki.org/Federated-timesheets">{tr}More Information{/tr}...</a><br/><br/>
                </div>
                <div class="offset-sm-0 col-sm-12">
                    {tr}File gallery: Direct mapping allowed{/tr}
                    <a href="https://doc.tiki.org/File-Gallery---Direct-Mapping">{tr}More Information{/tr}...</a><br/><br/>
                </div>
                <div class="offset-sm-0 col-sm-12">
                    {tr}Gitpod for Tiki cloud-based development{/tr}
                    <a href="https://dev.tiki.org/Gitpod">{tr}More Information{/tr}...</a><br/><br/>
                </div>
                <div class="offset-sm-0 col-sm-12">
                    {tr}New themes have been added — Boosted and Zephyr.{/tr}
                    <a href="https://doc.tiki.org/Tiki25#Themes_and_CSS_variables">{tr}More Information{/tr}...</a><br/><br/>
                </div>
            </div>
        </fieldset>
        <i>{tr}And many more improvements{/tr}.
            {tr}See the full list of changes.{/tr}</i>
        <a href="https://doc.tiki.org/Tiki25" target="tikihelp" class="tikihelp" title="{tr}Tiki25:{/tr}
            {tr}Click to read more{/tr}
        ">
            {icon name="help" size=1}
        </a>
    </div>
</div>
