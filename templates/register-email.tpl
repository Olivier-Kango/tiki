{if $prefs.user_register_prettytracker eq 'y' and $prefs.user_register_prettytracker_tpl and $prefs.socialnetworks_user_firstlogin != 'y'}
    <input type="text" id="email" name="email" class="form-control" >
    {if $prefs.user_register_prettytracker_hide_mandatory neq 'y'}&nbsp;<strong class='mandatory_star text-danger tips' title=":{tr}This field is mandatory{/tr}">*</strong>{/if}
{else}
    {if $prefs.login_is_email ne 'y'}
        <div class="tiki-form-group row">
            <label class="col-sm-4 col-form-label" for="email">{tr}Email{/tr} {if $trackerEditFormId}<strong class='mandatory_star text-danger tips' title=":{tr}This field is mandatory{/tr}">*</strong>{/if}</label>
            <div class="col-sm-8">
                <input class="form-control" type="text" id="email" name="email" value="{if !empty($smarty.post.email)}{$smarty.post.email}{/if}">
                {if $prefs.validateUsers eq 'y' and $prefs.validateEmail ne 'y'}
                    <p class="form-text">
                        <em>{tr}A valid email is mandatory to register{/tr}</em>
                    </p>
                {/if}
            </div>
        </div>
    {/if}
{/if}
