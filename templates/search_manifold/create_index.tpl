{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
    <form method="post" action="{service controller=search_manifold action=create_index}">
        <div class="mb-3 row">
            <label for="index" class="col-form-label col-sm-3">{tr}Index{/tr}</label>
            <div class="col-sm-9">
                <input type="text" class="form-control" name="index" value="{$index|escape}"/>
            </div>
        </div>
        <div class="mb-3 row">
            <label for="type" class="col-form-label col-sm-3">{tr}Type{/tr}</label>
            <div class="col-sm-9">
                <input type="text" class="form-control" name="type" value="{$type|escape}"/>
            </div>
        </div>
        <div class="mb-3 row">
            <label for="location" class="col-form-label col-sm-3">{tr}Location{/tr}</label>
            <div class="col-sm-9">
                <input type="text" class="form-control" name="location" value="{$location|escape}"/>
                <div class="form-text">
                    {tr}If you want this index to be on a different cluster, connected as a tribe node, you need to enter the primary cluster location here. Indices cannot be created on tribe nodes.{/tr}
                </div>
            </div>
        </div>
        <div class="mb-3 row">
            <div class="col-sm-9 offset-sm-3">
                <input class="btn btn-primary" type="submit" value="{tr}Create Index{/tr}"/>
            </div>
        </div>
    </form>
{/block}
