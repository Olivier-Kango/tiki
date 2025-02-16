{title admpage="articles" help="Articles"}{tr}Article Topics{/tr}{/title}
<div class="t_navbar mb-4">
    {if $tiki_p_admin eq 'y' or $tiki_p_admin_cms eq 'y'}
        {button href="tiki-list_articles.php" _type="link" _icon_name="list" _text="{tr}List Articles{/tr}"}
        {button href="tiki-article_types.php" _type="link" _icon_name="structure" _text="{tr}Article Types{/tr}"}
    {/if}
</div>
<form enctype="multipart/form-data" action="tiki-admin_topics.php" method="post">
    {ticket}
    <h2>{tr}Add topic{/tr}</h2>
    <div class="mb-3 row">
        <label class="col-sm-2 col-form-label" for="name">{tr}Name{/tr}</label>
        <div class="col-sm-10">
            <input type="text" name="name" id="name" class="form-control">
        </div>
    </div>
    <div class="mb-3 row">
        <label class="col-sm-2 col-form-label" for="image">{tr}Image{/tr}</label>
        <div class="col-sm-10">
            <input type="hidden" name="MAX_FILE_SIZE" value="1000000">
            <input class="form-control" name="userfile1" type="file" accept="image/*">
        </div>
    </div>
    <div class="mb-3 row">
        <label class="col-sm-2 col-form-label" for="notificationemail">{tr}Notification Email{/tr}</label>
        <div class="col-sm-10">
            <div class="card bg-body-tertiary">
                <div class="card-body">
                    {tr}You will be able to add a notification email per article topic when you edit the topic after its creation{/tr}
                </div>
            </div>
        </div>
    </div>
    <div class="text-center">
        <input type="submit" class="btn btn-primary" name="addtopic" value="{tr}Add{/tr}">
    </div>
</form>
<h2>{tr}Topics{/tr}</h2>
<div class="{if $js}table-responsive{/if}"> {* table-responsive class cuts off css drop-down menus *}
    <table class="table table-striped table-hover">
        <tr>
            <th>{tr}ID{/tr}</th>
            <th>{tr}Name{/tr}</th>
            <th>{tr}Image{/tr}</th>
            <th>{tr}Active{/tr}</th>
            <th>{tr}Articles{/tr}</th>
            {if $prefs.feature_submissions eq 'y'}<th>{tr}Submissions{/tr}</th>{/if}
            <th></th>
        </tr>
        {section name=user loop=$topics}
            <tr>
                <td class="integer">{$topics[user].topicId}</td>
                <td class="text">
                    <a class="link" href="tiki-view_articles.php?topic={$topics[user].topicId}">{$topics[user].name|escape}</a>
                </td>
                <td class="text">
                    {if $topics[user].image_size}
                        <img alt="{tr}topic image{/tr}" src="article_image.php?image_type=topic&amp;id={$topics[user].topicId}&amp;reload=1">
                    {else}
                        &nbsp;
                    {/if}
                </td>
                <td class="text">{if $topics[user].active eq 'y'}{icon name="toggle-on"}{else}{icon name="toggle-off"}{/if}</td>
                <td><span class="badge bg-secondary">{$topics[user].arts}</span></td>
                {if $prefs.feature_submissions eq 'y'}<td><span class="badge bg-secondary">{$topics[user].subs}</span></td>{/if}
                <td class="action">
                    {actions}
                        {strip}
                            <action>
                                {permission_link mode=text type=topic permType=articles id=$topics[user].topicId title=$topics[user].name}
                            </action>
                            {if $topics[user].active eq 'n'}
                                <action>
                                    <form action="tiki-admin_topics.php" method="post">
                                        {ticket}
                                        <input type="hidden" name="activate" value="{$topics[user].topicId}">
                                        <button type="submit" class="btn btn-link px-0 pt-0 pb-0" onclick="confirmPopup('{tr}Are you sure you want to activate this topic?{/tr}')">
                                            {icon name="toggle-on" _menu_text='y' _menu_icon='y' alt="{tr}Activate{/tr}"}
                                        </button>
                                    </form>
                                </action>
                            {else}
                                <action>
                                    <form action="tiki-admin_topics.php" method="post">
                                        {ticket}
                                        <input type="hidden" name="deactivate" value="{$topics[user].topicId}">
                                        <button type="submit" class="btn btn-link px-0 pt-0 pb-0" onclick="confirmPopup('{tr}Are you sure you want to de-activate this topic?{/tr}')">
                                            {icon name="toggle-off" _menu_text='y' _menu_icon='y' alt="{tr}De-activate{/tr}"}
                                        </button>
                                    </form>
                                </action>
                            {/if}
                            <action>
                                <a href="tiki-edit_topic.php?topicid={$topics[user].topicId}">
                                    {icon name='edit' _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
                                </a>
                            </action>
                            <action>
                                <form action="tiki-admin_topics.php" method="post">
                                    {ticket}
                                    <input type="hidden" name="remove" value="{$topics[user].topicId}">
                                    <button type="submit" class="btn btn-link px-0 pt-0 pb-0" onclick="confirmPopup('{tr}Are you sure you want to remove this topic?{/tr}')">
                                        {icon name='remove' _menu_text='y' _menu_icon='y' alt="{tr}Remove{/tr}"}
                                    </button>
                                </form>
                            </action>
                            <action>
                                <form action="tiki-admin_topics.php" method="post">
                                    {ticket}
                                    <input type="hidden" name="removeall" value="{$topics[user].topicId}">
                                    <button type="submit" class="btn btn-link px-0 pt-0 pb-0" onclick="confirmPopup('{tr}Are you sure you want to remove this topic AND all the articles related?{/tr}')">
                                        {icon name='remove' _menu_text='y' _menu_icon='y' alt="{tr}Remove with articles{/tr}"}
                                    </button>
                                </form>
                            </action>
                        {/strip}
                    {/actions}
                </td>
            </tr>
        {sectionelse}
            {if $prefs.feature_submissions eq 'y'}{norecords _colspan=7}{else}{norecords _colspan=6}{/if}
        {/section}
    </table>
</div>
