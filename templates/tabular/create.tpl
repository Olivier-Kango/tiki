{extends "layout_view.tpl"}

{block name="title"}
	{title}{$title}{/title}
{/block}

{block name="navigation"}
	<div class="form-group row">
		{permission name=admin_trackers}
			<a class="btn btn-link" href="{service controller=tabular action=manage}">{icon name=list} {tr}Manage{/tr}</a>
		{/permission}
	</div>
{/block}

{block name="content"}
	<form method="post" action="{service controller=tabular action=create}">
		<div class="form-group row">
			<label class="col-form-label col-sm-3">{tr}Name{/tr}</label>
			<div class="col-sm-9">
				<input class="form-control" type="text" name="name" required>
			</div>
		</div>
		<div class="form-group row">
			<label class="col-form-label col-sm-3">{tr}Tracker{/tr}</label>
			<div class="col-sm-9">
				{object_selector _class="form-control" type="tracker" _simplename="trackerId"}
			</div>
		</div>
		<div class="form-group row mb-4">
			<label class="form-check-label col-sm-3">{tr}Initializite this format with the current tracker fields{/tr}</label>
			<div class="col-sm-9">
				<div class="form-check">
					<input class="form-check-input" type="checkbox" name="prefill">
				</div>
			</div>
		</div>
		{if $has_odbc}
		<div class="form-group row mb-4">
			<label class="form-check-label col-sm-3">{tr}External ODBC source?{/tr}</label>
			<div class="col-sm-9">
				<div class="form-check">
					<input class="form-check-input use-odbc" type="checkbox" name="use_odbc" value="1">
				</div>
			</div>
		</div>
		<div class="odbc-container" style="display: none">
			<div class="form-group row">
				<label class="col-form-label col-sm-2 offset-sm-1">{tr}DSN{/tr}</label>
				<div class="col-sm-9">
					<input class="form-control" type="text" name="odbc[dsn]">
				</div>
			</div>
			<div class="form-group row">
				<label class="col-form-label col-sm-2 offset-sm-1">{tr}User{/tr}</label>
				<div class="col-sm-9">
					<input class="form-control" type="text" name="odbc[user]">
				</div>
			</div>
			<div class="form-group row">
				<label class="col-form-label col-sm-2 offset-sm-1">{tr}Password{/tr}</label>
				<div class="col-sm-9">
					<input class="form-control" type="text" name="odbc[password]">
				</div>
			</div>
			<div class="form-group row">
				<label class="col-form-label col-sm-2 offset-sm-1">{tr}Table/Schema{/tr}</label>
				<div class="col-sm-9">
					<input class="form-control" type="text" name="odbc[table]">
				</div>
			</div>
			<div class="form-group row">
				<label class="form-check-label col-sm-2 offset-sm-1">{tr}Initialize with remote schema fields{/tr}</label>
				<div class="col-sm-9">
					<div class="form-check">
						<input class="form-check-input" type="checkbox" name="prefill_odbc" value="1">
						<a class="tikihelp text-info" title="{tr}Remote initialization{/tr}: {tr}Create missing fields in related tracker and in this tabular format from remote schema.{/tr}">
							{icon name=information}
						</a>
					</div>
				</div>
			</div>
		</div>
		{/if}
		<div class="form-group submit">
			<div class="col-sm-9 offset-sm-3">
				<input type="submit" class="btn btn-primary" value="{tr}Create{/tr}">
			</div>
		</div>
	</form>
{/block}
