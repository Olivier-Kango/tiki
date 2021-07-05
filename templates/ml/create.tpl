{extends "layout_view.tpl"}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="navigation"}
    <div class="form-group row">
        <a class="btn btn-link" href="{service controller=ml action=list}">{icon name=list} {tr}Manage{/tr}</a>
    </div>
{/block}

{block name="content"}
    <form method="post" action="{service controller=ml action=create}">
        <div class="form-group row">
            <label class="col-form-label col-sm-3">{tr}Name{/tr}</label>
            <div class="col-sm-9">
                <input class="form-control" type="text" name="name" required>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-form-label col-sm-3">{tr}Description{/tr}</label>
            <div class="col-sm-9">
                <textarea class="form-control" name="description"></textarea>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-form-label col-sm-3">{tr}Source tracker{/tr}</label>
            <div class="col-sm-9">
                {object_selector _class="form-control" type="tracker" _simplename="trackerId"}
            </div>
        </div>
        <div class="form-group row">
            <label class="col-form-label col-sm-3">
                {tr}Model template{/tr}
                {help url="Machine Learning Models" desc="Choose a predefined ML model designed to solve a common problem or start with a blank model."}
            </label>
            <div class="col-sm-9">
                <select class="form-control" name="template">
                    <option value="">{tr}Start with a blank model{/tr}</option>
                    <option value="MLT">{tr}More like this search{/tr}</option>
                </select>
            </div>
        </div>
        <div class="form-group submit">
            <div class="col-sm-9 offset-sm-3">
                <input type="submit" class="btn btn-primary" value="{tr}Create{/tr}">
            </div>
        </div>
    </form>
{/block}
