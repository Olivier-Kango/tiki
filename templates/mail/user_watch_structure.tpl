{if $action eq 'add'}
{tr _0=$prefs.mail_template_custom_text}A page has been added to your watched %0sub-structure:{/tr}
{$name}
{mailurl}tiki-index.php?page_ref_id={$page_ref_id}{/mailurl}
{elseif $action eq 'remove'}
{tr _0=$prefs.mail_template_custom_text}A page has been removed from your watched %0sub-structure:{/tr}
{$name}
{elseif $action eq 'move_up'}
{tr _0=$prefs.mail_template_custom_text}A page has been promoted in your watched %0sub-structure:{/tr}
{mailurl}tiki-index.php?page_ref_id={$page_ref_id}{/mailurl}
{elseif $action eq 'move_down'}
{tr _0=$prefs.mail_template_custom_text}A page has been demoted in your watched %0structure:{/tr}
{mailurl}tiki-index.php?page_ref_id={$page_ref_id}{/mailurl}
{/if}
