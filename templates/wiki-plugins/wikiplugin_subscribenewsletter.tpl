{if $wpSubscribe eq 'y'}
    {if empty($subscribeThanks)}
        {tr}Subscription confirmed!{/tr}
    {else}
        {if !empty($subscribeThanksIsHtml)}
            {$subscribeThanks nofilter}
        {else}
            {$subscribeThanks|escape}
        {/if}
    {/if}
    
{elseif $alreadySubscribed}
    {remarksbox type='warning'}
        {$alreadySubscribedMessage|escape}
    {/remarksbox}

{else}
    <form name="wpSubscribeNL" method="post">
        <input type="hidden" name="wpNlId" value="{$subscribeInfo.nlId|escape}">

        {if !empty($wpError)}
            {remarksbox type='errors'}
                {$wpError|escape}
            {/remarksbox}
        {/if}

        <div class="form-inline row wp-subscribe-inline">
            <div class="input-group">
                <div class="wp-subscribe-email-wrap">
                    <span class="wp-subscribe-email-icon"><i class="fa fa-envelope" aria-hidden="true"></i></span>
                    <input type="email" class="form-control" id="wpEmail" name="wpEmail" size="50" value="{$subscribeEmail|escape}" placeholder="{tr}Enter your email address{/tr}" required>
                </div>
                <div class="input-group-append">
                    {if empty($subcribeMessage)}
                        <input type="submit" class="btn btn-primary" name="wpSubscribe" value="{tr}Subscribe to the newsletter:{/tr} {$subscribeInfo.name}">
                    {else}
                        <input type="submit" class="btn btn-primary" name="wpSubscribe" value="{$subcribeMessage|escape}">
                    {/if}
                </div>
            </div>
        </div>

        {if $useCaptcha !== 0}
            {if !$user and $prefs.feature_antibot eq 'y'}
                {include file='antibot.tpl' antibot_table="y" showmandatory="y" form="$inmodule"}
            {/if}
        {/if}
    </form>
{/if}
