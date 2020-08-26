/* (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
 *
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
 *
 * $Id$ 
 *
 * Rewritten for bootstrap tiki 15.x 2015-2016
 * Based on work by Jobi Carter keacarterdev@gmail.com
 */

$.genAutoToc = function () {
	var $page = $("body"),
	$top = $("#top"), $row;
	var $page_data = $("#page-data").addClass("col-md-7 clearfix");
	var toc = "";
	var start = 0;

	//if a wiki page, and div and not printing
	if ($top.length && location.href.indexOf("tiki-print.php") == -1) {
		var container = document.querySelector(container) || document.querySelector('#page-data');
		var children = container.children;

		if (countHeadings(children) > 0) {
			$row = $("<div class='row'/>");
			$container = $("<div class='container'/>");

			//div for nav
			var $tocNav = $("<div id='all-toc' class='col-md-5 hidden'/>");
			var $nav = $("<nav id='auto-toc'>");

			//create object to store processed IDs.
			var processedId = {};

			//function to process header Id generation. If an ID which has been processed is generated again and passed in again, the id name will be incremented to id_[1*]
			function processId(id) {
				if (id in processedId) {
					//if processed before
					//iterate count for header with this ane
					processedId[id] += 1;
					//set the new id to id plus count for header
					var newId = id + "_" + processedId[id];
				} else {
					//if not processed before
					//add to "dictionary' with count of 0
					processedId[id] = 0;
					//return id passed in
					newId = id;
				}
				return newId;
			}

			for (var i = 0; i < children.length; i++) {
				var isHeading = children[i].nodeName.match(/^H\d+$/);
				if (isHeading) {
					var level = children[i].nodeName.substr(1);

					var headerText = (children[i].textContent);
					//generate and set id if necessary (if element does not already have an id, create one)
					var id = children[i].getAttribute("id");

					if (!id) {
						id = processId(aText.replace(/\W/g, "_"));
					} else {
						id = id.replace(":", "\\:").replace(".", "\\.").replace("#", "\\#");
					}
					//set the element's id to the constructed ID
					children[i].setAttribute("id", id);
					//construct the anchor URL with chars jquery doesn't like escaped
					var url = "#" + id;

					if (headerText) {
						if (autoTocLevels != null) {
							if (autoTocLevels.includes(level.toString())) {
								if (level > start) {
									toc += (new Array(level - start + 1)).join("<ul class='nav navbar-nav'>");
								} else if (level < start) {
									toc += (new Array(start - level + 1)).join('</li></ul>');
								} else {
									toc += (new Array(start + 1)).join('</li>');
								}
								start = parseInt(level);
								toc += "<li class><a class='nav-link' href='" + url + "'>" + headerText + "</a>";
							}
						} else {
							if (level > start) {
								toc += (new Array(level - start + 1)).join("<ul class='nav navbar-nav'>");
							} else if (level < start) {
								toc += (new Array(start - level + 1)).join('</li></ul>');
							} else {
								toc += (new Array(start + 1)).join('</li>');
							}
							start = parseInt(level);
							toc += "<li class><a class='nav-link' href='" + url + "'>" + headerText + "</a>";
						}
					}
				}
			}
			if (start) {
				toc += (new Array(start + 1)).join('</ul>');
			}

			var fixed_height = 0;
			var toc_offset = parseInt(jqueryAutoToc.plugin_autoToc_offset);
			// append the $list
			if (!jqueryAutoToc.plugin_autoToc_mode) {
				// build content
				buildContent();

				// parameter offset
				$('<style type="text/css">.affix{top:' + toc_offset + 'px;}</style>').appendTo('head');
				$(window).resize(function() {
					var page = document.getElementById("page-data");
					if (window.innerWidth <= 991) {
						if (page.hasAttribute('class')) {
							page.setAttribute("class", "col-md-12");
						}
					} else {
						page.setAttribute("class", "col-md-7");
					}
					affix();
				}).resize();

				// trigger the bootstrap affix and scrollspy
				controllSpy(toc_offset);

				function affix() {
					$(window).on('scroll', function (e) {
						var scrollpos = $(window).scrollTop();
						var offsetpos = $('#page-data').height() - $('#auto-toc > .nav').height();
						var top = $('#page-data').offset().top - (toc_offset + fixed_height);
						var bottom = $('#page-data').offset().top + $('#page-data').height() - $('#auto-toc > .nav').height() - (toc_offset + fixed_height);

						if (scrollpos > top) {
							if (scrollpos > bottom) {
								$('#auto-toc > .nav').removeClass('affix').addClass('affix-bottom').css('top', offsetpos + 'px');
							} else {
								$('#auto-toc > .nav').addClass('affix').removeClass('affix-bottom').css('top', '');
							}
						} else {
							$('#auto-toc > .nav').removeClass('affix');
						}

						$('#auto-toc .nav li a').each(function() {
							if ( $(this).hasClass('active') ) {
								$(this).parents('li').addClass('open');
							} else {
								$(this).parentsUntil('.navbar-nav .nav').removeClass('open');
							}
						});
					});
				}
			} else {
				// build content
				buildContent();
				// parameter offset
				$('<style type="text/css">#all-toc{top:' + toc_offset + 'px;}</style>').appendTo('head');
				$('#all-toc').height($('#page-data').height() - 2000);
				controllSpy(toc_offset);
			}

			function controllSpy (toc_offset) {
				var fixed_height = 0;
				$page.scrollspy({
					target: "#auto-toc",
					offset: (toc_offset + fixed_height)
				});
			}

			function buildContent() {
				$nav.prepend(toc);
				if (jqueryAutoToc.plugin_autoToc_pos === "left" || jqueryAutoToc.plugin_autoToc_pos === "top") {
					$tocNav.prepend($nav);
					$page_data.prependTo($row);
					$tocNav.prependTo($row);
				} else {
					$tocNav.prepend($nav);
					$tocNav.prependTo($row);
					$page_data.prependTo($row);
				}
				$row.prependTo($top);
			}
		}
	}
};

function countHeadings(children) {
	var count = 0;
	for (var i = 0; i < children.length; i++) {
		var isHeading = children[i].nodeName.match(/^H\d+$/);
		if (isHeading) {
			count++;
		}
	}
	return count;
}

$(document).ready(function () {
	$.genAutoToc();
	$('#auto-toc a[href^="#"]').on('click', function (e) {
		e.preventDefault();
		var target = this.hash;
		var $target = $(target);
		$('html, body').stop().animate({
			'scrollTop': $target.offset().top
		}, 900, 'swing', function () {});
	});
});