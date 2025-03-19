{strip}
    {if $prefs.cookie_consent_mode eq 'dialog'}
    <div class="modal" tabindex="-1" role="dialog" id="{$prefs.cookie_consent_dom_id}">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-black">{tr}Cookie Consent{/tr}</h5>
                    </div>
                    <div class="modal-body">
             {else}
                  <div id="{$prefs.cookie_consent_dom_id}" class="alert alert-primary col-auto mx-auto" role="alert"
            {if not empty($prefs.cookie_consent_mode)}
                style="display:none;" class="{$prefs.cookie_consent_mode}"
            {/if}
        >
    {/if}
       <form method="POST">
            <div class="row mx-0">
                <div class="col-12 mb-3">
                    <p class="text-muted small">{wiki}{tr}{$prefs.cookie_consent_description}{/tr}{/wiki}</p>
                </div>
                <div class="col-md-7 d-flex flex-wrap justify-content-between mb-3">
                    {foreach from=$cookie_categories key=category item=data}
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="toggle{$category|capitalize}" name="cookie_consent_{$category}" {if $category == 'essential'}checked disabled{/if}>
                            <label class="form-check-label text-black" for="toggle{$category|capitalize}">
                                {tr}{$data.name}{/tr}
                            </label>
                            <span class="ms-2 text-black" data-bs-toggle="tooltip" data-bs-placement="right" title="{tr}{$data.description}{/tr}">
                                <i class="icon icon-help fas fa-question-circle"></i>
                            </span>
                        </div>
                    {/foreach}
                </div>
            </div>
            <div class="row mx-0">
                <div class="col-md-6 d-flex flex-wrap gap-2 justify-content-center">
                    <button type="button" class="btn btn-outline-secondary flex-grow-1" id="cookie_save_button">{tr}Save{/tr}</button>
                    {{if $prefs.cookie_consent_disable eq 'n'}}
                        <button type="button" class="btn btn-outline-danger flex-grow-1" id="cookie_decline_unnecessary_button">{tr}Refuse unnecessary{/tr}</button>
                    {{/if}}
                    <input type="submit" class="btn btn-success flex-grow-1" id="cookie_consent_preference" name="cookie_consent_preference" value="{tr}Accept All{/tr}">
                </div>
            </div>
        </form>
    {if $prefs.cookie_consent_mode eq 'dialog'}
        </div></div></div>
    {/if}
    </div>
{/strip}
