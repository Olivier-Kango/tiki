{if $wpSubscribe eq 'y'}
    {if empty($subscribeThanks)}
        {tr}Subscription confirmed!{/tr}
    {else}
        {$subscribeThanks|escape}
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

        <style>
            .wp-subscribe-inline .input-group {
                display: flex;
                flex-wrap: nowrap;
                width: 100%;
                align-items: stretch;
            }

            .wp-subscribe-inline .wp-subscribe-email-wrap {
                position: relative;
                flex: 1 1 auto;
            }

            .wp-subscribe-inline .wp-subscribe-email-icon {
                position: absolute;
                top: 0;
                bottom: 0;
                left: 1rem;
                display: flex;
                align-items: center;
                color: var(--secondary, #6c757d);
                pointer-events: none;
                z-index: 3;
            }

            .wp-subscribe-inline .wp-subscribe-email-wrap #wpEmail {
                height: 100%;
                width: 100%;
                padding-left: 2.75rem;
                border-top-right-radius: 0;
                border-bottom-right-radius: 0;
            }

            .wp-subscribe-inline .input-group-append,
            .wp-subscribe-inline .input-group-append .btn {
                display: flex;
                align-items: stretch;
            }

            .wp-subscribe-inline .input-group-append .btn {
                border-top-left-radius: 0;
                border-bottom-left-radius: 0;
                margin-left: -1px;
            }
        </style>

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
