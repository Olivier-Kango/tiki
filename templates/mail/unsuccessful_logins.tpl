{$msg}
{tr}Please visit this {$prefs.mail_template_custom_text}link before logging in again:{/tr}
{mailurl}{$mail_link}?user={$user|escape:'url'}&pass={$mail_apass}{/mailurl}

{tr}Last attempt:{/tr} {tr}IP:{/tr} {$mail_ip}, {tr}User:{/tr} {$user}
