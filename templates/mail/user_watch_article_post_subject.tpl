{if $mail_action eq 'New'}{tr}New {$prefs.mail_template_custom_text}article post at{/tr}{/if}{if $mail_action eq 'Edit'}{tr}Edited {$prefs.mail_template_custom_text}article post at{/tr}{/if}{if $mail_action eq 'Delete'}{tr}Deleted {$prefs.mail_template_custom_text}article post at{/tr}{/if} %s {*get_string {tr}New article post at{/tr} *}
{*get_string {tr}Edited article post at{/tr} *}
{*get_string {tr}Deleted article post at{/tr} *}
