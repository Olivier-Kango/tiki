{* $Id$ *}

{title help="Webmail" admpage="webmail"}{tr}Webmail{/tr}{/title}

{include file='tiki-mytiki_bar.tpl'}

{$output_data}

{jq}
$('.draft_folder_select, .sent_folder_select, .trash_folder_select, .delete_folder_select, .rename_folder_select, .rename_parent_folder_select, .parent_folder_select, .archive_folder_select').attr('style','background-color: #ffff !important');
{/jq}
