{* $Id$ *}
{* Use css menus as fallback for item dropdown action menu if javascript is not being used *}
{if $prefs.javascript_enabled !== 'y'}
	{$js = 'n'}
	{$libeg = '<li>'}
	{$liend = '</li>'}
{else}
	{$js = 'y'}
	{$libeg = ''}
	{$liend = ''}
{/if}
{title help="Scheduler Management" admpage="login" url="tiki-admin_schedulers.php"}{tr}Scheduler{/tr}{/title}
<div class="t_navbar margin-bottom-md">
	{if isset($schedulerinfo.id)}
		{button href="?add=1" class="btn btn-default" _text="{tr}Add a new Scheduler{/tr}"}
	{/if}

</div>
{tabset name='tabs_admin_schedulers'}

	{* ---------------------- tab with list -------------------- *}
{tab name="{tr}Scheduler{/tr}"}
	<h2>{tr}Schedulers{/tr}</h2>
	<form class="form-horizontal" name="checkform" id="checkform" method="post">
		<div id="admin_schedulers-div">
			<div class="{if $js === 'y'}table-responsive{/if} user-table ts-wrapperdiv">
				{* Use css menus as fallback for item dropdown action menu if javascript is not being used *}
				<table id="admin_schedulers" class="table normal table-striped table-hover" data-count="{$cant|escape}">
					<thead>
					<tr>

						<th>
							{tr}Name{/tr}
						</th>
						<th>
							{tr}Description{/tr}
						</th>
						<th>
							{tr}Task{/tr}
						</th>
						<th>
							{tr}Run Time{/tr}
						</th>
						<th>
							{tr}Status{/tr}
						</th>
						<th>
							{tr}Re-Run{/tr}
						</th>
						<th id="actions"></th>
					</tr>
					</thead>

					<tbody>
					{section name=scheduler loop=$schedulers}
						{capture assign=scheduler_name}{$schedulers[scheduler].name|escape}{/capture}
						<tr>
							<td class="scheduler_name">
								<a class="link tips"
								   href="tiki-admin_schedulers.php?scheduler={$schedulers[scheduler].id}{if $prefs.feature_tabs ne 'y'}#2{/if}"
								   title="{$scheduler_name}:{tr}Edit scheduler settings{/tr}">{$scheduler_name}</a>
							</td>
							<td class="scheduler_description">
								{$schedulers[scheduler].description|escape}
							</td>
							<td class="scheduler_task">
								{$schedulers[scheduler].task|escape}
							</td>
							<td class="scheduler_run_time">
								{$schedulers[scheduler].run_time|escape}
							</td>
							<td class="scheduler_status">
								{$schedulers[scheduler].status|escape}
							</td>
							<td class="scheduler_re_run">
								<input type="checkbox" {if $schedulers[scheduler].re_run}checked{/if} disabled>
							</td>
							<td class="action">
								{capture name=scheduler_actions}
									{strip}
										{$libeg}<a href="{query _type='relative' scheduler=$schedulers[scheduler].id}">
										{icon name="edit" _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
										</a>{$liend}
										{$libeg}<a href="{query _type='relative' scheduler=$schedulers[scheduler].id logs='1'}">
										{icon name="log" _menu_text='y' _menu_icon='y' alt="{tr}Logs{/tr}"}
										</a>{$liend}
										{$libeg}<a href="{bootstrap_modal controller=scheduler action=remove schedulerId=$schedulers[scheduler].id}">
										{icon name="remove" _menu_text='y' _menu_icon='y' alt="{tr}Delete{/tr}"}
										</a>{$liend}
									{/strip}
								{/capture}
								{if $js === 'n'}<ul class="cssmenu_horiz"><li>{/if}
										<a
												class="tips"
												title="{tr}Actions{/tr}" href="#"
												{if $js === 'y'}{popup fullhtml="1" center=true text=$smarty.capture.scheduler_actions|escape:"javascript"|escape:"html"}{/if}
												style="padding:0; margin:0; border:0"
										>
											{icon name='wrench'}
										</a>
										{if $js === 'n'}
										<ul class="dropdown-menu" role="menu">{$smarty.capture.scheduler_actions}</ul></li></ul>
								{/if}
							</td>
						</tr>
					{/section}
					</tbody>
				</table>
			</div>
		</div>
	</form>
{/tab}
	{* ---------------------- tab with form -------------------- *}
	<a id="tab2"></a>
{if isset($schedulerinfo.id) && $schedulerinfo.id}
	{capture assign=add_edit_scheduler_tablabel}{tr}Edit Scheduler{/tr}{/capture}
{else}
	{assign var=add_edit_scheduler_tablabel value="{tr}Add a new Scheduler{/tr}"}
{/if}

{tab name="{$add_edit_scheduler_tablabel}"}

{if isset($schedulerinfo.id) && $schedulerinfo.id}
	<h2>{tr}Edit Scheduler{/tr} {$schedulerinfo.name|escape}</h2>
{else}
	<h2>{tr}Add a new Scheduler{/tr}</h2>
{/if}
	<div class="row">
		<div class="col-md-offset-2 col-md-6">
			{remarksbox type="note" title="{tr}Information{/tr}"}
			{tr}Use CRON format to enter the values in "Run Time":
				<br>
				Minute, Hour, Day of Month, Month, Day of Week
				<br>
				Eg. every 5 minutes: */5 * * * *{/tr}
			{/remarksbox}
		</div>
	</div>
	<form class="form form-horizontal" action="tiki-admin_schedulers.php" method="post"
		  enctype="multipart/form-data" name="RegForm" autocomplete="off">
		<div class="form-group">
			<label class="col-sm-3 col-md-2 control-label" for="scheduler_name">{tr}Name{/tr}</label>
			<div class="col-sm-7 col-md-6">
				<input type="text" id='scheduler_name' class="form-control" name='scheduler_name'
					   value="{$schedulerinfo.name|escape}">
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-3 col-md-2 control-label" for="scheduler_description">{tr}Description{/tr}</label>
			<div class="col-sm-7 col-md-6">
				<input type="text" id='scheduler_description' class="form-control" name='scheduler_description'
					   value="{$schedulerinfo.description|escape}">
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-3 col-md-2 control-label" for="scheduler_task">{tr}Task{/tr}</label>
			<div class="col-sm-7 col-md-6">
				<select id="scheduler_task" name="scheduler_task" class="form-control">
					<option value='null'></option>
					{html_options options=$schedulerTasks selected=$schedulerinfo.task}
				</select>
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-3 col-md-2 control-label" for="scheduler_params">{tr}Parameters{/tr}</label>
			<div class="col-sm-7 col-md-6">
				<input type="text" id='scheduler_params' class="form-control" name='scheduler_params'
					   value="{$schedulerinfo.params|escape}">
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-3 col-md-2 control-label" for="scheduler_time">{tr}Run Time{/tr}</label>
			<div class="col-sm-7 col-md-6">
				<input type="text" id='scheduler_time' class="form-control" name='scheduler_time'
					   value="{$schedulerinfo.run_time|escape}">
			</div>
		</div>
		<div class="form-group">
			<label class="col-sm-3 col-md-2 control-label" for="scheduler_status">{tr}Status{/tr}</label>
			<div class="col-sm-7 col-md-6">
				<select id="scheduler_status" name="scheduler_status" class="form-control">
					schedulerStatus
					{html_options options=$schedulerStatus selected=$schedulerinfo.status}
				</select>
			</div>
		</div>

		<div class="form-group">
			<label class="col-sm-3 col-md-2 control-label" for="scheduler_catch">{tr}Run if missed{/tr}</label>
			<div class="col-sm-7 col-md-6">
				<input type="checkbox" id="scheduler_rerun" name="scheduler_rerun"
					   {if $schedulerinfo.re_run}checked{/if}>
			</div>
		</div>

		{if isset($schedulerinfo.id) && $schedulerinfo.id}
			<input type="hidden" name="scheduler" value="{$schedulerinfo.id|escape}">
			<input type="hidden" name="editscheduler" value="1">
			<input type="submit" class="btn btn-primary" name="save" value="{tr}Save{/tr}">
		{else}
			<input type="submit" class="btn btn-primary" name="new_scheduler" value="{tr}Add{/tr}">
		{/if}
	</form>
{/tab}
	<a id="tab3"></a>
{if isset($schedulerinfo.id) && $schedulerinfo.id}
	{tab name="{tr}Scheduler logs{/tr}"}
		<h2>{tr}Scheduler{/tr} {$schedulerinfo.name|escape} Logs</h2>
		<table class="table normal table-striped table-hover">
			<thead>
			<tr>
				<th>Start Time</th>
				<th>End Time</th>
				<th>Status</th>
				<th>Output</th>
			</tr>
			</thead>
			<tbody>
			{section name=run loop=$schedulerruns}
				<tr>
					<td>{$schedulerruns[run].start_time|date_format:"%b %e, %Y %H:%M:%S"}</td>
					<td>{$schedulerruns[run].end_time|date_format:"%b %e, %Y %H:%M:%S"}</td>
					<td>{$schedulerruns[run].status}</td>
					<td>{$schedulerruns[run].output|nl2br}</td>
				</tr>
			{/section}
			</tbody>
		</table>
	{/tab}
{/if}
{/tabset}
