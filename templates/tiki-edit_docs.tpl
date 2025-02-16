{title help="Docs"}{$name}{/title}

{if $missingPackage}
    {remarksbox type=error title="{tr}Missing Package{/tr}" close="n"}
        {tr}To view/edit ODT documents Tiki needs bower-asset/wodo.texteditor package.{/tr}
        {tr}Please contact the Administrator to install it.{/tr}
    {/remarksbox}
{else}
    <span class="editState" {if $edit eq "false"} style="display: none;" {/if}>
        {button _class="saveButton" _text="{tr}Save{/tr}" _htmlelement="role_main" fileId="$fileId" _title="{tr}Tiki Docs{/tr} | {tr}Save file{/tr}"}
        {button _class="cancelButton" _text="{tr}Cancel{/tr}" _htmlelement="role_main" fileId="$fileId" _title="{tr}Tiki Docs{/tr} | {tr}Cancel editing file{/tr}"}
    </span>

    <span class="viewState" {if $edit eq "true"} style="display: none;" {/if}>
        {button _id="editButton" _class="editButton" _text="{tr}Edit{/tr}" _template="tiki-edit_docs.tpl" edit="edit" _auto_args="*" _htmlelement="role_main" _title="{tr}Tiki Docs{/tr} | {tr}Editing file{/tr}"}
    </span>

    <input id="fileId" type="hidden" value="{$fileId}">

    <div id="tiki_doc" class="" style="min-height: 800px">
    </div>
{/if}
