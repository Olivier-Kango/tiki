{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
    <form method="post" action="{service controller=api_token action=create}">
        {ticket}
        <div class="mb-3 row">
            <label class="col-form-label col-sm-2">
                {tr}User{/tr}
                <a class="tikihelp text-info" title="{tr}User account:{/tr} {tr}All API requests with this token will authenticate against the selected user account.{/tr}">
                    {icon name=information}
                </a>
            </label>
            <div class="col-sm-10">
                {user_selector id="user_selector_api_token" realnames="n"}
            </div>
        </div>
        <div class="mb-3 row">
            <label class="col-form-label col-sm-2">
                {tr}Valid until{/tr}
                <a class="tikihelp text-info" title="{tr}Valid until:{/tr} {tr}Optionally specify the time when this token will expire.{/tr}">
                    {icon name=information}
                </a>
            </label>
            <div class="col-sm-10">
                {jscalendar date="" fieldname="expireAfter" showtime='y' timezone=$displayTimezone}
            </div>
        </div>
        <div class="submit">
            <input
                type="submit"
                class="btn btn-primary"
                value="{tr}Create{/tr}"
            >
        </div>
    </form>
{/block}
