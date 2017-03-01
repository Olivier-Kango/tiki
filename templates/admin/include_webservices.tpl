{* $ID:$ *}
<form method="post" action="tiki-admin.php?page=webservices" class="form-horizontal">
	<input type="hidden" name="ticket" value="{$ticket|escape}">

	<div class="row">
		<div class="form-group col-lg-12 clearfix">
			<div class="pull-right">
				<input type="submit" class="btn btn-primary btn-sm" title="{tr}Apply Changes{/tr}" value="{tr}Apply{/tr}">
			</div>
		</div>
	</div>

	<fieldset>
		<legend>{tr}Activate the feature{/tr}</legend>
		{preference name=feature_webservices visible="always"}
	</fieldset>

	<div class="t_navbar margin-bottom-md">
		{foreach from=$webservices item=name}
			{button href="tiki-admin.php?page=webservices&amp;name=$name" class="btn btn-default" _text=$name}
		{/foreach}
		{if $storedName}
			{button href="tiki-admin.php?page=webservices" class="btn btn-default" _text="{tr}Create New{/tr}"}
		{/if}
	</div>

	{if $storedName}
		<p><strong>{$storedName|escape}</strong>: {$url|escape}<input type="hidden" name="name" value="{$storedName|escape}"/> <a href="tiki-admin.php?page=webservices&amp;name={$storedName|escape}&amp;delete">{icon name='delete' iclass='tips' title=":{tr}Delete{/tr}"}</a></p>
	{else}
		{remarksbox type="tip" title="{tr}Tip{/tr}"}
			{tr}Enter the URL of a web services returning either JSON or YAML. Parameters can be specified by enclosing a name between percentage signs. For example: %name%. %service% and %template% are reserved keywords and cannot be used.{/tr}
		{/remarksbox}
		<p>{tr}URL:{/tr}<input type="text" name="url" size="75" value="{$url|escape}" class="form-control"/></p>
		<p>
			{tr}Type:{/tr}
			<select name="wstype">
				{foreach from=$webservicesTypes item=_type}
					<option value="{$_type}"{if $wstype eq $_type} selected="selected"{/if}>{$_type}</option>
				{/foreach}
			</select>
		</p>
		<p id="ws_postbody">
			<label class="col-sm-4"> {tr}Body of POST request{/tr}</label>
			<div class="col-sm-8">
				<textarea name="postbody" class="form-control">{$postbody|escape}</textarea><br>
				{tr}Parameters (%name%):{/tr}
			</div>
		</p>
		<p id="ws_operation" style="display: none;">{tr}Operation:{/tr}<input type="text" name="operation" size="30" value="{$operation|escape}" class="form-control"/></p>
		<p><input type="submit" class="btn btn-default btn-sm" name="parse" value="{tr}Lookup{/tr}"/></p>
	{/if}
	{if $url}
		<h3>{tr}Parameters{/tr}</h3>
			{if $params|@count}
				{foreach from=$params key=name item=value}
					<div class="form-group">
						<label>{$name|escape}
							<input type="text" name="params[{$name|escape}]" value="{$value|escape}" class="form-control"/>
						</label>
					</div>
				{/foreach}
			{else}
				<div>{tr}{$url} requires no parameter.{/tr}</div>
			{/if}
			<div class="form-group">
				<input type="submit" class="btn btn-default btn-sm" name="test" value="{tr}Test Input{/tr}" />
			</div>
	{/if}
	{if $data}
		<h3>{tr}Response Information{/tr}</h3>
		<div class="table-responsive">
			<table class="table">
				<tr>
					<th>{tr}OIntegrate Version{/tr}</th>
					<td>{if $response->version}{$response->version|escape}{else}<em>{tr}Not supported{/tr}</em>{/if}
				</tr>
				<tr>
					<th>{tr}Schema Version{/tr}</th>
					<td>{if $response->schemaVersion}{$response->schemaVersion|escape}{else}<em>{tr}Not supported{/tr}</em>{/if}
				</tr>
				<tr>
					<th>{tr}Schema Documentation{/tr}</th>
					<td>{if $response->schemaDocumentation}<a href="{$response->schemaDocumentation|escape}">{tr}Available{/tr}</a>{else}<em>{tr}Not supported{/tr}</em>{/if}
				</tr>
				<tr>
					<th>{tr}Cache{/tr}</th>
					<td>{if $response->cacheControl}{$response->cacheControl->getFieldValue()|escape}{else}<em>{tr}Not specified, default used{/tr}</em>{/if}
				</tr>
				<tr>
					<th>{tr}Content Type{/tr}</th>
					<td>{if $response->contentType}{$response->contentType->getMediaType()|escape} ({$response->contentType->getCharset()|escape}){else}<strong>{tr}Not specified{/tr}</strong>{/if}
				</tr>
				<tr>
					<th colspan="2">{tr}Returned Data{/tr}</th>
				</tr>
				<tr>
					<td colspan="2"><pre style="max-height: 40em; overflow: auto; white-space: pre-wrap">{$data|escape}</pre></td>
				</tr>
				<tr>
					<th colspan="2">{tr}Proposed Templates{/tr}</th>
				</tr>
				{foreach from=$templates item=template key=number}
					<tr>
						<th>
							{$template.engine|escape}/{$template.output|escape}
							<input type="submit" class="btn btn-default btn-sm" name="add[{$number}]" value="{tr}Add{/tr}"/>
						</th>
						<td><pre>{$template.content|escape}</pre></td>
					</tr>
				{foreachelse}
					<tr>
						<th>{tr}None{/tr}</th>
					</tr>
				{/foreach}
			</table>
		</div>
		{if ! $storedName}
			<p>{tr}Register this web service. It will be possible to register the templates afterwards. Service name must only contain letters.{/tr}</p>
			<p>
				<input type="text" name="new_name"  class="form-control"/>
				<input type="submit" class="btn btn-default btn-sm" name="register" value="{tr}Register Service{/tr}" />
			</p>
		{else}
			<h3>{tr}Registered Templates{/tr}</h3>
			<div class="table-responsive">
				<table>
					<tr>
						<th style="width: 25%">{tr}Name{/tr}</th>
						<th style="width: 25%">{tr}Engine{/tr}</th>
						<th style="width: 25%">{tr}Output{/tr}</th>
						<th style="width: 25%">{tr}Preview{/tr}</th>
					</tr>
					{foreach from=$storedTemplates item=template}
						<tr>
							<td>
								<input type="submit" class="btn btn-default btn-sm" name="loadtemplate" value="{$template->name|escape}"/>
								<a href="tiki-admin.php?page=webservices&amp;name={$storedName|escape}&amp;delete={$template->name|escape}">{icon name='delete' iclass='tips' title=":{tr}Delete{/tr}"}</a>
							</td>
							<td>{$template->engine|escape}</td>
							<td>{$template->output|escape}</td>
							<td><input type="submit" class="btn btn-default btn-sm" name="preview" value="{$template->name|escape}"/></td>
						</tr>
						<tr><td colspan="4"><pre style="max-height: 30em; overflow: auto; white-space: pre-wrap">{$template->content|escape}</pre></td></tr>
						{if $preview eq $template->name}
							<tr><td colspan="4">{$preview_output}</td></tr>
						{/if}
					{/foreach}
					<tr>
						<td style="padding: 0 .5em"><input type="text" name="nt_name" value="{$nt_name|escape}" class="form-control"/></td>
						<td style="padding: 0 .5em">
							<select id="nt_engine" name="nt_engine" class="form-control">
								<option value=""></option>
								<option value="javascript" {if $nt_engine eq 'javascript'} selected="selected"{/if}>JavaScript</option>
								<option value="smarty"{if $nt_engine eq 'smarty'} selected="selected"{/if}>Smarty</option>
								<option value="index"{if $nt_engine eq 'index'} selected="selected"{/if}>Index</option>
							</select>
						</td>
						<td style="padding: 0 .5em" colspan="2">
							<select id="nt_output" name="nt_output" class="form-control">
								<option value=""></option>
								<option value="html" {if $nt_output eq 'html'} selected="selected"{/if}>HTML</option>
								<option value="tikiwiki"{if $nt_output eq 'tikiwiki'} selected="selected"{/if}>Wiki</option>
								<option value="index"{if $nt_output eq 'index'} selected="selected"{/if}>Index</option>
								<option value="mindex"{if $nt_output eq 'mindex'} selected="selected"{/if}>Multi-Index</option>
							</select>
						</td>
					</tr>
					<tr><td colspan="4"><textarea name="nt_content" rows="10" class="form-control">{$nt_content|escape}</textarea></td></tr>
					<tr><td colspan="4"><input type="submit" class="btn btn-default btn-sm" name="create_template" value="{tr}Register Template{/tr}"/></td></tr>
				</table>
			</div>
		{/if}
	{/if}

	<div class="row">
		<div class="form-group col-lg-12 clearfix">
			<div class="text-center">
				<input type="submit" class="btn btn-primary btn-sm" title="{tr}Apply Changes{/tr}" value="{tr}Apply{/tr}">
			</div>
		</div>
	</div>
</form>
