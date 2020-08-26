function genAutoToc() {
	var toc = "";
	var start = 0;
	var $top = $("#top");
	var $output = $output || "#autotoc_inpage";
	var title = jqueryAutoToc.plugin_autoToc_title;

	var source = document.querySelector(source) || document.querySelector("#page-data");
	var children = source.children;

	//if a wiki page and not printing
	if ($top.length && location.href.indexOf("tiki-print.php") == -1) {
		if(countHeadings(children) > 0) {
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

			toc = title != '' ? "<h3>"+ title +"</h3>" : "";
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
									toc += (new Array(level - start + 1)).join("<ul>");
								} else if (level < start) {
									toc += (new Array(start - level + 1)).join("</li></ul>");
								} else {
									toc += (new Array(start + 1)).join("</li>");
								}
								start = parseInt(level);
								toc += "<li><a href='" + url + "'>" + headerText + "</a>";
							}
						} else {
							if (level > start) {
								toc += (new Array(level - start + 1)).join("<ul>");
							} else if (level < start) {
								toc += (new Array(start - level + 1)).join("</li></ul>");
							} else {
								toc += (new Array(start + 1)).join("</li>");
							}
							start = parseInt(level);
							toc += "<li><a href='" + url + "'>" + headerText + "</a>";
						}
					}
				}
			}
			if (start) {
				toc += (new Array(start + 1)).join("</ul>");
			}
			document.querySelector($output).innerHTML += toc;
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
	genAutoToc();
	$('#autotoc_inpage a[href^="#"]').on('click', function (e) {
		e.preventDefault();
		var target = this.hash;
		var $target = $(target);
		$('html, body').stop().animate({
			'scrollTop': $target.offset().top
		}, 900, 'swing', function () {});
	});
});