{assign var="baseURI" value="{$smarty.server.REQUEST_URI}"}
{$headerlib->add_jsfile("lib/jquery_tiki/tiki-profile.js")}

{remarksbox type="tip" title="{tr}Tip{/tr}"}
    <a class="alert-link" href="http://profiles.tiki.org">{tr}Tiki Configuration Profiles{/tr}</a>.
    {tr}You can revert a profile you applied through the <a href="tiki-syslog.php">Tiki logs</a>{/tr}.
{/remarksbox}

{if isset($profilefeedback)}
    {remarksbox type="note" title="{tr}Note{/tr}"}

        {tr}The following list of changes has been applied:{/tr}
        <ul>
        {section name=n loop=$profilefeedback}
            <li>
                <p>{$profilefeedback[n]}</p>
            </li>
        {/section}
        </ul>
    {/remarksbox}
{/if}

{tabset name='tabs_admin-profiles'}

    {tab name="{tr}Apply{/tr}"}

            {if $openSources == 'some'}
                {remarksbox type="warning" title="{tr}Warning{/tr}"}
                    {tr}Some of your Profiles Repositories are not connecting. This may prevent you from applying certain profiles{/tr}
                {/remarksbox}
            {/if}
            <form method="get" action="tiki-admin.php?page=profiles">
                <h4>{tr}Find profiles{/tr} <small>{tr}Search by name, types and repository{/tr}</small></h4>
                <div class="row">
                    <div class="col-sm-6">
                        <div class="mb-3 row">
                            <label class="col-form-label" for="profile">{tr}Profile name{/tr} </label>
                            <input type="text" class="form-control" name="profile" placeholder="{tr}Find{/tr}..." id="profile" value="{if isset($profile)}{$profile|escape}{/if}" />
                        </div>
                        {if isset($category_list) and count($category_list) gt 0}
                            <div class="mb-3 row">
                                <label class="col-form-label" for="categories">{tr}Profile types{/tr}</label>
                                    <select multiple="multiple" name="categories[]" id="categories" class="form-control" style="width:100%;">
                                        {foreach item=cat from=$category_list}
                                            <option value="{$cat|escape}"{if !empty($categories) and in_array($cat, $categories)} selected="selected"{/if}>{$cat|escape}</option>
                                        {/foreach}
                                    </select>
                            </div>
                        {/if}
                        <div class="mb-3 row">
                            <label class="col-form-label" for="repository">{tr}Profile repository{/tr}</label>
                            <select name="repository" id="repository" class="form-control">
                                <option value="">{tr}All{/tr}</option>
                                {foreach item=source from=$sources}
                                    <option value="{$source.url|escape}"{if isset($repository) && $repository eq $source.url} selected="selected"{/if}>{$source.short|escape}</option>
                                {/foreach}
                            </select>
                        </div>
                        <input type="hidden" name="page" value="profiles">
                        <input type="hidden" name="redirect" value=0>
                        <div class="mb-3 text-center">
                            <input type="submit" class="btn btn-secondary" name="list" value="{tr}Find{/tr}" />
                        </div>
                    </div>
                    <div class="col-sm-6">
                            {remarksbox type="info" title="{tr}Suggested Profiles{/tr}" close="n"}
                                {assign var=profilesFilterUrlStart value="tiki-admin.php?categories%5B%5D="}
                                {assign var=profilesFilterUrlMid value='.x&categories%5B%5D='}
                                {assign var=profilesFilterUrlEnd value='&repository=http%3A%2F%2Fprofiles.tiki.org%2Fprofiles&page=profiles&preloadlist=y&list=List#step2'}

                                <p>
                                    {assign var=profilesFilterUrlFeaturedProfiles value='Featured+profiles'}
                                    <a href="{$profilesFilterUrlStart}{$tikiMajorVersion}{$profilesFilterUrlMid}{$profilesFilterUrlFeaturedProfiles}{$profilesFilterUrlEnd}" class="alert-link">{tr}Featured Site Profiles{/tr}</a>
                                    <br>{tr}Featured Site Profiles is a list of applications that are maintained by the Tiki community and are a great way to get started.{/tr}
                                </p>

                                <p>
                                    {assign var=profilesFilterUrlFullProfiles value='Full+profile+(out+of+the+box+%26+ready+to+go)'}
                                    <a href="{$profilesFilterUrlStart}{$tikiMajorVersion}{$profilesFilterUrlMid}{$profilesFilterUrlFullProfiles}{$profilesFilterUrlEnd}" class="alert-link">{tr}Full Profiles{/tr}</a>
                                    <br>{tr}Full Profiles are full featured out of the box solutions.{/tr}
                                </p>

                                <p>
                                    {assign var=profilesFilterUrlMiniProfiles value='Mini-profile+(can+be+included+in+other)'}
                                    <a href="{$profilesFilterUrlStart}{$tikiMajorVersion}{$profilesFilterUrlMid}{$profilesFilterUrlMiniProfiles}{$profilesFilterUrlEnd}" class="alert-link">{tr}Mini Profiles{/tr}</a>
                                    <br>{tr}Mini Profiles will configure specific features and are a great way to add more functionality to an existing configuration.{/tr}
                                </p>

                                <p>
                                    {assign var=profilesFilterUrlLearningProfiles value='Learning+profile+(just+to+show+off+feature)'}
                                    <a href="{$profilesFilterUrlStart}{$tikiMajorVersion}{$profilesFilterUrlMid}{$profilesFilterUrlLearningProfiles}{$profilesFilterUrlEnd}" class="alert-link">{tr}Learning Profiles{/tr}</a>
                                    <br>{tr}Learning Profiles will allow you to quickly evaluate specific features in Tiki.{/tr}
                                </p>
                            {/remarksbox}
                    </div>

                </div>
            </form>
            <a id="step2"></a>
            {if isset($result)}
                <h4>{tr}Select and apply profile <small>Click on a configuration profile name below to review it and apply it on your site</small>{/tr}</h4>
                <div class="table-responsive">
                    <table class="table table-condensed table-hover table-striped">
                        <tr>
                            <th>{tr}Profile name{/tr}</th>
                            <th>{tr}Repository{/tr}</th>
                            <th>{tr}Profile type{/tr}</th>
                        </tr>
                        {foreach key=k item=profile from=$result}
                            <tr id="profile-{$k}">
                                {if $profile.name == $show_details_for}
                                    {assign var="show_details_for_profile_num" value="$k"}
                                    {assign var="show_details_for_fullname" value=$profile.name|escape}
                                    {assign var="show_details_for_domain" value=$profile.domain|escape}
                                    {assign var="show_details_for_event" value={ticket mode=get}}
                                    {$show=true}
                                    <td>{$profile.name|escape}: {tr}See profile info below (may take a few seconds to load){/tr}.</td>
                                {else}
                                    <td><a href="#" onclick="$.profilesShowDetails( '{$baseURI}', 'profile-{$k}', '{$profile.domain|escape}', '{$profile.name|escape}', event, '{$show}'); return false"
                                           data-ticket="{ticket mode=get}"
                                        >
                                            {$profile.name|escape}
                                        </a>{if !empty($profile.installed)} <em>{tr}applied{/tr}</em>{/if}
                                    </td>
                                {/if}

                                <td>{$profile.domain}</td>
                                <td>{$profile.categoriesString}</td>
                            </tr>
                        {/foreach}
                        {if $result|@count eq '0'}
                            <tr><td colspan="3" class="odd">{tr}No results{/tr}</td></tr>
                        {/if}
                    </table>
                    {if isset($show_details_for_profile_num) && $show_details_for_profile_num != ""}
                        {jq}$.profilesShowDetails('{{$baseURI}}', 'profile-{{$show_details_for_profile_num}}', '{{$show_details_for_domain}}', '{{$show_details_for_fullname}}', '{{$show_details_for_event}}', '{{$show}}');{/jq}
                    {/if}
                </div>
            {/if}
    {/tab}

    {tab name="{tr}Export{/tr}"}
        <form action="tiki-admin.php?page=profiles" method="post">
            <input type="hidden" name="redirect" value=0>
            <fieldset id="export_to_yaml">
                <legend class="h3">{tr}Export YAML{/tr}</legend>
                {if !empty(exported_content)}
                    {foreach $exported_content as $export}
                        <div class="wikitext">{$export}</div>
                    {/foreach}
                {/if}
                <div class="mb-3 row">
                    <label class="col-form-label col-sm-2" for="export_type">{tr}Object type{/tr}</label>
                    <div class="col-sm-5">
                    <select name="export_type" id="export_type" class="form-control">
                        <option value="prefs"{if !empty($export_type) && $export_type eq "prefs"} selected="selected"{/if}>
                            {tr}Preferences{/tr}
                        </option>
                        <option value="modules"{if !empty($export_type) && $export_type eq "modules"} selected="selected"{/if}>
                            {tr}Modules{/tr}
                        </option>
                        <option value="pages"{if !empty($export_type) && $export_type eq "pages"} selected="selected"{/if}>
                            {tr}Pages{/tr}
                        </option>
                        {if $tiki_p_admin_trackers eq 'y'}
                            <option value="trackers"{if !empty($export_type) && $export_type eq "trackers"} selected="selected"{/if}>
                                {tr}Trackers{/tr}
                            </option>
                        {/if}
                    </select>
                    </div>
                </div>
                <fieldset>
                    <legend class="h3">{tr}Export modified preferences as YAML{/tr}</legend>
                    <div class="t_navbar">
                        {listfilter selectors=".profile_export_list > li"}
                        <label for="select_all_prefs_to_export">{tr}Toggle Visible{/tr}</label>
                        <input type="checkbox" id="select_all_prefs_to_export" />
                        <label for="export_show_added">{tr}Show added preferences{/tr}</label>
                        <input type="checkbox" name="export_show_added" id="export_show_added" {if !empty($smarty.request.export_show_added)} checked="checked"{/if} >
                    </div>
                    <div id="export_options_container">
                        <ul id="prefs_to_export_list" class="profile_export_list"{if not empty($export_type) and $export_type neq "prefs"} style=display:none;"{/if}>
                            {foreach $modified_list as $name => $data}
                                <li class="form-check">
                                    {if is_array($data.current.expanded)}
                                        {$current = $data.current.expanded|json_encode}
                                    {else}
                                        {$current = $data.current.expanded}
                                    {/if}
                                    <input type="checkbox" class="form-check-input" name="prefs_to_export[{$name}]" value="{$current|escape}"
                                           id="checkbox_{$name}"{if isset($prefs_to_export[$name])} checked="checked"{/if}
                                    >
                                    <label for="checkbox_{$name}" class="form-check-label">
                                        {$name} = '<strong>{$current|truncate:40:"...":true|escape}</strong>'{* TODO: This one line per preference display format is ugly and doesn't work for multiline values *}
                                        <em>
                                            &nbsp;&nbsp;
                                            {if isset($data.default)}
                                                {if empty($data.default)}
                                                    ('')
                                                {else}
                                                    {if is_array($data.default)}{assign var=default  value=$data.default|join:', '}{else}{assign var=default value=$data.default}{/if}
                                                    ('{$default|truncate:20:"...":true|escape}')
                                                {/if}
                                            {else}
                                                ({tr}no default{/tr})
                                            {/if}
                                        </em>
                                    </label>
                                </li>
                            {/foreach}
                        </ul>
                        <ul id="modules_to_export_list" class="profile_export_list"{if $export_type neq "modules"} style=display:none;"{/if}>
                            {foreach from=$modules_for_export key="name" item="data"}
                                <li class="form-check">
                                    <input type="checkbox" class="form-check-input" name="modules_to_export[{$name}]" value="{$data.name|escape}"
                                           id="modcheckbox_{$name}"{if isset($modules_to_export[$name])} checked="checked"{/if} />
                                    <label for="modcheckbox_{$name}" class="form-check-label">
                                        {$data.data.name|escape} :
                                        <em>
                                            &nbsp;&nbsp;
                                            {$data.data.position}
                                            {$data.data.order}
                                        </em>
                                    </label>
                                </li>
                            {/foreach}
                        </ul>
                        <ul id="pages_to_export_list" class="profile_export_list"{if $export_type neq "pages"} style=display:none;"{/if}>
                            {foreach from=$pages_for_export item="page"}
                                <li class="form-check">
                                    <input type="checkbox" class="form-check-input" name="pages_to_export[{$page.page_id}]" value="{$page.page_id}"
                                           id="pages_checkbox_{$page.page_id}"{if isset($pages_to_export[$page.page_id])} checked="checked"{/if} />
                                    <label for="pages_checkbox_{$page.page_id}" class="form-check-label">
                                        {$page.pageName|escape}
                                    </label>
                                </li>
                            {/foreach}
                        </ul>
                        {if $tiki_p_admin_trackers eq 'y'}
                            <ul id="trackers_to_export_list" class="profile_export_list"{if $export_type neq "trackers"} style=display:none;"{/if}>
                                {foreach from=$trackers_for_export item="tracker"}
                                    <li class="form-check">
                                        <input type="checkbox" class="form-check-input" name="trackers_to_export[{$tracker.trackerId}]" value="{$tracker.trackerId}"
                                               id="trackers_checkbox_{$tracker.trackerId}"{if isset($trackers_to_export[$tracker.trackerId])} checked="checked"{/if} />
                                        <label for="trackers_checkbox_{$tracker.trackerId}" class="form-check-label">
                                            {$tracker.name|escape}
                                        </label>
                                    </li>
                                {/foreach}
                            </ul>
                        {/if}
                    </div>
                    <div class="text-center submit input_submit_container">
                        <input type="submit" class="btn btn-primary" name="export" value="{tr}Export{/tr}" />
                    </div>
                </fieldset>
            </fieldset>
        </form>
    {/tab}

    {tab name="{tr}Advanced{/tr}"}
        <br>
        <fieldset>
            <h4>{tr}Repository status{/tr} <small>{tr}status of the registered profile repositories{/tr}</small></h4>
            <div class="table-responsive">
                <table class="table">
                    <tr>
                        <th>{tr}Profile repository{/tr}</th>
                        <th>{tr}Status{/tr}</th>
                        <th>{tr}Last update{/tr}</th>
                    </tr>
                    {foreach key=k item=entry from=$sources}
                        <tr>
                            <td>{$entry.short}</td>
                            <td id="profile-status-{$k}">
                                {if $entry.status == 'open'}
                                    {icon name='status-open' iclass='tips' ititle="{tr}Status:{/tr}{tr}Open{/tr}"}
                                    {icon name='status-pending' istyle='display:none' iclass='tips' ititle="{tr}Status:{/tr}{tr}Pending{/tr}"}
                                    {icon name='status-closed' istyle='display:none' iclass='tips' ititle="{tr}Status:{/tr}{tr}Closed{/tr}"}
                                {elseif $entry.status == 'closed'}
                                    {icon name='status-open' istyle='display:none' iclass='tips' ititle="{tr}Status:{/tr}{tr}Open{/tr}"}
                                    {icon name='status-pending' istyle='display:none' iclass='tips' ititle="{tr}Status:{/tr}{tr}Pending{/tr}"}
                                    {icon name='status-closed' iclass='tips' ititle="{tr}Status:{/tr}{tr}Closed{/tr}"}
                                {else}
                                    {icon name='status-open' istyle='display:none' iclass='tips' ititle="{tr}Status:{/tr}{tr}Open{/tr}"}
                                    {icon name='status-pending' iclass='tips' ititle="{tr}Status:{/tr}{tr}Pending{/tr}"}
                                    {icon name='status-closed' istyle='display:none' iclass='tips' ititle="{tr}Status:{/tr}{tr}Closed{/tr}"}
                                {/if}
                            </td>
                            <td><span id="profile-date-{$k}">{$entry.formatted}</span> <a href='javascript:$.profilesRefreshCache("{$baseURI}", "{$k}")' title="{tr}Refresh{/tr}">{icon name="refresh" iclass='tips' ititle=":{tr}Refresh{/tr}"}</a></td>
                        </tr>
                    {/foreach}
                </table>
            </div>
            <form action="tiki-admin.php?page=profiles" method="post">
                {ticket}
                {preference name=profile_autoapprove_wikiplugins}
                {preference name=profile_unapproved}
                {preference name=profile_sources}
                {preference name=profile_channels}
                <div class="text-center submit">
                    <input type="submit" class="btn btn-primary" name="config" value="{tr}Save{/tr}"/>
                </div>
            </form>
        </fieldset>
        <fieldset><legend class="h3">{tr}Profile tester{/tr}</legend>
            <form action="tiki-admin.php?page=profiles" method="post">
                {ticket}
                <input type="hidden" name="redirect" value=0>
                {remarksbox type="warning" title="{tr}Warning{/tr}"}
                    {tr}Paste or type wiki markup and YAML (with or without the {literal}{CODE}{/literal} tags) into the text area below{/tr}<br>
                    <em><strong>{tr}This will run the profile and make potentially unrecoverable changes in your database!{/tr}</strong></em>
                {/remarksbox}
                <div class="adminoptionbox">
                    <div class="adminoptionlabel mb-3 row">
                        <label for="profile_tester_name" class="col-form-label col-sm-4">{tr}Test profile name{/tr} </label>
                        <div class="col-sm-4 mb-3">
                        <input class="form-control" type="text" name="profile_tester_name" id="profile_tester_name" value="{if isset($profile_tester_name)}{$profile_tester_name}{else}Test{/if}" />
                        </div>
                        <div class="col-sm-4">
                            <select class="form-select" name="empty_cache" class="form-control">
                            <option value=""{if isset($empty_cache) and $empty_cache eq ''} checked="checked"{/if}>{tr}None{/tr}</option>
                            <option value="all"{if isset($empty_cache) and $empty_cache eq 'all'} checked="checked"{/if}>{tr}All{/tr}</option>
                            <option value="templates_c"{if isset($empty_cache) and $empty_cache eq 'templates_c'} checked="checked"{/if}>templates_c</option>
                            <option value="temp_cache"{if isset($empty_cache) and $empty_cache eq 'temp_cache'} checked="checked"{/if}>temp_cache</option>
                            <option value="temp_public"{if isset($empty_cache) and $empty_cache eq 'temp_public'} checked="checked"{/if}>temp_public</option>
                            <option value="modules_cache"{if isset($empty_cache) and $empty_cache eq 'modules_cache'} checked="checked"{/if}>modules_cache</option>
                            <option value="prefs"{if isset($empty_cache) and $empty_cache eq 'prefs'} checked="checked"{/if}>prefs</option>
                        </select>{$empty_cache}
                            </div>
                    </div>
                    <div class="mb-3 row">
                        <div class="col-sm-12">
                            <textarea data-codemirror="true" data-syntax="yaml" id="profile_tester" name="profile_tester" class="form-control">{if isset($test_source)}{$test_source}{/if}</textarea>
                        </div>
                    </div>
                </div>
                <div align="center" style="padding:1em;">
                    <input type="submit" class="btn btn-primary" name="test" value="{tr}Test{/tr}">
                </div>
            </form>
        </fieldset>
    {/tab}

{/tabset}

{jq}
        {{foreach item=k from=$oldSources}
                $.profilesRefreshCache("{$baseURI}", "{$k}");
    {/foreach}}
{/jq}
