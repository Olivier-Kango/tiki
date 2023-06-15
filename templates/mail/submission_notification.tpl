{tr}A new {$prefs.mail_template_custom_text}article was submitted by {$mail_user|username} to {$mail_site} at {$mail_date|tiki_short_datetime:"":"n"}{/tr}

{tr}You can edit the submission following this link:{/tr} {mailurl}tiki-edit_submission.php?subId={$mail_subId}{/mailurl}

{tr}Title:{/tr} {$mail_title}

{tr}Heading:{/tr}
{$mail_heading}

{tr}Body:{/tr}
{$mail_body}
