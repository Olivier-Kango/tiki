{* $Id$ *}
{extends 'layout_edit.tpl'}

{block name=content}
	{if $tiki_p_edit == 'y'}
        {title help="http://doc.tiki.org/LIST+-+Troubleshooting+The+List+Plugin" url=''}{tr}Experiment with plugin LIST{/tr}{/title}
		<form method="post" class="form-horizontal">
			<div class="row">
				<div class="col">
					<div class="form-group row">
						<div class="row">
							<div class="col">
								<label for="editwiki">Plugin LIST content:</label>
							</div>
						</div>
						<textarea class="form-control" rows="5" name="editwiki" id="editwiki">{$listtext}</textarea>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-3"></div>
				<div class="col-sm-9">
					<input class="btn btn-secondary " type="submit" name="quickedit" value="{tr}Test Plugin LIST{/tr}">
				</div>
			</div>
		</form>
		<div class="row">
			<div class="col-sm-12">
				<hr>
				<div class="preview_contents">
					{$listparsed}
				</div>
				<hr>
			</div>
		</div>
	{/if}
{/block}
