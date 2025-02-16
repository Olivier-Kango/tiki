{title help="Integrator"}{tr}Integrator{/tr}{/title}

{remarksbox type="tip" title="{tr}Tip{/tr}"}
    {tr}An easier way to integrate content from another site into Tiki is via iframed links using Tiki's <a class="alert-link" href="tiki-admin_links.php">featured links</a> feature.{/tr}
{/remarksbox}

{if $repID > 0}
    <h2>{tr}Edit Repository:{/tr} {$name}</h2>
{else}
    <h2>{tr}Create New Repository{/tr}</h2>
{/if}

<div class="t_navbar btn-group mb-3">
    {button href="tiki-list_integrator_repositories.php" class="btn btn-info" _icon_name="list" _text="{tr}List{/tr}"}
    {button href="tiki-admin_integrator.php" class="btn btn-primary" _icon_name="create" _text="{tr}New{/tr}"}
    {if isset($repID) and $repID ne '0'}
        {assign var=thisrepID value=$repID|escape}
        {button href="tiki-integrator.php?repID=$thisrepID" class="btn btn-primary" _icon_name="view" _text="{tr}View{/tr}"}
    {/if}
</div>


{* Add form *}
<form action="tiki-admin_integrator.php" method="post">
    <input type="hidden" name="repID" value="{$repID|escape}">
    <div class="tiki-form-group row">
        <label class="col-sm-3 col-form-label" title="Human-readable repository name">{tr}Name{/tr}</label>
        <div class="col-sm-7 ">
            <input type="text" name="name" value="{$name|escape}" title="{tr}Human-readable repository name{/tr}" class="form-control">
        </div>
    </div>
    <div class="tiki-form-group row">
        <label class="col-sm-3 col-form-label" title="{tr}Path to repository (local filesystem: relative/absolute web root, remote: prefixed with 'http://'){/tr}">{tr}Path{/tr}</label>
        <div class="col-sm-7">
            <input type="text" name="path" value="{$path|escape}" title="{tr}Path to repository (local filesystem: relative/absolute web root, remote: prefixed with 'http://'){/tr}" class="form-control">
        </div>
    </div>
    <div class="tiki-form-group row">
        <label class="col-sm-3 col-form-label" title="{tr}File name of start page{/tr}">{tr}Start page{/tr}</label>
        <div class="col-sm-7">
            <input type="text" name="start" value="{$start|escape}" title="{tr}File name of start page{/tr}" class="form-control">
        </div>
    </div>
    <div class="tiki-form-group row">
        <label class="col-sm-3 col-form-label" title="{tr}CSS file to load when browse this repository{/tr}">{tr}CSS File{/tr}</label>
        <div class="col-sm-7">
            <input type="text" name="cssfile" value="{$cssfile|escape}" title="{tr}CSS file to load when browse this repository{/tr}" class="form-control">
        </div>
    </div>
    <div class="tiki-form-group row">
        <label class="col-sm-3 col-form-label" title="{tr}Is repository visible to users{/tr}">{tr}Visible{/tr}</label>
        <div class="col-sm-7">
            <input type="checkbox" name="vis" {if $vis eq 'y'}checked="checked"{/if} title="{tr}Is repository visible to users{/tr}">
        </div>
    </div>
    <div class="tiki-form-group row">
        <label class="col-sm-3 col-form-label" title="{tr}Can files from repository be cached{/tr}">{tr}Cacheable{/tr}</label>
        <div class="col-sm-7">
            <input type="checkbox" name="cacheable" {if $cacheable eq 'y'}checked="checked"{/if} title="{tr}Can files from repository be cached{/tr}">
                {if isset($repID) and $repID ne '0'}
                    &nbsp;&nbsp;
                    <a href="tiki-admin_integrator.php?action=clear&amp;repID={$repID|escape}" title="{tr}Clear all cached pages of this repository{/tr}">
                        {tr}Clear cache{/tr}
                    </a>
                {/if}
        </div>
    </div>
    <div class="tiki-form-group row">
        <label class="col-sm-3 col-form-label" title="{tr}Seconds count 'till cached page will be expired{/tr}">{tr}Cache expiration{/tr}</label>
        <div class="col-sm-7">
            <input type="number" min="0" name="expiration" value="{$expiration|escape}" title="{tr}Seconds count 'till cached page will be expired{/tr}"
            class="form-control">
        </div>
    </div>
    <div class="tiki-form-group row">
        <label class="col-sm-3 col-form-label" title="{tr}Human-readable text description of repository{/tr}">{tr}Description{/tr}</label>
        <div class="col-sm-7">
            <textarea name="description" rows="4" title="{tr}Human-readable text description of repository{/tr}" class="form-control">{$description|escape}</textarea>
        </div>
    </div>
    <div class="tiki-form-group row">
        <div class="col-sm-7 offset-sm-3 text-center">
            <input type="submit" class="btn btn-primary" name="save" value="{tr}Save{/tr}">
        </div>
    </div>
</form>

<h2>{tr}Available Repositories{/tr}</h2>

{* Table with list of repositories *}
<div class="{if $js}table-responsive{/if}"> {* table-responsive class cuts off css drop-down menus *}
    <table class="table table-striped table-hover" id="integrator-repositories">
        <tr>
            <th rowspan="2">{tr}Name{/tr}</th>
            <th>{tr}Path{/tr}</th>
            <th>{tr}Start{/tr}</th>
            <th>{tr}CSS File{/tr}</th>
            <th></th>
        </tr><tr>
            <th colspan="4">{tr}Description{/tr}</th>
        </tr>

        {section name=rep loop=$repositories}
            <tr>
                <td class="text"{if (strlen($repositories[rep].description) > 0)} rowspan="2"{/if}>
                    <a href="tiki-admin_integrator_rules.php?repID={$repositories[rep].repID|escape}" title="{tr}Edit rules{/tr}">
                        {$repositories[rep].name}
                    </a>
                </td>
                <td class="text">{($repositories[rep].path)? $repositories[rep].path:'Empty'}</td>
                <td class="text">{($repositories[rep].start_page)? $repositories[rep].start_page:'Empty'}</td>
                <td class="text">{($repositories[rep].css_file)? $repositories[rep].css_file:'Empty'}</td>
                <td class="action">
                    {actions}
                        {strip}
                            <action>
                                <a href="tiki-admin_integrator.php?action=edit&amp;repID={$repositories[rep].repID|escape}">
                                    {icon name='edit' _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
                                </a>
                            </action>
                            <action>
                                <form action="tiki-admin_integrator.php" method="post">
                                    {ticket}
                                    <input type="hidden" name="action" value="rm">
                                    <input type="hidden" name="repID" value="{$repositories[rep].repID|escape}">
                                    <button type="submit" class="btn btn-link text-danger px-0 pt-0 pb-0" onclick="confirmPopup('{tr}Are you sure you want to delete this repository ?{/tr}')">
                                        {icon name='remove' _menu_text='y' _menu_icon='y' alt="{tr}Remove{/tr}"}
                                    </button>
                                </form>
                            </action>
                        {/strip}
                    {/actions}
                </td>

                {* Show description as colspaned row if it is not an empty *}
                {if (strlen($repositories[rep].description) > 0)}
                    </tr><tr>
                        <td class="text" colspan="4">{$repositories[rep].description}</td>
                {/if}
            </tr>
        {/section}
    </table>
</div>
