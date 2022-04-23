{* $Id$ *}
{tikimodule error=$module_error title=$tpl_module_title name=$tpl_module_name flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle type=$module_type}
    <button type="button" class="navbar-toggle" data-bs-toggle="collapse" data-bs-target="{$module_params.target}"{if $module_params.parent} data-bs-parent="{$module_params.parent}"{/if}>
        <span class="sr-only">{tr}Toggle navigation{/tr}</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
    </button>
{/tikimodule}
