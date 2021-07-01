// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

$(document).ready(function() {

	var tocDirty = false;

	var setupStructure = function() {
		var sortableOptions = {
			group: {
				name: 'shared',
			},
			dataIdAttr: 'data-id',
			ghostClass: 'draggable-background',
			animation: 500,
			// invertSwap: true,
			swapThreshold: 1,
			direction: 'vertical',
			fallbackOnBody: true,
			// Called when dragging element changes position
			onAdd: function(event) {
				var pageName = $(event.item).data('page-name');
				if (!jqueryTiki.structurePageRepeat && $(`.structure-container li .link:contains(${pageName})`).length > 0) {
					$.getJSON($.service('object', 'report_error', {message:tr("Page only allowed once in a structure")}));
					$(event.item).remove();
				}
			},
			onEnd: function(event) {
				if ($(".save_structure:visible").length === 0) {
					$(".save_structure").show("fast").parent().show("fast");
					tocDirty = true;
				}
			}
		};

		document.querySelectorAll('.admintoc').forEach(function(el) {
			new Sortable(el, sortableOptions);
		});

	// 	$(".admintoc:first").nestedSortable({

	// 		disableNesting:'no-nest',
	// 		forcePlaceholderSize:true,
	// 		handle:'div',
	// 		helper:'clone',
	// 		items:'li',
	// 		//maxLevels: 3,
	// 		opacity:.6,
	// 		tabSize:20,
	// 		tolerance:'pointer',
	// 		toleranceElement:'> div',
	// 		placeholder:"ui-state-highlight",
	// 		rootID:"root",
	// 		connectWith:"#page_list_container",

	// 		stop:function (event, ui) {
	// 			if ($(".save_structure:visible").length === 0) {
	// 				$(".save_structure").show("fast").parent().show("fast");
	// 				tocDirty = true;
	// 			}
	// //			$(this).removeClass("ui-state-active");
	// 		},
	// 		start:function (event, ui) {
	// //			$(this).addClass("ui-state-active");
	// 		}
	// 	}).droppable({
	// 				hoverClass:"ui-state-active",
	// 				drop:function (event, ui) {
	// 					if (!$(ui.draggable).hasClass("admintoclevel")) {
	// 						var pageName = $.trim($(ui.draggable).text());
	// 						if (!jqueryTiki.structurePageRepeat && $("li:contains('" + $.trim($(ui.draggable).text()) + "')", this).length) {
	// 							$.getJSON($.service('object', 'report_error', {message:tr("Page only allowed once in a structure")}), function () {
	// 								$("#tikifeedback").css({
	// 									position: "absolute",
	// 									top: ui.position.top,
	// 									left: ui.position.left,
	// 									width: "40em"
	// 								});
	// 							});
	// 						} else {
	// 							$(this).append(
	// 									$('<li class="ui-state-default admintoclevel new"><div>' +
	// 											'<div class="actions"><input type="text" class="page-alias-input" value="" placeholder="Page alias..."></div>' +
	// 											pageName + '</div></li>')
	// 							);
	// 							$(".save_structure").show("fast").parent().show("fast");
	// 							tocDirty = true;
	// 						}
	// 					}
	// 				}
	// 			})
	// 			.disableSelection();

		$(".page-alias-input").on("change", function () {
			$(".save_structure").show("fast").parent().show("fast");
			tocDirty = true;
		}).on("click", function () {	// for Firefox
			$(this).focus().selection($(this).val().length);
		});

		var sortableListOptions = {
			group: {
				name: 'shared',
				pull: 'clone',
				put: false // Do not allow items to be put into this list
			},
			sort: false,
			animation: 500,
			onEnd: function(event) {
				var pageName = $(event.item).data('page-name');

				if ($(event.to).closest('.structure-container').length > 0) {
					$(event.item).text('');
					$(event.item).removeClass('ui-state-default').addClass('row admintoclevel new').append(`
						<div class="col-sm-12">
							<label>${pageName}</label>
							<div class="actions input-group input-group-sm mb-2"><input type="text" class="page-alias-input form-control" value="" placeholder="Page alias..."></div>
						</div>
						<div class="col-sm-12">
							<ol class="admintoc"></ol>
						</div>
					`);
					new Sortable($(event.item).find('.admintoc')[0], sortableOptions);

					$(".save_structure").show("fast").parent().show("fast");
					tocDirty = true;
				}
			}
		};

		new Sortable(document.querySelector('#page_list_container'), sortableListOptions);

		// $("li", "#page_list_container").each(function () {
		// 	var el = this;
		// 	$(this).draggable({
		// 		connectToSortable:".admintoc:first",
		// 		revert:"invalid",
		// 		helper:"clone",
		// 		start:function (event, ui) {
		// 			$(this).css("z-index", 1000);
		// 		},
		// 		stop:function (event, ui) {
		// 			$("#save_structure").show("fast").data("dragged", $(el).attr("id"));
		// 			$(this).css("z-index", "auto");
		// 		}
		// 	}).disableSelection();
		// });
	};

	$(window).on("beforeunload", function() {
		if (tocDirty) {
			return tr("You have unsaved changes to your structure, are you sure you want to leave the page without saving?");
		}
	});

	setupStructure();

	$(".save_structure").click(function(){

		var $sortable = $(this).parent().find(".admintoc:first");
		$sortable.tikiModal(tr("Saving..."));

		var fakeId = 1000000;
		$(".admintoclevel.new").each(function() {
			$(this).attr("id", "node_" + fakeId);
			$(this).data("id", fakeId);
			fakeId++;
		});
		// Adjusted to previous nestedSortable plugin result array
		var arr = [{
			item_id: 'root',
			parent_id: 'none',
			structure_id: $sortable.data("params").page_ref_id,
			depth: 0
		}];

		$sortable.find('li.admintoclevel').each(function() {
			var parentId = $(this).parent().closest('li.admintoclevel').data('id');
			var itemId = $(this).data('id');
			var pageAlias = $(this).find('.page-alias-input').val();
			var structureId = $sortable.data("params").page_ref_id;
			var pageName = $(this).find('> div').text().trim();

			var obj = {
				item_id: itemId,
				parent_id: parentId || 'root',
				structure_id: structureId,
				page_name: pageName,
				page_alias: pageAlias,
				depth: 1
				// el: $(this)[0] // Debug only
			};

			let item = arr.find(el => el.parent_id === parentId);
			if (!parentId) {
				obj.depth = 1;
			} else if (item) {
				obj.depth = item.depth;
			} else {
				obj.depth = arr[arr.length - 1].depth + 1;
			}

			arr.push(obj);
		});

		// console.log(arr, $sortable.data("params"))

		$.post($.service("wiki_structure", "save_structure"), {data: $.toJSON(arr), params: $.toJSON($sortable.data("params"))}, function (data) {
			$sortable.tikiModal();
			if (data) {
				$sortable.replaceWith(data.html);
				setupStructure();
				$(".save_structure").hide();
				tocDirty = false;
			}
		}, "json");
		return false;
	});

	// $(".save_structure").click(function(){

	// 	var $sortable = $(this).parent().find(".admintoc:first");
	// 	$sortable.tikiModal(tr("Saving..."));

	// 	var fakeId = 1000000;
	// 	$(".admintoclevel.new").each(function() {
	// 		$(this).attr("id", "node_" + fakeId);
	// 		fakeId++;
	// 	});

	// 	var ary = $sortable.nestedSortable('toArray', {startDepthCount: 0, listType:"ol"});

	// 	for (var i = 0; i < ary.length; i++ ) {
	// 		if (ary[i]["item_id"] != "root") {
	// 			ary[i]["page_alias"] = $(".page-alias-input", "#node_" + ary[i]["item_id"]).val();
	// 			if (ary[i]["item_id"] >= 1000000) {		// new pages
	// 				ary[i]["page_name"] = $("#node_" + ary[i]["item_id"] + " > div").text();
	// 			}
	// 		} else {
	// 			ary[i]["structure_id"] = $sortable.data("params").page_ref_id;
	// 		}
	// 	}


	// 	$.post($.service("wiki_structure", "save_structure"), {data: $.toJSON(ary), params: $.toJSON($sortable.data("params"))}, function (data) {
	// 		$sortable.tikiModal();
	// 		if (data) {
	// 			$sortable.replaceWith(data.html);
	// 			setupStructure();
	// 			$(".save_structure").hide();
	// 			tocDirty = false;
	// 		}
	// 	}, "json");
	// 	return false;
	// });

});

function movePageToStructure(element) {
	var id = $(element).parents(".admintoclevel:first").attr("id").match(/\d*$/);
	if (id) {
		id = id[0];
	}
	$("input[name=page_ref_id]", "#move_dialog").val(id);
	$("#move_dialog").dialog({
		title: tr("Move page")
	});
}

function addNewPage(element) {
	var id = $(element).parents(".admintoclevel:first").attr("id").match(/\d*$/);
	if (id) {
		id = id[0];
	}
	$("input[name=page_ref_id]", "#newpage_dialog").val(id);

	$("#newpage_dialog").dialog({
		title: tr("Add page")

	});
}

