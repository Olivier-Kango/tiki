function initializeEditorUI(tiki={})
{
		// Disable communication to external services
		urlParams['stealth'] = 1;
		urlParams['embed'] = 1;

		var editorUiInit = EditorUi.prototype.init;
		EditorUi.prototype.init = function()
		{
			editorUiInit.apply(this, arguments);
			var editorUi = this.actions.editorUi;
			var editor = editorUi.editor;
			var self = this;
			var tickets = tiki.tickets;
			var fileId = tiki.fileId;
			var backLocation = tiki.backLocation;
			var newDiagram = tiki.newDiagram;

			function saveDiagramFlow(closeWindow)
			{
				editorUi.editor.graph.stopEditing();

				let compressXml = tiki.compressXml;

				if (compressXml) {
					var node = editorUi.getXmlFileData();
				} else {
					var node = editorUi.getXmlFileData(true, false, true);
				}

				var content = mxUtils.getXml(node);
				var galleryId = tiki.galleryId;
				var pagesAmount = node.children.length;
				var saveElem = $(tiki.saveModal)[0];
				editorUi.showDialog(saveElem, 400, 200, true, false, null, true);

				function updatePlugin(content, params, callback) {
					var data = {
						controller: 'plugin',
						action: 'replace',
						ticket: tickets.pop(),
						page: tiki.page,
						message: 'Modified by mxGraph',
						type: 'diagram',
						content: content,
						index: tiki.index,
						params: params
					};

					$.ajax({
						type: 'POST',
						url: 'tiki-ajax_services.php',
						dataType: 'json',
						data: data,
						success: function(result) {
							reloadTickets();
							callback();
						},
						error: function(xhr, status, message) {
							showErrorMessage(message);
						}
					});
				}

				function uploadFile(content, callback) {
					var blob = new Blob([content]);
					content = window.btoa(content);

					var name = galleryId ? 'New Diagram' : tiki.fileName;

					var data = {
						controller: 'file',
						action: 'upload',
						ticket: tickets.pop(),
						name: name,
						type: 'text/plain',
						size: blob.size,
						data: content,
						fileId: fileId,
					};

					if (galleryId) {
						data.galleryId = tiki.galleryId;
					}

					$.ajax({
						type: 'POST',
						url: 'tiki-ajax_services.php',
						dataType: 'json',
						data: data,
						success: function(result) {
							reloadTickets();

							fileId = result.fileId;

							if (tiki.page && result.fileId) {
								updatePlugin('', {'fileId': result.fileId}, function() { callback() });
							} else {
								callback();
							}
						},
						error: function(xhr, status, message) {
							showErrorMessage(message);
						}
					});
				}

				function saveCache(callback) {
					var diagramPNGs = {};

					let saveImages = function(diagrams) {
						var data = {
							controller: 'diagram',
							action: 'image',
							ticket: tickets.pop(),
							name: 'Preview',
							type: 'image/png',
							content: content,
							fileId: fileId,
							data: diagrams
						};

						$.ajax({
							type: 'POST',
							url: 'tiki-ajax_services.php',
							dataType: 'json',
							data: data,
							success: function(result) {
								reloadTickets();
								callback();
							},
							error: function(xhr, status, message) {
								showErrorMessage(message);
							}
						});
					}

					for (var i = 0; i < node.children.length; i++) {
						let id = node.children[i].id;

						self.getEmbeddedPng(function(pngData) {
							diagramPNGs[id] = pngData;

							if (Object.keys(diagramPNGs).length === pagesAmount) {
								saveImages(diagramPNGs);
							}
						}, null, '<mxfile>' + node.children[i].outerHTML + '</mxfile>');
					}
				}

				function afterSaveDiagramCallback() {
					let exportImageCache = tiki.exportImageCache;

					if (exportImageCache){
						saveCache(function() {
							showModalAfterSave();
						});
					} else {
						showModalAfterSave();
					}
				}

				if (fileId || galleryId) {
					uploadFile(content, function() {
						afterSaveDiagramCallback();
					});
				} else {
					updatePlugin(content, {}, afterSaveDiagramCallback);
				}

				// Show Modal after Save diagram
				function showModalAfterSave() {
					editor.modified = false;
					editorUi.hideDialog(saveElem);

					setTimeout(function() {
						if (newDiagram && closeWindow) {
							window.location.href = backLocation;
						} else if (closeWindow) {
							window.close();
							window.opener.location.reload(false)
						}
					}, 500);
				}

				// Show Errors
				function showErrorMessage(message) {
					$('div.diagram-saving').hide();
					$('p.diagram-error-message').html(message);

					$('div.diagram-error button').on('click', function() {
						editorUi.hideDialog();
					});

					$('div.diagram-error').show();
				}

				function reloadTickets(numTickets) {

					if (tickets.length >= 1) {
						return;
					}

					var data = {
						controller: 'diagram',
						action: 'tickets',
						ticket: tickets.pop(),
						ticketsAmount: numTickets || 3,
					};

					$.ajax({
						type: 'POST',
						url: 'tiki-ajax_services.php',
						dataType: 'json',
						data: data,
						success: function(result) {
							tickets = result.new_tickets;
						},
						error: function(xhr, status, message) {
							showErrorMessage(message);
						}
					});
				}
			}

			function exit() {
				if (newDiagram) {
					window.location.href = backLocation;
				} else {
					window.close();
				}
			}

			editorUi.actions.get('exit').funct = function() {
				if (editor.modified) {
					editorUi.confirm(mxResources.get('allChangesLost'), null, function() {
						editor.modified = false;
						exit();
					}, mxResources.get('cancel'), mxResources.get('discardChanges'));
				} else {
					exit();
				}
			};

			this.saveFile = function(forceDialog) {
				saveDiagramFlow(false);
			}

			mxResources.parse('saveAndExit=Save and Exit');
			editorUi.actions.addAction('saveAndExit', function()
			{
				saveDiagramFlow(true);
			});

			editorUi.keyHandler.bindAction(83, true, 'saveAndExit', true);
			editorUi.actions.get('saveAndExit').shortcut = Editor.ctrlKey + '+Shift+S';

			var menu = editorUi.menus.get('file');
			var oldFunct = menu.funct;

			menu.funct = function(menu, parent)
			{
				oldFunct.apply(this, arguments);
				editorUi.menus.addMenuItem(menu, 'saveAndExit', parent);

				let submenuItems = $(menu.table).children().children();
				let saveAndExit = submenuItems.last();

				for (var i = 0; i < submenuItems.length; i++) {
					if (submenuItems.get(i).innerText.toLowerCase() == ('Save' + Editor.ctrlKey + '+S').toLowerCase()) {
						saveAndExit.insertAfter($(submenuItems.get(i)).before());
						break;
					}
				}
			};
			mxResources.parse(tr('saveUnchanged=Unsaved changes. Click here to save.'));

			editorUi.menubar.addMenu(mxResources.get('saveUnchanged'), function(){
				saveDiagramFlow(false);
				$('.geMenubar').children().last().hide();
			 } );

			 $('.geMenubar').children().last().css(
				{'background-color': '#f2dede', 'color': '#a94442 !important', 'padding': '4px 6px 4px 6px',
				'border': '1px solid #ebccd1', 'border-radius': '3px', 'font-size': '12px'}
			 );

			$('.geMenubar').children().last().hide();

			editor.graph.model.addListener(mxEvent.CHANGE, function(sender, evt){
				var changes = evt.getProperty('edit').changes;

				for (var i = 0; i < changes.length; i++)
				{
					var change = changes[i];
					if (change instanceof mxChildChange || change instanceof mxGeometryChange || change instanceof mxStyleChange){

						$('.geMenubar').children().last().show();
					}
				}
			});

		};
		// Adds required resources (disables loading of fallback properties, this can only
		// be used if we know that all keys are defined in the language specific file)
		mxResources.loadDefaultBundle = false;
		var bundle = mxResources.getDefaultBundle(RESOURCE_BASE, mxLanguage) ||
			mxResources.getSpecialBundle(RESOURCE_BASE, mxLanguage);

		// Fixes possible asynchronous requests
		mxUtils.getAll([bundle, STYLE_PATH + '/default.xml'], function(xhr)
		{
			// Adds bundle text to resources
			mxResources.parse(xhr[0].getText());

			// Configures the default graph theme
			var themes = new Object();
			themes[Graph.prototype.defaultThemeName] = xhr[1].getDocumentElement();

			// Main
			var ui = new EditorUi(new Editor(urlParams['chrome'] == '0', themes));
			var xml = tiki.xmlDiagram;
			ui.openLocalFile(xml, 'tiki diagram', true);

		}, function()
		{
			document.body.innerHTML = '<div class=\"mt-5 text-center alert alert-danger\">Error loading resource files. Please check browser console.</div>';
		});
};