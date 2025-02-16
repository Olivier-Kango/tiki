{title help='share' admpage='share'}
    {if $report != 'y'}{tr}Share this page{/tr}{else}{tr}Report this page{/tr}{/if}
{/title}

{if isset($sent) && empty($errors)}
    <div id="success" class="alert alert-success">
        {icon name='ok' alt="{tr}OK{/tr}" style="vertical-align:middle" align="left"}
        {if $report ne 'y'}
            {tr}Page shared{/tr}
            <br>
        {else}
            {tr}Your report was sent to the Webmaster{/tr}
        {/if}
        {if isset($emailSent) and $report ne 'y'}
            <div>
                {tr}The link was sent via email to the following addresses:{/tr} {$addresses|escape}
            </div>
        {/if}
        {if isset($tweetId)}
            <div>
                <a href="http://www.twitter.com/">{icon name='twitter' size='2'}</a>
                {tr}The link was sent via Twitter{/tr}
            </div>
        {/if}
        {if isset($facebookId) and $facebookId!=false}
            <div>
                {icon name='facebook' size='2'}{tr}The link was posted on your Facebook wall{/tr}
            </div>
        {/if}
        {if isset($messageSent)}
            <div>{tr}The link was sent as message to{/tr} {$messageSentTo|escape}</div>
        {/if}
        {if isset($threadId) and $threadId>0}
            <div>
                {tr}The link was published in a{/tr} <a href="tiki-view_forum_thread.php?comments_parentId={$threadId}>{tr}forum{/tr}</a>
                <br>
                {foreach from=$feedbacks item=feedback}
                    {$feedback}
                    <br>
                {/foreach}
            </div>
        {/if}
        {if not empty($back_url)}
            {button _type='link' href=$back_url _text='{tr}Back{/tr}'}
        {/if}
    </div>
{/if}

{if !empty($errors)}
    <div id="shareerror" class="alert alert-warning">
        {icon name='error' alt="{tr}Error{/tr}" style="vertical-align:middle" align="left"}
        {foreach from=$errors item=m name=errors}
            {$m}
            {if !$smarty.foreach.errors.last}<br>{/if}
        {/foreach}
    </div>
{/if}

{if !isset($sent) && empty($errors)}
    <div id="ajaxmsg"></div>
    <form method="post" action="tiki-share.php?url={$url|escape:url}" id="share-form">
        {ticket}
        <div class="mb-3 row">
            <label class="col-form-label col-sm-3">
                {tr}Subject{/tr}
            </label>
            <div class="col-sm-9">
                <input class="form-control" type="text" name="subject" value="{$subject|escape|default:"{tr}Have a look at this page{/tr}"}">
            </div>
        </div>
        <div class="mb-3 row clearfix">
            <label class="col-form-label col-sm-3">
                {tr}Text{/tr}
            </label>
            <div class="col-sm-9">
                <textarea name="comment" class="form-control" rows="5" id='comment'>{$comment|escape|@default:"{tr}Access rights are granted for the page.{/tr}"}</textarea>
            </div>
        </div>
        {if $prefs.share_display_links eq 'y'}
            <div class="mb-3 row">
                <label for="url" class="col-form-label col-sm-3">
                    {tr}Link{/tr}
                </label>
                <div class="col-sm-9">
                    <a href="{$prefix}{$url}">{$prefix}{$url}</a>
                </div>
            </div>
            <div class="mb-3 row">
                {if $report != 'y' and $shorturl neq $prefix|cat:$url}
                    <label for="url" class="col-form-label col-sm-3">
                        {tr}Short link{/tr}
                    </label>
                    <div class="col-sm-9">
                        <span class="form-text">
                            <a href="{$shorturl}">{$shorturl}</a>
                        </span>
                    </div>
                {/if}
            </div>
        {/if}
        <div class="card mb-4">
            <div class="card-header radio">
                <div class="row mb-0">
                    <label for="do_email" class="col-form-label col-sm-3 float-start">
                        {icon name="admin_webmail"} {tr}Send email{/tr}
                    </label>
                    {if $report !='y'}
                        <div class="col-sm-9">
                            <label class="col-form-label">
                                <input class="share-email-show" type="radio" name="do_email" value="1" checked="checked" class="share-email-toggle">
                                {tr}Yes{/tr}
                            </label>
                            <label class="col-form-label">
                                <input class="share-email-hide" type="radio" name="do_email" value="0">
                                {tr}No{/tr}
                            </label>
                        </div>
                    {else}
                        <input type="hidden" name="do_email" value="1">
                    {/if}
                </div>
            </div>
            <div class="card-body share-email-details">
                {if $report!='y'}
                    <div class="mb-3 row">
                        <label for="addresses" class="col-form-label col-sm-3">
                            {tr}Recipient(s){/tr}
                        </label>
                        <div class="col-sm-9">
                            {if $prefs.feature_jquery_autocomplete == 'y'}
                                {user_selector contact='true' user = '' multiple='true' editable='y' mustmatch='false' group='all' name='addresses' id='addresses' class='form-control' user_selector_threshold=0 style='width:99%'}
                                <span class="form-text">
                                    {tr}Separate multiple email addresses with a comma and a space{/tr}
                                </span>
                            {else}
                                <input class="form-control" type="text" size="60" name="addresses" value="{$addresses|escape}">
                                <span class="form-text">
                                    {tr}Separate multiple email addresses with a comma.{/tr}
                                </span>
                            {/if}
                        </div>
                    </div>
                {/if}
                {if $prefs.share_display_name_and_email eq 'y'}
                    <div class="mb-3 row">
                        <label for="name" class="col-form-label col-sm-3">
                            {tr}Your name{/tr}
                        </label>
                        <div class="col-sm-9">
                            <input class="form-control" type="text" name="name" value="{$name|username:false:true}">
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="email" class="col-form-label col-sm-3">
                            {tr}Your email{/tr}{if empty($email)} <strong class='mandatory_star text-danger tips' title=":{tr}This field is mandatory{/tr}">*</strong>{/if}
                        </label>
                        <div class="mandatory_field col-sm-9">
                            <input class="form-control" type="text" name="email" value="{$email}">
                        </div>
                    </div>
                {else}
                    <input type="hidden" value="{$name}" name="name">
                    <input type="hidden" value="{$email}" name="email">
                {/if}
                {if $prefs.auth_token_share eq 'y' and $user!='' and $report !='y'}
                    <div class="mb-3 row">
                        <div class="offset-sm-3 col-sm-9">
                            <div class="form-check">
                                <label class="form-check-label">
                                    <input type="checkbox" class="form-check-input" value="1" name="share_access" id="share_access" {if $share_access}checked="checked" {/if}> {tr}Share access rights{/tr}
                                </label>
                            </div>
                        </div>
                    </div>
                {/if}
                {if $prefs.share_token_notification eq 'y'}
                    <div class="mb-3 row">
                        <div class="offset-sm-3 col-sm-9">
                            <div class="form-check">
                                <label class="form-check-label">
                                    <input type="checkbox" class="form-check-input" value="y" name="share_token_notification" {if $share_token_notification eq 'y'}checked="checked" {/if}> {tr}Receive notifications when the link is accessed{/tr}
                                </label>
                            </div>
                        </div>
                    </div>
                {/if}
                {if $prefs.share_can_choose_how_much_time_access eq 'y' && $prefs.auth_token_access eq 'y'}
                    <div class="mb-3 row">
                        <label for="how_much_time_access" class="col-form-label col-sm-3">
                            {tr}Token Access Limit{/tr}
                        </label>
                        <div class="col-sm-2">
                            {if $prefs.share_max_access_time eq -1}
                                <input type="text" name="how_much_time_access" value="{$how_much_time_access|default:1}" class="form-control">
                            {else}
                                <select id="how_much_time_access" name="how_much_time_access" class="form-control">
                                    {section name=share_max_access start=1 loop=$prefs.share_max_access_time+1}
                                        {html_options values=$smarty.section.share_max_access.index output=$smarty.section.share_max_access.index}
                                    {/section}
                                </select>
                            {/if}
                        </div>
                        <div class="form-text col-sm-9 offset-sm-3">
                            {tr}How many times recipients can access this page{/tr}
                        </div>
                    </div>
                {/if}
            </div>
        </div>
        {if $twitterRegistered}
            <div class="card mb-4">
                <tr>
                    <td rowspan="2">
                        {icon name='twitter' size='2'}
                        <br>
                        {tr}Tweet via Twitter{/tr}
                    </td>
                    <td>
                        {if $twitter}
                            <input type="radio" name="do_tweet" value="1">
                            {tr}Yes{/tr}
                            <input type="radio" name="do_tweet" value="0" checked="checked">
                            {tr}No{/tr}
                        {else}
                            {remarksbox type="note" title="{tr}Note{/tr}"}
                                <p><a href="tiki-socialnetworks.php" class="alert-link">{tr}Authorize with Twitter first{/tr}</a></p>
                            {/remarksbox}
                        {/if}
                    </td>
                </tr>
                <tr id="twitterrow">
                    <td>
                        {if $twitter}
                            <div id="twittertable" style="display: none;">
                                <div class="mb-3 row">
                                    <label class="col-form-label col-sm-3">{tr}Tweet{/tr}</label>
                                    <div class="col-sm-7">
                                        <input type="text" class="form-control" name="tweet" maxlength="140" style="width:95%;" id="tweet" value="{$subject|escape|default:"{tr}Have a look at {/tr}"} {$shorturl}">
                                    </div>
                                </div>
                            </div>
                        {else}
                            &nbsp;
                        {/if}
                    </td>
                </tr>
            </div>
        {/if}
        {if $facebookRegistered}
            <div class="card mb-4">
                <tr>
                    <td rowspan="2">
                        {icon name='facebook' size='2' alt="Facebook"}
                        <br>
                            {tr}Put on my Facebook wall{/tr}
                    </td>
                    <td>
                        {if $facebook}
                            <input type="radio" name="do_fb" value="1">
                            {tr}Yes{/tr}
                            <input type="radio" name="do_fb" value="0" checked="checked">
                            {tr}No{/tr}
                        {else}
                            {remarksbox type="note" title="{tr}Note{/tr}"}
                                <p><a href="tiki-socialnetworks.php" class="alert-link">{tr}Authorize with Facebook first{/tr}</a></p>
                            {/remarksbox}
                        {/if}
                    </td>
                </tr>
                <tr id="fbrow">
                    <td>
                        {if $facebook}
                            <div id="fbtable" style="display: none;">
                                <div class="mb-3 row">
                                    <label class="col-form-label col-sm-3">{tr}Link text{/tr}</label>
                                    <div class="col-sm-7">
                                        <input type="text" name="fblinktitle" id="fblinktitle" value="{$fblinktitle|escape}" style="width: 95%;" class="form-control">
                                        <div class="form-text">
                                            {tr}This will be the title for the URL{/tr}
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label class="col-form-label col-sm-3">{tr}Like this post{/tr}</label>
                                    <div class="col-sm-7">
                                        <label>
                                            <input type="radio" name="fblike" value="1" {if $fblike==1}checked="checked" {/if}>
                                            {tr}Yes{/tr}
                                        </label>
                                        <label>
                                            <input type="radio" name="fblike" value="0" {if $fblike==0}checked="checked" {/if}>
                                            {tr}No{/tr}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        {else}
                            &nbsp;
                        {/if}
                    </td>
                </tr>
            </div>
        {/if}
        {if $prefs.feature_messages eq 'y' && $report != 'y'}
            <div class="card mb-4">
                <div class="card-header radio">
                    {if $send_msg=='y'}
                        <label for="do_message" class="col-form-label col-sm-3">
                            {icon name="admin_messages"} {tr}Send a message{/tr}
                        </label>
                        <label>
                            <input class="share-message-show" type="radio" name="do_message" value="1">
                            {tr}Yes{/tr}
                        </label>
                        <label>
                            <input class="share-message-hide" type="radio" name="do_message" value="0" checked="checked">
                            {tr}No{/tr}
                        </label>
                    {else}
                        {remarksbox type="note" title="{tr}Send a message{/tr}" close="n"}
                            {tr}You do not have the permission to send messages or you did not allow other users to send you messages.{/tr}
                        {/remarksbox}
                    {/if}
                </div>
                <div class="card-body share-message-details hidden">
                {if $send_msg}
                    <div class="mb-3 row clearfix">
                        <label for="messageto" class="col-form-label col-sm-3">
                            {tr}User(s){/tr}
                        </label>
                        <div class="col-sm-9">
                            {if $prefs.feature_jquery_autocomplete == 'y'}
                                {user_selector user = '' editable='y' multiple='true' name='messageto' style='width:99%' user_selector_threshold=0}
                            {else}
                                <input class="form-control" type="text" class="form-control" name="messageto" value="{$messageto|escape}">
                            {/if}
                            <span class="form-text">
                                {tr}Separate multiple recipients with a semicolon.{/tr}
                            </span>
                        </div>
                    </div>
                    <div class="mb-3 row clearfix">
                        <label for="priority" class="col-form-label col-sm-3">
                            {tr}Priority{/tr}
                        </label>
                        <div class="col-sm-9">
                            <select name="priority" id="mess-prio" class="form-control">
                                <option value="1" {if $priority eq 1}selected="selected"{/if}>1 -{tr}Lowest{/tr}-</option>
                                <option value="2" {if $priority eq 2}selected="selected"{/if}>2 -{tr}Low{/tr}-</option>
                                <option value="3" {if $priority eq 3}selected="selected"{/if}>3 -{tr}Normal{/tr}-</option>
                                <option value="4" {if $priority eq 4}selected="selected"{/if}>4 -{tr}High{/tr}-</option>
                                <option value="5" {if $priority eq 5}selected="selected"{/if}>5 -{tr}Very High{/tr}-</option>
                            </select>
                        </div>
                    </div>
                {else}
                    &nbsp;
                {/if}
                </div>
            </div>
        {/if}
        {if $prefs.feature_forums eq 'y' && $report != 'y'}
            <div class="card mb-4">
                <div class="card-header radio">
                    {if count($forums)>0}
                        <div class="row mb-0">
                            <label for="do_forum" class="col-form-label col-sm-3">
                                {icon name="admin_forums"} {tr}Post on forum{/tr}
                            </label>
                            <div class="col-sm-9">
                                <label class="col-form-label">
                                    <input class="share-forum-show" type="radio" name="do_forum" value="1">
                                    {tr}Yes{/tr}
                                </label>
                                <label class="col-form-label">
                                    <input class="share-forum-hide" type="radio" name="do_forum" value="0" checked="checked">
                                    {tr}No{/tr}
                                </label>
                            </div>
                        </div>
                    {else}
                        {remarksbox type="note" title="{tr}Post on forum{/tr}" close="n"}
                            {tr}There is no forum where you can post a message.{/tr}
                        {/remarksbox}
                    {/if}
                </div>
                <div class="card-body share-forum-details hidden">
                    {if count($forums)>0}
                        <div class="mb-3 row">
                            <label class="col-form-label col-sm-3">
                                {tr}Forum{/tr}
                            </label>
                            <div class="col-sm-9">
                                <select name="forumId" id="forumId" class="form-control">
                                    {foreach from=$forums item="forum"}
                                        <option value="{$forum.forumId}"{if $forum.forumId==$forumId} selected="selected"{/if}>
                                            {$forum.name}{if $forum.forum_use_password!='n'} ({tr}password-protected{/tr}){/if}
                                        </option>
                                    {/foreach}
                                </select>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label class="col-form-label col-sm-3">
                                {tr}Password{/tr}
                            </label>
                            <div class="col-sm-9">
                                <input class="form-control" type="password" name="forum_password" autocomplete="new-password">
                            </div>
                        </div>
                        {if $prefs.feature_contribution eq 'y'}
                            {include file='contribution.tpl'}
                        {/if}
                    {/if}
                </div>
            </div>
        {/if}
        {if $prefs.feature_antibot eq 'y' && $user eq ''}
            {include file='antibot.tpl'}
        {/if}
        <div class="submit text-center">
            <input type="hidden" name="url" value="{$url|escape}">
            <input type="hidden" name="back_url" value="{$back_url|escape}">
            <input type="hidden" name="report" value="{$report}">
            <button type="submit" class="btn btn-secondary" name="send">
                {icon name="share"} {tr}Share{/tr}
            </button>
        </div>
    </form>
{else}
    <p><a href="javascript:window.history.go(-2);">{tr}Return to previous page{/tr}</a></p>
{/if}
{jq}
    $('#share-form').on("submit", function(e){
            if($('#addresses').val() !='' || ! $('#emailtable:visible').length) {
                    $(this).tikiModal("Please wait....");
                    var postData = $(this).serializeArray();
                    var formURL = 'tiki-share.php?send=share';
                    $.ajax({
                            url : formURL,
                            type: "POST",
                            data : postData,
                            success:function(data, textStatus, jqXHR) {
                                    var shrsuccess = $($.parseHTML(data)).find("#success").html();
                                    var shrerror = $($.parseHTML(data)).find("#shareerror").html();
                                    if(shrsuccess) {
                                            $('#ajaxmsg').html("<div class='alert alert-success'>"+shrsuccess+"</div>");
                                    } else {
                                            $('#ajaxmsg').html("<div class='alert alert-warning'>"+shrerror+"</div>");
                                    }
                                    $('#share-form').tikiModal("");
                                    $('#addresses').val('');
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                    $('#share-form').tikiModal("");
                            }
                    });
            } else {
                    alert("You must provide at least one recipient email address");
            }
            e.preventDefault();
            return false;
    });
    $(".share-email-hide").on("click", function(){
        $(".share-email-details").addClass('hidden');
    });
    $(".share-email-show").on("click", function(){
        $(".share-email-details").removeClass('hidden');
    });
    $(".share-message-hide").on("click", function(){
        $(".share-message-details").addClass('hidden');
    });
    $(".share-message-show").on("click", function(){
        $(".share-message-details").removeClass('hidden');
    });
    $(".share-forum-hide").on("click", function(){
        $(".share-forum-details").addClass('hidden');
    });
    $(".share-forum-show").on("click", function(){
        $(".share-forum-details").removeClass('hidden');
    });
{/jq}
