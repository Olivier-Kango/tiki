<div class="dropdowns">
    <h2>{tr}Dropdowns{/tr}</h2>
    <div class="row">
        <div class="col-sm-8 col-md-9">
            <div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenu1" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    {tr}Dropdown{/tr}
                </button>
                <div class="dropdown-menu" aria-labelledby="dropdownMenu1">
                    <a class="dropdown-item" href="javascript:void(0);">{tr}Action{/tr}</a>
                    <a class="dropdown-item" href="javascript:void(0);">{tr}Another action{/tr}</a>
                    <a class="dropdown-item" href="javascript:void(0);">{tr}Something else here{/tr}</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="javascript:void(0);">{tr}Separated link{/tr}</a>
                </div>
            </div>
        </div>

        <div class="col-sm-4 col-md-3">
            <div class="input">
                <p class="picker" data-selector=".dropdown-menu" data-element="background-color">
                    <label for="tc-dropdown-bg-color">{tr}Background:{/tr}</label>
                    <input id="tc-dropdown-bg-color" data-selector=".dropdown-menu" data-element="background-color" data-var="@dropdown-bg" type="text">
                    <span class="input-group-addon"><i></i></span>
                </p>
                <p class="picker" data-selector=".dropdown-menu .dropdown-item" data-element="color">
                    <label for="tc-dropdown-text-color">{tr}Text color:{/tr}</label>
                    <input id="tc-dropdown-text-color" data-selector=".dropdown-menu .dropdown-item" data-element="color" data-var="@dropdown-link-color" type="text">
                    <span class="input-group-addon"><i></i></span>
                </p>
                <p>
                    <label for="tc-dropdown-border-radius">{tr}Border radius:{/tr}</label>
                    <input id="tc-dropdown-border-radius" class="nocolor" data-selector=".dropdown-menu" data-element="border-radius" type="text">
                </p>
            </div>
        </div>
    </div>
</div>
