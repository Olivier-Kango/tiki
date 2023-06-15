{tr _0=$prefs.mail_template_custom_text}A new file was posted to %0file gallery:{/tr} {$galleryName}

{tr}Posted by:{/tr} {$author|username}
{tr}Date:{/tr} {$mail_date|tiki_short_datetime:"":"n"}
{tr}Name:{/tr} {$fname}
{tr}File Name:{/tr} {$filename}
{tr}File Description:{/tr} {$fdescription}

{tr}You can download the new file at:{/tr}
{mailurl}{$galleryId|sefurl:'file gallery'}{/mailurl}
