{title help="Integrator"}{tr}Edit Rules for Repository:{/tr} {$name}{/title}

<div class="t_navbar mb-4">
    {button href="tiki-admin_integrator.php" class="btn btn-primary" _icon_name="cog" _text="{tr}Configure Repositories{/tr}"}
    {button href="tiki-list_integrator_repositories.php" class="btn btn-info" _icon_name="list" _text="{tr}List Repositories{/tr}"}
    {assign var=thisrepID value=$repID|escape}
    {button href="tiki-admin_integrator.php?action=edit&amp;repID=$thisrepID" class="btn btn-primary" _icon_name="wrench" _text="{tr}Configure this Repository{/tr}"}
    {button href="tiki-integrator.php?repID=$thisrepID" _text="{tr}View this Repository{/tr}" _icon_name="view" class="btn btn-primary"}
    {button href="tiki-admin_integrator_rules.php?repID=$thisrepID" _text="{tr}New Rule{/tr}" _icon_name="create" class="btn btn-primary"}
    {if count($reps) gt 0}
        {button _onclick="javascript:flip('rules-copy-panel');" _text="{tr}Copy Rules{/tr}" _icon_name="copy" _title="{tr}view/hide copy rules dialog{/tr}"}
    {/if}
</div>

{if count($reps) gt 0}
    <div id="rules-copy-panel">
        <form action="tiki-admin_integrator_rules.php?repID={$repID|escape}" method="post">
            <div class="mb-3 row">
                <label class="col-sm-3 col-form-label">{tr}Source repository{/tr}</label>
                <div class="col-sm-6 offset-sm-1">
                    <select name="srcrep" class="form-control">{html_options options=$reps}</select>
                </div>
                <div class="col-sm-1">
                    <input type="submit" class="btn btn-primary btn-sm" name="copy" value="{tr}Copy{/tr}">
                </div>
            </div>
        </form>
        <br><br>
    </div>
{/if}

{* Add form *}
<form action="tiki-admin_integrator_rules.php?repID={$repID|escape}" method="post">
    <input type="hidden" name="ruleID" value="{$ruleID|escape}">
    <input type="hidden" name="repID" value="{$repID|escape}">

    <div class="mb-3 row">
        <label class="col-sm-3 col-form-label" title="{tr}Rules will be applied in this order ('0' or empty = auto){/tr}">{tr}Rule order{/tr}</label>
        <div class="col-sm-7 offset-sm-1">
            <input type="text" maxlength="2" size="2" class="form-control" name="ord" value="{$ord|escape}" title="{tr}Rules will be applied in this order ('0' or empty = auto){/tr}">
        </div>
    </div>
    <div class="mb-3 row">
        <label class="col-sm-3 col-form-label" title="{tr}Text to search for{/tr}">{tr}Search{/tr}</label>
        <div class="col-sm-7 offset-sm-1">
            <input type="text" name="srch" value="{$srch|escape}" title="{tr}Text to search for{/tr}" class="form-control">
        </div>
    </div>
    <div class="mb-3 row">
        <label class="col-sm-3 col-form-label" title="{tr}Text to replace{/tr}">{tr}Replace{/tr}</label>
        <div class="col-sm-7 offset-sm-1">
            <input type="text" name="repl" value="{$repl|escape}" title="{tr}Text to replace{/tr}" class="form-control">
        </div>
    </div>
    <div class="mb-3 row">
        <label class="col-sm-3 col-form-label" title="{tr}Is this regular expression or simple search/replacer{/tr}">{tr}Regex{/tr}</label>
        <div class="col-sm-7 offset-sm-1">
            <input type="checkbox" name="type" {if $type eq 'y'}checked="checked"{/if} title="{tr}Is this regular expression or simple search/replacer{/tr}">
        </div>
    </div>
    <div class="mb-3 row">
        <label class="col-sm-3 col-form-label" title="{tr}Is case sensitive (for simple replacer){/tr}">{tr}Case sensitive{/tr}</label>
        <div class="col-sm-7 offset-sm-1">
            <input type="checkbox" name="casesense" {if $casesense eq 'y'}checked="checked"{/if} title="{tr}Is case sensitive (for simple replacer){/tr}">
        </div>
    </div>
    <div class="mb-3 row">
        <label class="col-sm-3 col-form-label" title="{tr}subset of chars: imsxeADSXUu, which are regex modifiers{/tr}">{tr}Regex modifiers{/tr}</label>
        <div class="col-sm-7 offset-sm-1">
            <input type="text" maxlength="20" size="20" class="form-control" name="rxmod" value="{$rxmod|escape}" title="{tr}subset of chars: imsxeADSXUu, which are regex modifiers{/tr}">
        </div>
    </div>
    <div class="mb-3 row">
        <label class="col-sm-3 col-form-label" title="{tr}Human-readable text description of rule{/tr}">{tr}Description{/tr}</label>
        <div class="col-sm-7 offset-sm-1">
            <textarea name="description" class="form-control" rows="4" title="{tr}Human-readable text description of rule{/tr}">{$description|escape}</textarea>
        </div>
    </div>
    <div class="mb-3 row">
        <label class="col-sm-3 col-form-label">{tr}Enabled{/tr}</label>
        <div class="col-sm-7 offset-sm-1">
            <input type="checkbox" name="enabled" {if $enabled eq 'y'}checked="checked"{/if} title="{tr}Check to enable this rule{/tr}">&nbsp;
        </div>
    </div>
    <div class="mb-3 row">
        <label class="col-sm-3 col-form-label"></label>
        <div class="col-sm-7 offset-sm-1">
            <input type="submit" class="btn btn-primary" name="save" value="{tr}Save{/tr}">
        </div>
    </div>
    <div class="mb-3 row">
        <label class="col-sm-3 col-form-label">{tr}Preview options{/tr}</label>
    </div>
    <div class="mb-3 row">
        <label class="col-sm-3 col-form-label" title="{tr}Apply all rules or just this to generate preview{/tr}">{tr}Apply all rules{/tr}</label>
        <div class="col-sm-7 offset-sm-1">
            <input type="checkbox" name="all" {if $all eq 'y'}checked="checked"{/if} title="{tr}Apply all rules or just this to generate preview{/tr}">
        </div>
    </div>
    <div class="mb-3 row">
        <label class="col-sm-3 col-form-label" title="{tr}View source code after rules applied{/tr}">{tr}Code preview{/tr}</label>
        <div class="col-sm-7 offset-sm-1">
            <input type="checkbox" name="code" {if $code eq 'y'}checked="checked"{/if} title="{tr}View source code after rules applied{/tr}">
        </div>
    </div>
    <div class="mb-3 row">
        <label class="col-sm-3 col-form-label" title="{tr}Generate HTML preview{/tr}">{tr}HTML preview{/tr}</label>
        <div class="col-sm-7 offset-sm-1">
            <input type="checkbox" name="html" {if $html eq 'y'}checked="checked"{/if} title="{tr}Generate HTML preview{/tr}">
        </div>
    </div>
    <div class="mb-3 row">
        <label class="col-sm-3 col-form-label" title="{tr}Test file from repository to generate preview for (empty = configured start page){/tr}">{tr}File{/tr}</label>
        <div class="col-sm-7 offset-sm-1">
            <input type="text" name="file" value="{$file|escape}" class="form-control" title="{tr}Test file from repository to generate preview for (empty = configured start page){/tr}">
        </div>
    </div>
    <div class="mb-3 row">
        <label class="col-sm-3 col-form-label" title="{tr}View source code after rules applied{/tr}">{tr}Code preview{/tr}</label>
        <div class="col-sm-7 offset-sm-1">
            <input type="checkbox" name="code" {if $code eq 'y'}checked="checked"{/if} title="{tr}View source code after rules applied{/tr}">
        </div>
    </div>
    <div class="mb-3 row">
        <label class="col-sm-3 col-form-label"></label>
        <div class="col-sm-7 offset-sm-1">
            <input type="submit" class="btn btn-primary btn-sm" name="preview" value="{tr}Preview{/tr}">
        </div>
    </div>
</form>

{if (($html eq 'y') or ($code eq 'y')) and (strlen($preview_data) gt 0)}
    <h2>{tr}Preview Results{/tr}</h2>
    {if strlen($css_file) > 0}
        <link rel="StyleSheet" href="{$css_file}" type="text/css">
    {/if}
    <div class="integration_preview">
        {if $code eq 'y'}
            <div class="codelisting"><pre>{$preview_data|escape:"html"|wordwrap:120:"\n"}</pre></div>
        {/if}
        {if $html eq 'y'}
                <div class="integrated-page">{$preview_data}</div>
        {/if}
    </div>
{/if}

<h2>{tr}Rules List{/tr}</h2>

{* Table with list of repositories *}
<div class="{if $js}table-responsive{/if}"> {* table-responsive class cuts off css drop-down menus *}
    <table class="table table-striped table-hover" id="integrator_rules">
        <tr>
            <th rowspan="2"><span title="{tr}Rule order{/tr}">#</span></th>
            <th>{tr}Search{/tr}</th>
            <th>{tr}Replace{/tr}</th>
            <th>{tr}Regex{/tr}</th>
            <th>{tr}Case{/tr}</th>
            <th></th>
        </tr><tr>
            <th colspan="5">{tr}Description{/tr}</th>
        </tr>

        {section name=rule loop=$rules}
            <tr>
                <td{if (strlen($rules[rule].description) > 0)} rowspan="2"{/if}>
                    {if $rules[rule].enabled ne 'y'}<s>{$rules[rule].ord|escape}</s>
                    {else}{$rules[rule].ord|escape}
                    {/if}
                </td>
                <td class="text">{$rules[rule].srch|escape}</td>
                <td class="text">{$rules[rule].repl|escape}</td>
                <td class="text">{$rules[rule].type|escape}</td>
                <td class="text">{$rules[rule].casesense|escape}</td>
                <td class="action">
                    {actions}
                        {strip}
                            <action>
                                <a href="tiki-admin_integrator_rules.php?action=edit&amp;repID={$repID|escape}&amp;ruleID={$rules[rule].ruleID|escape}">
                                    {icon name='edit' _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
                                </a>
                            </action>
                            <action>
                                <form action="tiki-admin_integrator_rules.php" method="post">
                                    {ticket}
                                    <input type="hidden" name="action" value="rm">
                                    <input type="hidden" name="repID" value="{$repID}">
                                    <input type="hidden" name="ruleID" value="{$rules[rule].ruleID}">
                                    <button type="submit" class="btn btn-link px-0 pt-0 pb-0" title=":{tr}Delete{/tr}" onclick="confirmPopup('{tr _0=$rules[rule].srch}Are you sure you want to delete %0?{/tr}')">
                                        {icon name='remove' _menu_text='y' _menu_icon='y' alt="{tr}Remove{/tr}"}
                                    </button>
                                </form>
                            </action>
                        {/strip}
                    {/actions}
                </td>

                {* Show description as colspaned row if it is not an empty *}
                {if (strlen($rules[rule].description) > 0)}
                    </tr><tr>
                        <td colspan="5" class="text">{$rules[rule].description|escape}</td>
                {/if}
            </tr>
        {/section}
    </table>
</div>
