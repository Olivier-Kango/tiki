{if $mail_action eq 'New'}{tr}New {$prefs.mail_template_custom_text}article post:{/tr}{/if}{if $mail_action eq 'Edit'}{tr}Edit {$prefs.mail_template_custom_text}article post:{/tr}{/if}{if $mail_action eq 'Delete'}{tr}Delete {$prefs.mail_template_custom_text}article post:{/tr}{/if} {tr}{$mail_title} by {$mail_user|username} at{/tr} {$mail_date|tiki_short_datetime:"":"n"}

{if $mail_action neq 'Delete'}{tr}View the article at:{/tr} {mailurl}{$mail_postid|sefurl:article}{/mailurl}{/if}


{$mail_title}


{$mail_current_data}


-----------------------------------------------------------
{tr}Publish Date:{/tr} {$mail_current_publish_date|tiki_short_datetime:"":"n"}
{tr}Expiration Date:{/tr} {$mail_current_expiration_date|tiki_short_datetime:"":"n"}

{if !empty($watchId)}{tr}If you don't want to receive these notifications follow this link:{/tr}
{mailurl}tiki-user_watches.php?id={$watchId}{/mailurl}{/if}

{if isset($mail_old_data)}

***********************************************************
{tr}The old article follows.{/tr}
***********************************************************
{tr}Title:{/tr} {$mail_old_title}

{tr}Publish Date:{/tr} {$mail_old_publish_date|tiki_short_datetime:"":"n"}
{tr}Expiration Date:{/tr} {$mail_old_expiration_date|tiki_short_datetime:"":"n"}
***********************************************************
{tr}Content{/tr}
***********************************************************
{$mail_old_data}
{/if}
