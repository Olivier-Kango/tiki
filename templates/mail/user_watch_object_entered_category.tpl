{tr _0=$prefs.mail_template_custom_text}An object was added to %0category{/tr} {$categoryName}

{tr}Added by:{/tr} {$author|username}
{tr}Date:{/tr} {$mail_date|tiki_short_datetime:"":"n"}
{tr}Name:{/tr} {$categoryName}
{tr}Path:{/tr} {$categoryPath}
{mailurl}{$categoryId|sefurl:category}{/mailurl}

{tr}Object:{/tr} {$objectName}
{tr}Object type:{/tr} {$objectType}
{mailurl}{$objectUrl}{/mailurl}
