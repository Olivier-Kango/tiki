<div class="userWizardIconleft">{icon name='user-edit' alt="{tr}User Wizard{/tr}"}</div>
{tr}Use 'watches' to monitor wiki pages or other objects.{/tr} {tr}Watch new items by clicking the {icon name='watch'} button on specific pages.{/tr}<br/>
{tr}Set up below preferences related to receiving notifications by email about changes in the site{/tr}.<br/><br/><br/>

{if $email_ok eq 'n'}
    {remarksbox type="warning" title="{tr}Warning{/tr}"}
        {tr}You need to set your email to receive email notifications.{/tr}
        <a href="tiki-user_preferences.php" class="alert-link">{icon name="next"}</a>
    {/remarksbox}
{/if}

<div class="adminWizardContent">
    <fieldset>
        {if $prefs.feature_user_watches eq 'y'}
            <legend>{tr}Notification Preferences{/tr}</legend>
            <div class="userWizardIconright">{icon name='envelope-open' alt="{tr}Notification Preferences{/tr}"}</div>
            <table class="formcolor" style="width:80%">
                {tr}Send notification when I am the editor:{/tr}
                <tr>
                    <td style="width:48%">
                        <p><input type="checkbox" class="form-check-input" aria-label="{tr}Select{/tr}" name="user_wiki_watch_editor" {if $user_wiki_watch_editor eq 'y'}checked{/if}> {tr}Wiki{/tr}</p>
                        <p><input type="checkbox" class="form-check-input" aria-label="{tr}Select{/tr}" name="user_article_watch_editor" {if $user_article_watch_editor eq 'y'}checked{/if}> {tr}Article{/tr}</p>
                        <p><input type="checkbox" class="form-check-input" aria-label="{tr}Select{/tr}" name="user_blog_watch_editor" {if $user_blog_watch_editor eq 'y'}checked{/if}> {tr}Blog{/tr}</p>
                        <p><input type="checkbox" class="form-check-input" aria-label="{tr}Select{/tr}" name="user_category_watch_editor" {if $user_category_watch_editor eq 'y'}checked{/if}> {tr}Category{/tr}</p>
                    </td>
                    <td style="width:4%">
                        &nbsp;
                    </td>
                    <td style="width:48%">
                        <p><input type="checkbox" class="form-check-input" aria-label="{tr}Select{/tr}" name="user_tracker_watch_editor" {if $user_tracker_watch_editor eq 'y'}checked{/if}> {tr}Tracker{/tr}</p>
                        <p><input type="checkbox" class="form-check-input" aria-label="{tr}Select{/tr}" name="user_calendar_watch_editor" {if $user_calendar_watch_editor eq 'y'}checked{/if}> {tr}Calendar{/tr}</p>
                        <p><input type="checkbox" class="form-check-input" aria-label="{tr}Select{/tr}" name="user_comment_watch_editor" {if $user_comment_watch_editor eq 'y'}checked{/if}> {tr}Comment{/tr}</p>
                        <p><input type="checkbox" class="form-check-input" aria-label="{tr}Select{/tr}" name="user_plugin_approval_watch_editor" {if $user_plugin_approval_watch_editor eq 'y'}checked{/if}> {tr}Plugin approval{/tr}</p>
                    </td>
                </tr>
            </table>
        {else}
            {tr}The feature user watches is disabled in this site{/tr}.<br/>
            {tr}You might ask your site admin to enable it{/tr}.
        {/if}
    </fieldset>

</div>
