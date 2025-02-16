{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title|escape}{/title}
{/block}

{block name="content"}
    <form method="post" action="{service controller=workspace action=add_template}" class="form" role="form">
        <div class="mb-3 row">
            <label for="name" class="col-form-label">
                {tr}Name{/tr}
            </label>
            <input type="text" name="name" class="form-control">
        </div>
        <div class="submit">
            <input type="submit" class="btn btn-primary" value="{tr}Create{/tr}">
        </div>
    </form>
{/block}
