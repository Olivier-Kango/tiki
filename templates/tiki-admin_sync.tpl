{title help="Sync Dev-Prod Servers" admpage="general"}{tr}Synchronize Dev/Prod Servers{/tr}{/title}
{remarksbox type="note" title="{tr}Note:{/tr}"}
    {tr}Use this tool if you have at least two different Tiki instances serving as development, staging or production instances. You can compare differences between Tiki configuration, wiki pages and their contents as well as tracker and field configurations. Especially useful when changes from a development server needs to be applied to production one. This tool will only show differences between instances, you will still have to manually apply the changes to the production one.{/tr}
{/remarksbox}
{remarksbox type="note" title="{tr}Setup instructions:{/tr}"}
    {tr}Remote Tiki needs API turned on and an API token generated from Admin->Security. Local Tiki needs an Admin DSN configured with the Bearer API token authorization header.{/tr}
{/remarksbox}
<form action="tiki-admin_sync.php" method="post">
    {ticket}
    <div class="tiki-form-group row">
        <label for="url" class="col-sm-3 col-form-label">{tr}Remote Server Address{/tr}</label>
        <div class="col-sm-9">
            <input type="text" maxlength="255" class="form-control" name="url" value="">
        </div>
    </div>
    <div class="tiki-form-group text-center">
        <input type="submit" class="btn btn-primary" name="submit" value="{tr}Show diff{/tr}">
    </div>
</form>

{if $diff}
<style>
.diff td {
    word-wrap: hard-wrap;
    max-width: 400px;
}
</style>
<div class="table-responsive">
    <table class="table diff">
        <tr>
            <th colspan="2"><b>{tr}Local{/tr}</b></th>
            <th colspan="2"><b>{tr}Remote{/tr}</b></th>
        </tr>
        {$diff}
    </table>
</div>
{/if}
