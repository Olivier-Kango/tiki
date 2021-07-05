{tikimodule error=$module_params.error title=$tpl_module_title name='search_last_rebuild_stats' flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}
    {service_inline controller='search' action='rebuild' getlaststats=1}
{/tikimodule}
