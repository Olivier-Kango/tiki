{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title|escape}{/title}
{/block}

{block name="content"}
    <div class="customizer">
        {if in_array('colors', $sections)}{include file='templates/styleguide/sections/colors.tpl'}{/if}
        {if in_array('fonts', $sections)}{include file='templates/styleguide/sections/fonts.tpl'}{/if}
        {if in_array('headings', $sections)}{include file='templates/styleguide/sections/headings.tpl'}{/if}
        {if in_array('tables', $sections)}{include file='templates/styleguide/sections/tables.tpl'}{/if}
        {if in_array('buttons', $sections)}{include file='templates/styleguide/sections/buttons.tpl'}{/if}
        {if in_array('forms', $sections)}{include file='templates/styleguide/sections/forms.tpl'}{/if}
        {if in_array('lists', $sections)}{include file='templates/styleguide/sections/lists.tpl'}{/if}
        {if in_array('navbars', $sections)}{include file='templates/styleguide/sections/navbars.tpl'}{/if}
        {if in_array('dropdowns', $sections)}{include file='templates/styleguide/sections/dropdowns.tpl'}{/if}
        {if in_array('tabs', $sections)}{include file='templates/styleguide/sections/tabs.tpl'}{/if}
        {if in_array('alerts', $sections)}{include file='templates/styleguide/sections/alerts.tpl'}{/if}
        {if in_array('icons', $sections)}{include file='templates/styleguide/sections/icons.tpl'}{/if}
    </div>

    <div class="col">
        <label for="header_custom_css">{tr}Custom CSS{/tr}</label>
        <textarea name="header_custom_css" class="form-control" rows="10"></textarea>
    </div>

    <div class="tc-footer">
        <div class="container">

            <div class="footer-ui">
                <button id="generate-custom-css" class="btn btn-primary generate-custom-css">
                    {tr}Generate custom CSS{/tr}
                </button>
                <label><input class="keep-changes form-check-input" type="checkbox"><span>{tr}Keep changes after refresh{/tr}</span></label>
            </div>

            <div class="btn-group dropup">
                <a id="dLabel" data-bs-target="#" href="" data-bs-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                    Select a section:
                </a>
                <div class="dropdown-menu" aria-labelledby="dLabel">
                </div>
            </div>
        </div>
    </div>
{/block}
