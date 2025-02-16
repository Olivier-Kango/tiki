{jq}
    $("#genPass").on("click", function () {
        var passcodeId = $("input[name=registerPasscode]").attr('id');
        genPass(passcodeId);
        return false
    });
    var frm = $('form[name="LogForm"]'),
        pretty_tracker_tpl = $('input[name="user_register_prettytracker_tpl"]'),
        user_register_pt_checkbox = $('input[name="user_register_prettytracker"]'),
        warning_ept = $('#empty_pretty_tracker_warning');
    warning_ept.hide();
    //prevent space, backslash and bad chars in template name, see lib/wiki/wikilib.php:188
    pretty_tracker_tpl.keypress(function(e){
    return /[^ //?#\[\]@$&+;=<>\\]/i.test(e.key);
    });
    frm.on("submit", function(){
        if (user_register_pt_checkbox.is(':checked')
        && pretty_tracker_tpl.val() === "") {
            warning_ept.show();
            pretty_tracker_tpl.trigger("focus");
            return false;
        }
    });
{/jq}
<form action="tiki-admin.php?page=login" class="admin" method="post" name="LogForm" enctype="multipart/form-data">
    {ticket}
    <div class="t_navbar mb-4 clearfix">
        {button href="tiki-admingroups.php" _type="text" _class="btn btn-link tips" _icon_name="group" _text="{tr}Groups{/tr}" _title=":{tr}Group Administration{/tr}"}
        {button href="tiki-adminusers.php" _type="text" _class="btn btn-link tips" _icon_name="user" _text="{tr}Users{/tr}" _title=":{tr}User Administration{/tr}"}
        {permission_link mode=text label="{tr}Permissions{/tr}"}
        {include file='admin/include_apply_top.tpl'}
    </div>
    {tabset name="admin_login"}
        {tab name="{tr}General Preferences{/tr}"}
            <br>
            {preference name=auth_method}
            {preference name=feature_intertiki}
            <fieldset>
                <legend class="h3">{tr}Registration{/tr} &amp; {tr}Log in{/tr}</legend>
                {preference name=user_must_change_password_set_default_on}
                {preference name=allowRegister}
                <div class="adminoptionboxchild" id="allowRegister_childcontainer">
                    {preference name=validateUsers}
                    {preference name=validateEmail}
                    {preference name=validateRegistration}
                    <div class="adminoptionboxchild" id="validateRegistration_childcontainer">
                        {preference name=validator_emails size="80"}
                    </div>
                    {preference name=useRegisterPasscode}
                    <div class="adminoptionboxchild" id="useRegisterPasscode_childcontainer">
                        {preference name=registerPasscode}
                        <div class="col-sm-8 offset-sm-4">
                            <span id="genPass">
                                {button href="#" _onclick="" _text="{tr}Generate a passcode{/tr}"}
                            </span>
                        </div>
                        {preference name=showRegisterPasscode}
                    </div>
                    {preference name=registerKey}
                    {if $gd_lib_found neq 'y'}
                        {remarksbox type="warning" title="{tr}Warning{/tr}" close="n"}
                            {tr}Requires PHP GD library{/tr}.
                        {/remarksbox}
                    {/if}
                    {preference name=generate_password}
                    {preference name=http_referer_registration_check}
                    {preference name=email_detect_disposable}
                    <fieldset>
                        <legend class="h3">{tr}CAPTCHA{/tr}</legend>
                        {preference name=feature_antibot}
                        <div class="adminoptionboxchild" id="feature_antibot_childcontainer">
                            {preference name=captcha_wordLen}
                            {preference name=captcha_width}
                            {preference name=captcha_noise}
                            {preference name=recaptcha_enabled}
                            <div class="adminoptionboxchild" id="recaptcha_enabled_childcontainer">
                                {preference name=recaptcha_pubkey}
                                {preference name=recaptcha_privkey}
                                {preference name=recaptcha_theme}
                                {preference name=recaptcha_version}
                            </div>
                            {preference name=captcha_questions_active}
                            <div class="adminoptionboxchild" id="captcha_questions_active_childcontainer">
                                {preference name=captcha_questions}
                            </div>
                    </fieldset>
                    <legend class="h3">{tr}Group and tracker login settings{/tr}</legend>
                    <div class="adminoptionbox mb-3 row">
                        <label for="registration_choices" class="col-sm-4 col-form-label">{tr}Users can select a group to join at registration:{/tr}</label>
                        <div class="col-sm-8 adminoptionlabel">
                            <select id="registration_choices" name="registration_choices[]" multiple="multiple" size="5" class="form-control">
                                {foreach key=g item=gr from=$listgroups}
                                    {if $gr.groupName ne 'Anonymous'}
                                        <option value="{$gr.groupName|escape}" {if $gr.registrationChoice eq 'y'} selected="selected"{/if}>{$gr.groupName|truncate:"52"|escape}</option>
                                    {/if}
                                {/foreach}
                            </select>
                            <div class="form-text">{tr}By default, new users automatically join the Registered group{/tr}.</div>
                        </div>
                    </div>
                    {preference name=user_must_choose_group}
                    {preference name=url_after_validation}
                </div>
                {preference name=userTracker}
                <div class="adminoptionboxchild" id="userTracker_childcontainer">
                    {preference name=user_add_tracker_item_set_default_on}
                    {preference name=feature_userWizardDifferentUsersFieldIds}
                    <div class="adminoptionboxchild" id="feature_userWizardDifferentUsersFieldIds_childcontainer">
                        {preference name=feature_userWizardUsersFieldIds}
                    </div>
                    {preference name=user_register_prettytracker}
                    <div class="adminoptionboxchild" id="user_register_prettytracker_childcontainer">
                    <div id="empty_pretty_tracker_warning" style="display:none;">
                        {remarksbox type="warning" title="{tr}Registration pretty tracker template can't be empty{/tr}" close="n"}
                        {tr}Please indicate a Registration pretty tracker template to collect more user information.{/tr}<br/>
                        {tr}Use a wiki page name or a Smarty template with a .tpl extension.{/tr}
                        {/remarksbox}
                    </div>
                        {preference name=user_register_prettytracker_tpl}
                        {preference name=user_register_prettytracker_hide_mandatory}
                    </div>
                    {preference name=user_register_prettytracker_output}
                    <div class="adminoptionboxchild" id="user_register_prettytracker_output_childcontainer">
                        {preference name=user_register_prettytracker_outputwiki}
                        {preference name=user_register_prettytracker_outputtowiki}
                    </div>
                    {preference name=user_trackersync_trackers}
                    {preference name=user_trackersync_realname}
                    {preference name=user_trackersync_groups}
                    {preference name=user_trackersync_geo}
                    {preference name=user_trackersync_lang}
                    {preference name=user_tracker_auto_assign_item_field}
                    <div class="mb-3 row">
                        <div class="col-sm-8 offset-sm-4">
                            {button href="?page=login&amp;resync_tracker=y" _onclick="confirmPopup('{tr}Resynchronize all user preferences based on above settings?{/tr}', '{ticket mode=get}')" _text="{tr}Synchronize all users{/tr}"}
                            <div class="form-text">{tr}This will re-save all user tracker items to resynchronize prefs like real name, user groups, location.{/tr}</div>
                        </div>
                    </div>
                </div>
                {preference name=user_force_avatar_upload}
                {preference name=tracker_force_fill}
                <div class="adminoptionboxchild" id="tracker_force_fill_childcontainer">
                    {preference name=tracker_force_tracker_id}
                    {preference name=tracker_force_mandatory_field}
                    {preference name=tracker_force_tracker_fields}
                </div>
                {preference name=groupTracker}
                <legend class="h3">{tr}Other login settings{/tr}</legend>
                {preference name=email_due}
                {preference name=unsuccessful_logins}
                {preference name=unsuccessful_logins_invalid}
                {preference name=eponymousGroups}
                {preference name=desactive_login_autocomplete}
                {preference name=permission_denied_login_box}
                {preference name=login_text_explanation}
                {preference name=login_multiple_forbidden}
                {preference name=login_cookies_auto_clean}
                <div class="adminoptionboxchild" id="login_multiple_forbidden_childcontainer">
                    {preference name=login_grab_session}
                </div>
                {preference name=session_protected}
                {preference name=https_login}
                {preference name=login_http_basic}
                <div class="adminoptionboxchild https_login_childcontainer allowed encouraged force_nocheck required">
                    {preference name=feature_show_stay_in_ssl_mode}
                    {preference name=feature_switch_ssl_mode}
                    {preference name=http_port}
                    {preference name=https_port}
                    {preference name=https_external_links_for_users}
                </div>
                <fieldset>
                    <legend class="h3">{tr}Cookies{/tr}</legend>
                    {preference name=rememberme}
                    <div class="adminoptionboxchild rememberme_childcontainer all always">
                        {preference name=remembertime}
                        {preference name=cookie_refresh_rememberme}
                    </div>
                    {preference name=cookie_name}
                    {preference name=cookie_domain}
                    {preference name=cookie_path}
                    <hr>
                    <legend class="h3">{tr}Cookie consent{/tr}</legend>
                    {preference name=cookie_consent_feature}
                    <div class="adminoptionboxchild" id="cookie_consent_feature_childcontainer">
                        {preference name=cookie_consent_name}
                        {preference name=cookie_consent_expires}
                        {preference name=cookie_consent_description}
                        {preference name=cookie_consent_question}
                        {preference name=cookie_consent_analytics}
                        {preference name=cookie_consent_alert}
                        {preference name=cookie_consent_button}
                        {preference name=cookie_consent_mode}
                        {preference name=cookie_consent_dom_id}
                        {preference name=cookie_consent_disable}
                    </div>
                </fieldset>
                {preference name=feature_banning}
                <div class="adminoptionboxchild" id="feature_banning_childcontainer">
                    {preference name=feature_banning_email}
                    {preference name=feature_banning_attempts}
                    {preference name=feature_banning_duration}
                </div>
            </fieldset>
            <fieldset>
                <legend class="h3">{tr}Username{/tr}</legend>
                {preference name=login_is_email mode=invert}
                {preference name=login_is_email_obscure}
                {preference name=user_unique_email}
                <div class="adminoptionboxchild" id="user_unique_email_childcontainer">
                    {preference name=user_unique_email_validation}
                </div>
                {preference name=login_allow_email}
                <div class="adminoptionboxchild" id="login_is_email_childcontainer">
                    {preference name=min_username_length}
                    {preference name=max_username_length}
                    {preference name=lowercase_username}
                </div>
                {preference name=username_pattern}
                {preference name=login_autogenerate}
            </fieldset>
            <fieldset>
                <legend class="h3">{tr}Password{/tr}</legend>
                {preference name=forgotPass}
                {preference name=twoFactorAuth}
                <div class="adminoptionboxchild" id="twoFactorAuth_childcontainer">
                    {preference name=twoFactorAuthIntervalDays}
                    {preference name=twoFactorAuthType}
                    {preference name=twoFactorAuthEmailTokenLength}
                    {preference name=twoFactorAuthEmailTokenTTL}
                    {preference name=twoFactorAuthAllUsers}
                    {preference name=twoFactorAuthIncludedGroup}
                    {preference name=twoFactorAuthIncludedUsers}
                    {preference name=twoFactorAuthExcludedGroup}
                    {preference name=twoFactorAuthExcludedUsers}
                </div>
                {preference name=change_password}
                {preference name=pass_chr_num}
                {preference name=pass_chr_case}
                {preference name=pass_chr_special}
                {preference name=pass_repetition}
                {preference name=pass_blacklist}
                {preference name=pass_diff_username}
                {preference name=min_pass_length}
                {preference name=pass_due}
            </fieldset>
            <fieldset>
                <div class="mb-3 row">
                    <div class="col-sm-8 offset-sm-4">
                        {button href="?page=login&amp;refresh_email_group=y" _onclick="confirmPopup('{tr}Assign users to groups based on email patterns?{/tr}', '{ticket mode=get}')" _text="{tr}Assign users to groups by matching email patterns{/tr}"}
                        <div class="form-text">{tr}An email pattern must be defined in the settings for at least one group for this to produce any results.{/tr}</div>
                    </div>
                </div>
            </fieldset>
        {/tab}

        {tab name="{tr}Remote Tiki Autologin{/tr}"}
            <br>
            <fieldset>
                {if $prefs.login_autologin eq 'y' and $prefs.users_admin_actions_require_validation eq 'y'}
                    {remarksbox type="warning" title="{tr}Warning{/tr}" close="n"}
                        {tr}Admin actions that require password won't work for users while in remote session. To allow those actions, the preference “Require admin users to enter their password for some critical actions” must be disabled.{/tr}
                    {/remarksbox}
                {/if}
                {preference name=login_autologin}
                {preference name=login_autologin_user}
                {preference name=login_autologin_group}
                {preference name=login_autologin_createnew}
                {preference name=login_autologin_allowedgroups}
                {preference name=login_autologin_syncgroups}
                {preference name=login_autologin_logoutremote}
                {preference name=login_autologin_redirectlogin}
                {preference name=login_autologin_redirectlogin_url}
            </fieldset>
        {/tab}

        {tab name="{tr}LDAP{/tr}"}
            <br>
            <fieldset>
                <legend class="h3">LDAP {help url="Login+Authentication+Methods"}</legend>
                {if ! $ldap_extension_loaded}
                    {remarksbox type="warning" title="{tr}Warning{/tr}" close="n"}
                        {tr}You must install PHP extension LDAP{/tr}
                    {/remarksbox}
                {else}
                    {if $prefs.auth_method ne 'ldap'}
                        {remarksbox type="warning" title="{tr}Warning{/tr}" close="n"}
                        {tr}You must change the Authentication Method to LDAP for these changes to take effect{/tr}
                        {/remarksbox}
                    {/if}
                {/if}
                {preference name=ldap_create_user_tiki}
                {preference name=ldap_create_user_tiki_validation}
                {preference name=ldap_create_user_ldap}
                {preference name=ldap_skip_admin}
                {preference name=auth_ldap_permit_tiki_users}
            </fieldset>
            <fieldset>
                <legend class="h3">{tr}LDAP bind settings{/tr}{help url="LDAP+Authentication"}</legend>
                {preference name=auth_ldap_host}
                {preference name=auth_ldap_port}
                {preference name=auth_ldap_debug}
                {preference name=auth_ldap_ssl}
                {preference name=auth_ldap_starttls}
                {preference name=auth_ldap_type}
                {preference name=auth_ldap_scope}
                {preference name=auth_ldap_basedn}
            </fieldset>
            <fieldset>
                <legend class="h3">{tr}LDAP user{/tr}</legend>
                {preference name=auth_ldap_userdn}
                {preference name=auth_ldap_userattr}
                {preference name=auth_ldap_useroc}
                {preference name=auth_ldap_nameattr}
                {preference name=auth_ldap_countryattr}
                {preference name=auth_ldap_emailattr}
            </fieldset>
            <fieldset>
                <legend class="h3">{tr}LDAP admin{/tr}</legend>
                {preference name=auth_ldap_adminuser}
                {preference name=auth_ldap_adminpass}
            </fieldset>
        {/tab}

        {tab name="{tr}LDAP external groups{/tr}"}
            <br>
            <fieldset>
                <legend class="h3">{tr}LDAP external groups{/tr}</legend>
                {preference name=auth_ldap_group_external}
            </fieldset>
            <fieldset>
                <legend class="h3">{tr}LDAP bind settings{/tr}{help url="LDAP+Authentication"}</legend>
                {preference name=auth_ldap_group_host}
                {preference name=auth_ldap_group_port}
                {preference name=auth_ldap_group_debug}
                {preference name=auth_ldap_group_ssl}
                {preference name=auth_ldap_group_starttls}
                {preference name=auth_ldap_group_type}
                {preference name=auth_ldap_group_scope}
                {preference name=auth_ldap_group_basedn}
            </fieldset>
            <fieldset>
                <legend class="h3">{tr}LDAP user{/tr}</legend>
                {preference name=auth_ldap_group_userdn}
                {preference name=auth_ldap_group_userattr}
                {preference name=auth_ldap_group_corr_userattr}
                {preference name=auth_ldap_group_useroc}
                {preference name=syncGroupsWithDirectory}
            </fieldset>
            <fieldset>
                <legend class="h3">{tr}LDAP group{/tr}</legend>
                {preference name=auth_ldap_groupdn}
                {preference name=auth_ldap_groupattr}
                {preference name=auth_ldap_groupdescattr}
                {preference name=auth_ldap_groupoc}
                {preference name=syncUsersWithDirectory}
            </fieldset>
            <fieldset>
                <legend class="h3">{tr}LDAP group member - if group membership can be found in group attributes{/tr}</legend>
                {preference name=auth_ldap_memberattr}
                {preference name=auth_ldap_memberisdn}
            </fieldset>
            <fieldset>
                <legend class="h3">{tr}LDAP user group - if group membership can be found in user attributes{/tr}</legend>
                {preference name=auth_ldap_usergroupattr}
                {preference name=auth_ldap_groupgroupattr}
            </fieldset>
            <fieldset>
                <legend class="h3">{tr}LDAP admin{/tr}</legend>
                {preference name=auth_ldap_group_adminuser}
                {preference name=auth_ldap_group_adminpass}
            </fieldset>
        {/tab}

        {tab name="{tr}PAM{/tr}"}
            <br>
            <fieldset>
                <legend class="h3">{tr}PAM{/tr} {help url="AuthPAM" desc="{tr}PAM{/tr}"}</legend>
                {if $prefs.auth_method ne 'pam'}
                    {remarksbox type="warning" title="{tr}Warning{/tr}" close="n"}
                        {tr}You must change the Authentication Method to PAM for these changes to take effect{/tr}
                    {/remarksbox}
                {/if}
                {preference name=pam_create_user_tiki}
                {preference name=pam_skip_admin}
            </fieldset>
        {/tab}

        {tab name="{tr}Shibboleth{/tr}"}
            <br>
            <fieldset>
                <legend class="h3">{tr}Shibboleth{/tr}{help url="AuthShib" desc="{tr}Shibboleth Authentication {/tr}"}</legend>
                {if $prefs.auth_method ne 'shib'}
                    {remarksbox type="warning" title="{tr}Warning{/tr}" close="n"}
                        {tr}You must change the Authentication Method to Shibboleth for these changes to take effect{/tr}
                    {/remarksbox}
                {/if}
                {preference name=shib_create_user_tiki}
                {preference name=shib_skip_admin}
                {preference name=shib_affiliation}
                {preference name=shib_usegroup}
                <div class="adminoptionboxchild" id="shib_usegroup_childcontainer">
                    {preference name=shib_group}
                </div>
            </fieldset>
        {/tab}

        {tab name="{tr}SAML2{/tr}"}
            <fieldset>
                <legend class="h3">{tr}SAML2{/tr}{help url="SAML" desc="{tr}based on Onelogin's php-saml {/tr}"}</legend>
                {if $prefs.auth_method ne 'saml' && $prefs.saml_auth_enabled eq 'y'}
                    {remarksbox type="warning" title="{tr}Warning{/tr}" close="n"}
                        {tr}You must change the Authentication Method to SAML for these changes to take effect{/tr}
                    {/remarksbox}
                {/if}

                {preference name=saml_auth_enabled}

                <fieldset>
                    <legend class="h3">{tr}Identity provider settings{/tr}</legend>
                    {preference name=saml_idp_entityid}
                    {preference name=saml_idp_sso}
                    {preference name=saml_idp_slo}
                    {preference name=saml_idp_x509cert}
                </fieldset>
                <fieldset>
                    <legend class="h3">{tr}Options{/tr}</legend>
                        {preference name=saml_options_autocreate}
                        {preference name=saml_options_sync_group}
                        {preference name=saml_options_slo}
                        {preference name=saml_options_skip_admin}
                        {preference name=saml_option_account_matcher}
                        {preference name=saml_option_default_group}
                        {preference name=saml_option_login_link_text}
                </fieldset>
                <fieldset>
                    <legend class="h3">{tr}Attribute mapping{/tr}</legend>
                        {preference name=saml_attrmap_username}
                        {preference name=saml_attrmap_mail}
                        {preference name=saml_attrmap_group}
                </fieldset>
                <fieldset>
                    <legend class="h3">{tr}Group mapping{/tr}</legend>
                        {preference name=saml_groupmap_admins}
                        {preference name=saml_groupmap_registered}
                </fieldset>
                <fieldset>
                    <legend class="h3">{tr}Advanced settings{/tr}</legend>
                        {preference name=saml_advanced_debug}
                        {preference name=saml_advanced_strict}
                        {preference name=saml_advanced_sp_entity_id}
                        {preference name=saml_advanced_nameidformat}
                        {preference name=saml_advanced_requestedauthncontext}
                        {preference name=saml_advanced_nameid_encrypted}
                        {preference name=saml_advanced_authn_request_signed}
                        {preference name=saml_advanced_logout_request_signed}
                        {preference name=saml_advanced_logout_response_signed}
                        {preference name=saml_advanced_metadata_signed}
                        {preference name=saml_advanced_want_message_signed}
                        {preference name=saml_advanced_want_assertion_signed}
                        {preference name=saml_advanced_want_assertion_encrypted}
                        {preference name=saml_advanced_retrieve_parameters_from_server}
                        {preference name=saml_advanced_sp_x509cert}
                        {preference name=saml_advanced_sp_privatekey}
                        {preference name=saml_advanced_sign_algorithm}
                        {preference name=saml_advanced_idp_lowercase_url_encoding}
                </fieldset>
            </fieldset>
        {/tab}

        {tab name="{tr}CAS{/tr}"}
            <br>
            <fieldset>
                <legend class="h3">{tr}CAS (central authentication service){/tr}{help url="CAS+Authentication"}</legend>
                {if $prefs.auth_method ne 'cas'}
                    {remarksbox type="warning" title="{tr}Warning{/tr}" close="n"}
                        {tr}You must change the Authentication Method to CAS for these changes to take effect{/tr}
                    {/remarksbox}
                {/if}
                {preference name='cas_create_user_tiki'}
                {preference name='cas_autologin'}
                {preference name='cas_skip_admin'}
                {preference name='cas_show_alternate_login'}
                {preference name='cas_force_logout'}
                {preference name='cas_version'}
                <fieldset>
                    <legend class="h3">{tr}CAS server{/tr}</legend>
                    {preference name='cas_hostname' label="{tr}CAS Server Name{/tr}"}
                    {preference name='cas_port' label="{tr}CAS Server Port{/tr}"}
                    {preference name='cas_path' label="{tr}CAS Server Path{/tr}"}
                    {preference name='cas_extra_param' label="{tr}CAS Extra Parameter{/tr}"}
                    {preference name='cas_authentication_timeout'}
                </fieldset>
            </fieldset>
        {/tab}

        {tab name="{tr}phpBB{/tr}"}
            <br>
            <fieldset>
                <legend class="h3">{tr}phpBB{/tr}{help url="phpBB+Authentication" desc="{tr}phpBB User Database Authentication {/tr}"}</legend>
                {if $prefs.auth_method ne 'phpbb'}
                    {remarksbox type="warning" title="{tr}Warning{/tr}" close="n"}
                        {tr}You must change the Authentication Method to phpBB for these changes to take effect{/tr}
                    {/remarksbox}
                {/if}
                {if $prefs.allowRegister ne 'n'}
                    {remarksbox type="warning" title="{tr}Warning{/tr}" close="n"}
                        {tr}You must turn Users can register off for phpBB Authentication to function properly{/tr}
                    {/remarksbox}
                {/if}
                {preference name=auth_phpbb_create_tiki}
                {preference name=auth_phpbb_skip_admin}
                {preference name=auth_phpbb_disable_tikionly}
                {preference name=auth_phpbb_version}
                {remarksbox type="warning" title="{tr}Warning{/tr}" close="n"}
                    {tr}MySql only (for now){/tr}
                {/remarksbox}
                {preference name=auth_phpbb_dbhost}
                {preference name=auth_phpbb_dbuser}
                {preference name=auth_phpbb_dbpasswd}
                {preference name=auth_phpbb_dbname}
                {preference name=auth_phpbb_table_prefix}
            </fieldset>
        {/tab}

        {tab name="{tr}Web Server{/tr}"}
            <br>
            <fieldset>
                <legend class="h3">{tr}Web server{/tr}{help url="External+Authentication#Web_Server_HTTP_" desc="{tr}Web Server Authentication {/tr}"}</legend>
                {if $prefs.auth_method ne 'ws'}
                    {remarksbox type="warning" title="{tr}Warning{/tr}" close="n"}
                        {tr}You must change the Authentication Method to Web Server for these changes to take effect{/tr}
                    {/remarksbox}
                {/if}
                {preference name='auth_ws_create_tiki'}
            </fieldset>
        {/tab}


        {tab name="{tr}Password Blacklist{/tr}"}
            <br>
            <fieldset>
                <legend class="h3">{tr}Password{/tr}</legend>

                {preference name=pass_blacklist_file}

                <legend class="h3">{tr}Password blacklist tools{/tr}</legend>

                <div class="mb-3 row">
                    <h3>{tr}Upload Word List for Processing{/tr}</h3>
                    <p>{tr}Words currently indexed:{/tr} {$num_indexed}</p>

                    <p>{tr}You may create custom blacklists to better fit your needs. Start by uploading a word list. Then reduce that list to something that applies to your specific configuration and needs with the tools that appear below.{/tr}</p>

                    <p>
                        {tr _0='<a href="https://github.com/danielmiessler/SecLists/tree/master/Passwords" target="_blank">' _1='</a>'}Raw password files can be obtained from %0Daniel Miessler's Collection%1.{/tr} {tr}Tiki's default password blacklist files were generated from Missler's top 1 million password file.{/tr}</p>

                        <input type="file" name="passwordlist" accept="text/plain" class="form-control mb-2" />
                        <div class="col-sm-4">
                            {tr}Use 'LOAD DATA INFILE':{/tr}
                            <input type="checkbox" name="loaddata" />
                            {help desc="{tr}Allows much larger files to be uploaded, but requires MySQL on localhost with extra permissions.{/tr}"}
                        </div>
                        <div class="col-sm-8">
                            <input
                                type="submit"
                                value="{tr}Create or Replace Word Index{/tr}"
                                name="uploadIndex"
                                class="btn btn-primary btn-sm"
                            >
                            {help desc="{tr}Text files with one word per line accepted.
                            The word list will be converted to all lower case. Duplicate entries will be removed.
                            Typically passwords lists should be arranged with the most commonly used passwords first.{/tr}"}
                            <input
                                type="submit"
                                value="{tr}Delete Temporary Index{/tr}"
                                name="deleteIndex"
                                class="btn btn-danger btn-sm"
                                onclick="confirmPopup('{tr}Delete temporary index?{/tr}')"
                            >
                            {help desc="{tr}It is recommended that you delete indexed passwords from your database after you're done generating your password lists.
                            They can take up quite a lot of space and serve no pourpose after processing is complete.{/tr}"}
                        </div>

                        <p>{tr}Blacklist Currently Using:{/tr} {$file_using}</p>
                        {if $num_indexed}
                            <h3>Generate and Save a Password Blacklist{help desc="{tr}Saving places a text file with the generated passwords in your storage/pass_blacklists folder and enables it
                            as an option for use. Fields default to the password standards set in tiki. You should not have to change these, unless you plan on changing your password
                            requirements in the future.{/tr}"}</h3>
                            {tr}Number of passwords (limit):{/tr} <input type="number" name="limit" value="{$limit}" />
                            {help desc="{tr}This sets the number of passwords that your blacklist will use. The words from the begining of of the file will be selected over the lower,
                                        so if you have a list of words arranged with the most common at the top, it will select only the most common works to blacklist.
                                        Typical usage ranges between 1,000 & 10,000, although many more could be used. Twitter blacklists 396.{/tr}"}<br>
                            {tr}Minimum Password Length:{/tr} <input type="number" name="length" value="{$length}" />
                            {help desc="{tr}The minimum password length for your password. This will filter out any password that has an illegal length.{/tr}"}<br>
                            {tr}Require Numbers &amp; Letters:{/tr} <input type="checkbox" name="charnum" {if $charnum}checked{/if} />
                            {help desc="{tr}If checked, will filter out any password that does not have both upper and lower case letters.{/tr}"}<br>
                            {tr}Require Special Characters:{/tr} <input type="checkbox" name="special" {if $special}checked{/if} />
                            {help desc="{tr}If checked, will filter out any passwords that do not have special characters.{/tr}"}<br>
                            <input
                                type="submit"
                                value="{tr}Save & Set as Default{/tr}"
                                name="saveblacklist"
                                class="btn btn-primary btn-sm"
                            >
                            <input
                                type="submit"
                                value="{tr}View Password List{/tr}"
                                name="viewblacklist"
                                class="btn btn-secondary btn-sm"
                                formtarget="_blank"
                            >
                        {/if}
                </div>
            </fieldset>
        {/tab}
        {tab name="{tr}OAuth Server Settings{/tr}"}
            <fieldset>
                {preference name=oauthserver_encryption_key}
                {preference name=oauthserver_public_key}
                {preference name=oauthserver_private_key}
            </fieldset>
        {/tab}
        {tab name="{tr}OpenId Connect{/tr}"}
            {if $prefs.auth_method ne 'openid_connect'}
                {remarksbox type="warning" title="{tr}Warning{/tr}" close="n"}
                {tr}You must change the Authentication Method to OpenId Connect for these changes to take effect{/tr}
                {/remarksbox}
            {/if}
            <fieldset>
                {preference name=openidconnect_name}
                {preference name=openidconnect_client_id}
                {preference name=openidconnect_client_secret}
                {preference name=openidconnect_issuer}
                {preference name=openidconnect_auth_url}
                {preference name=openidconnect_access_token_url}
                {preference name=openidconnect_details_url}
                {preference name=openidconnect_verify_method}
                {if $prefs.openidconnect_verify_method eq 'jwks'}
                    {preference name=openidconnect_jwks_url}
                {elseif $prefs.openidconnect_verify_method eq 'cert'}
                    {preference name=openidconnect_cert}
                {/if}
                {preference name=openidconnect_create_user_tiki}
            </fieldset>
        {/tab}
    {/tabset}

    {include file='admin/include_apply_bottom.tpl'}
</form>
