{tr}A {$prefs.mail_template_custom_text}category was updated:{/tr}

{tr}Updated by:{/tr} {$author|username}
{tr}Date:{/tr} {$mail_date|tiki_short_datetime:"":"n"}

{tr}Old:{/tr}
{tr}Name:{/tr} {$oldCategoryName}
{tr}Path:{/tr} {$oldCategoryPath}
{tr}Description:{/tr} {$oldDescription}

{tr}New:{/tr}
{tr}Name:{/tr} {$categoryName}
{tr}Path:{/tr} {$categoryPath}
{tr}Description:{/tr} {$description}

{mailurl}{$categoryId|sefurl:category}{/mailurl}
