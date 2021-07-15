{* $Id$ *}

<div class="media">
    <div class="mr-4">
            <span class="float-left fa-stack fa-lg margin-right-18em" alt="{tr}Changes Wizard{/tr}" title="Changes Wizard">
            <i class="fas fa-arrow-circle-up fa-stack-2x"></i>
            <i class="fas fa-flip-horizontal fa-magic fa-stack-1x ml-4 mt-4"></i>
            </span>
    </div>
    <br/><br/><br/>
    <div class="media-body">
        {tr}Main new and improved features and settings in Tiki 22.{/tr}
        <a href="https://doc.tiki.org/Tiki24" target="tikihelp" class="tikihelp text-info" title="{tr}Tiki24:{/tr}
            {tr}It is a Long Term Support (LTS) version.{/tr}
            {tr}As it is a Long-Term Support (LTS) version, it will be supported for 5 years.{/tr}
            {tr}Some internal libraries and optional external packages have been upgraded or replaced by more updated ones.{/tr}
            <br/><br/>
            {tr}Click to read more{/tr}
        ">
            {icon name="help" size=1}
        </a>
        <fieldset class="mb-3 w-100 clearfix featurelist">
            <legend>{tr}New Features{/tr}</legend>
            {preference name='foo1'}
            {preference name='foo12'}
            <fieldset class="mb-3 w-100 clearfix featurelist">
                <legend>{tr}New Wiki Plugins{/tr}</legend>
                {preference name=wikiplugin_bar1}
            </fieldset>
        </fieldset>
        <fieldset class="mb-3 w-100 clearfix featurelist">
            <legend>{tr}Improved Plugins{/tr}</legend>
            {preference name=wikiplugin_bar2}
        </fieldset>
        <fieldset class="mb-3 w-100 clearfix featurelist">
            <legend>{tr}Other Extended Features{/tr}</legend>
            <div class="adminoption form-group row">
                <label class="col-sm-3 col-form-label"><b>{tr}Foobar{/tr}</b>:</label>
                <div class="offset-sm-1 col-sm-11">
                    {tr}FooBar.{/tr}
                    <a href="https://doc.tiki.org/Tiki24#FooBar">{tr}More Information{/tr}...</a><br/><br/>
                </div>
            </div>
        </fieldset>
        <i>{tr}And many more improvements{/tr}.
            {tr}See the full list of changes.{/tr}</i>
        <a href="https://doc.tiki.org/Tiki24" target="tikihelp" class="tikihelp" title="{tr}Tiki24:{/tr}
            {tr}Click to read more{/tr}
        ">
            {icon name="help" size=1}
        </a>
    </div>
</div>
