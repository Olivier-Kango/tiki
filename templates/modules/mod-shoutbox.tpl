{if $tiki_p_view_shoutbox eq 'y'}
    {tikimodule title=$tpl_module_title name="shoutbox" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}

        {if $tiki_p_post_shoutbox eq 'y'}
            {js_maxlength textarea=shout_msg maxlength=255}
            <form action="#" method="post" onsubmit="return verifyForm(this);" id="shout_form">
                {if !empty($shout_error)}<div class="highlight">{$shout_error}</div>{/if}
                <div class="text-center">
                    <textarea rows="3" class="form-control mb-3 tshoutbox" id="shout_msg" name="shout_msg"></textarea>
                    {if $prefs.feature_antibot eq 'y' && $user eq ''}
                        <table>{include file="antibot.tpl"}</table>
                    {/if}
                    {if $prefs.feature_socialnetworks eq 'y' && $user neq ''}
                        {if $prefs.socialnetworks_twitter_consumer_key neq '' && $tweet}
                            <div class="form-check">
                                <input type="hidden" name="tweet" value="-1" />
                                <input type="checkbox" class="form-check-input" id="shout_tweet" name="shout_tweet" value='1' />
                                <label class="form-check-label" for="shout_tweet">{tr}Tweet with Twitter{/tr}</label>
                            </div>
                        {/if}
                        {if $prefs.socialnetworks_facebook_application_id neq '' && $facebook}
                            <div class="form-check">
                                <input type="hidden" name="facebook" value="-1" />
                                <input type="checkbox" class="form-check-input" name="shout_facebook" id="shout_facebook" value='1' />
                                <label class="form-check-label" for="shout_facebook">{tr}Post on my Facebook wall{/tr}</label>
                            </div>
                        {/if}
                    {/if}
                    <input type="submit" class="btn btn-primary btn-sm" id="shout_send" name="shout_send" value="{$buttontext}" />
                </div>
            </form>
        {/if}

        {section loop=$shout_msgs name=ix}
            <div class="shoutboxmodmsg">
                {assign var=userlink value=$shout_msgs[ix].user|userlink:"linkmodule"}
                {capture name=date}{strip} {* Print date *}
                    {$shout_msgs[ix].timestamp|tiki_short_time}, {$shout_msgs[ix].timestamp|tiki_short_date}
                {/strip}{/capture}
                {* Show user message in style according to 'tooltip' module parameter *}
                {assign var=cdate value=$smarty.capture.date}
                {if $tooltip == 1}{* TODO: Improve $userlink modifier one day to handle other attibutes better? *}
                    <b>{strip}{$userlink|replace:"\" href=":"&lt;br /&gt;&lt;em&gt;{tr}Shout date:{/tr} $cdate&lt;/em&gt;\" href="}{/strip}</b>:
                {else}
                    <b>{strip}{$userlink}{/strip}</b>, {$cdate}:
                {/if}
                {$shout_msgs[ix].message}
                {if $tiki_p_admin_shoutbox eq 'y' || $user == $shout_msgs[ix].user}
                    {if 0 and $prefs.feature_ajax eq 'y'}
                        <a class="text-danger" aria-label="{tr}Delete{/tr}"  onclick="removeShout({$shout_msgs[ix].msgId});return false" href="#" title="|{tr}Delete this shout{/tr}">[icon name="delete"}</a> <a class="mx-1 text-primary" href="tiki-shoutbox.php?msgId={$shout_msgs[ix].msgId}" title="|{tr}Edit this shout{/tr}">{icon name="edit"}</a>
                    {else}
                        <a class="text-danger mx-1" aria-label="{tr}Delete{/tr}"  href="tiki-shoutbox.php?remove={$shout_msgs[ix].msgId}">{icon name="delete"}</a> <a href="tiki-shoutbox.php?msgId={$shout_msgs[ix].msgId}" class="mx-1 text-primary">{icon name="edit"}</a>
                    {/if}
                {/if}
            </div>
        {/section}

        <div style="text-align: center">
            <a href="tiki-shoutbox.php" class="linkmodule more">{tr}Read More{/tr}…</a>
        </div>
    {/tikimodule}
{/if}
