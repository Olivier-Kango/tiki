<?php

$expectedJsArray = array (
  10 =>
  array (
    0 => 'if (typeof window.CKEDITOR !== "undefined" && !window.CKEDITOR.plugins.get("tikiimage")) {
    window.CKEDITOR.config.extraPlugins += (window.CKEDITOR.config.extraPlugins ? \',tikiimage\' : \'tikiimage\' );
    window.CKEDITOR.plugins.add( \'tikiimage\', {
        init : function( editor ) {
            var command = editor.addCommand( \'tikiimage\', new window.CKEDITOR.command( editor , {
                modes: { wysiwyg:1 },
                exec: function (editor, data) {
                    openFgalsWindow(\'tiki-upload_file.php?galleryId=1&view=browse&filegals_manager=editwiki\', true);
                },
                canUndo: false
            }));
            editor.ui.addButton( \'tikiimage\', {
                label : \'Choose or upload images\',
                command : \'tikiimage\',
                icon: editor.config._TikiRoot + \'img/icons/pictures.png\'
            });
        }
    });
}',
    1 => 'if (typeof window.CKEDITOR !== "undefined" && !window.CKEDITOR.plugins.get("tikilink")) {
    window.CKEDITOR.config.extraPlugins += (window.CKEDITOR.config.extraPlugins ? \',tikilink\' : \'tikilink\' );
    window.CKEDITOR.plugins.add( \'tikilink\', {
        init : function( editor ) {
            var command = editor.addCommand( \'tikilink\', new window.CKEDITOR.command( editor , {
                modes: { wysiwyg:1 },
                exec: function (editor, data) {
                    displayDialog( this, 1, editor.name)
                },
                canUndo: false
            }));
            editor.ui.addButton( \'tikilink\', {
                label : \'Wiki Link\',
                command : \'tikilink\',
                icon: editor.config._TikiRoot + \'img/icons/page_link.png\'
            });
        }
    });
}',
    2 => 'if (typeof window.CKEDITOR !== "undefined" && !window.CKEDITOR.plugins.get("externallink")) {
    window.CKEDITOR.config.extraPlugins += (window.CKEDITOR.config.extraPlugins ? \',externallink\' : \'externallink\' );
    window.CKEDITOR.plugins.add( \'externallink\', {
        init : function( editor ) {
            var command = editor.addCommand( \'externallink\', new window.CKEDITOR.command( editor , {
                modes: { wysiwyg:1 },
                exec: function (editor, data) {
                    displayDialog( this, 2, editor.name)
                },
                canUndo: false
            }));
            editor.ui.addButton( \'externallink\', {
                label : \'External Link\',
                command : \'externallink\',
                icon: editor.config._TikiRoot + \'img/icons/world_link.png\'
            });
        }
    });
}',
    3 => 'if (typeof window.CKEDITOR !== "undefined" && !window.CKEDITOR.plugins.get("tikihelp")) {
    window.CKEDITOR.config.extraPlugins += (window.CKEDITOR.config.extraPlugins ? \',tikihelp\' : \'tikihelp\' );
    window.CKEDITOR.plugins.add( \'tikihelp\', {
        init : function( editor ) {
            var command = editor.addCommand( \'tikihelp\', new window.CKEDITOR.command( editor , {
                modes: { wysiwyg:1 },
                exec: function (editor, data) {
                    $.openModal({show: true, remote: "tiki-ajax_services.php?controller=edit&action=help&modal=1&wysiwyg=1&plugins=1&areaId=editwiki"});
                },
                canUndo: false
            }));
            editor.ui.addButton( \'tikihelp\', {
                label : \'WYSIWYG Help\',
                command : \'tikihelp\',
                icon: editor.config._TikiRoot + \'img/icons/help.png\'
            });
        }
    });
}',
    4 => 'if (typeof window.CKEDITOR !== "undefined" && !window.CKEDITOR.plugins.get("tikitable")) {
    window.CKEDITOR.config.extraPlugins += (window.CKEDITOR.config.extraPlugins ? \',tikitable\' : \'tikitable\' );
    window.CKEDITOR.plugins.add( \'tikitable\', {
        init : function( editor ) {
            var command = editor.addCommand( \'tikitable\', new window.CKEDITOR.command( editor , {
                modes: { wysiwyg:1 },
                exec: function (editor, data) {
                    displayDialog( this, 5, editor.name)
                },
                canUndo: false
            }));
            editor.ui.addButton( \'tikitable\', {
                label : \'Table Builder\',
                command : \'tikitable\',
                icon: editor.config._TikiRoot + \'img/icons/table.png\'
            });
        }
    });
}',
  ),
  2 =>
  array (
    0 => 'window.dialogData[1] = ["Wiki Link","<label for=\\"tbWLinkDesc\\">Show this text<\\/label>","<input type=\\"text\\" id=\\"tbWLinkDesc\\" class=\\"ui-widget-content ui-corner-all\\" style=\\"width: 98%\\" \\/>","<label for=\\"tbWLinkPage\\">Link to this page<\\/label>","<input type=\\"text\\" id=\\"tbWLinkPage\\" class=\\"ui-widget-content ui-corner-all\\" style=\\"width: 98%\\" \\/>","","","","","{\\"open\\": function () { dialogInternalLinkOpen(area_id); },\\n                        \\"buttons\\": { \\"Cancel\\": function() { dialogSharedClose(area_id,this); },\\"Insert\\": function() { dialogInternalLinkInsert(area_id,this); }}}"];',
  ),
  3 =>
  array (
    0 => 'window.dialogData[2] = ["External Link","<label for=\\"tbLinkDesc\\">Show this text<\\/label>","<input type=\\"text\\" id=\\"tbLinkDesc\\" class=\\"ui-widget-content ui-corner-all\\" style=\\"width: 98%\\" \\/>","<label for=\\"tbLinkURL\\">link to this URL<\\/label>","<input type=\\"text\\" id=\\"tbLinkURL\\" class=\\"ui-widget-content ui-corner-all\\" style=\\"width: 98%\\" \\/>","<label for=\\"tbLinkRel\\">Relation:<\\/label>","<input type=\\"text\\" id=\\"tbLinkRel\\" class=\\"ui-widget-content ui-corner-all\\" style=\\"width: 98%\\" \\/>","","","{\\"width\\": 300, \\"open\\": function () { dialogExternalLinkOpen( area_id ) },\\n                        \\"buttons\\": { \\"Cancel\\": function() { dialogSharedClose(area_id,this); },\\"Insert\\": function() { dialogExternalLinkInsert(area_id,this) }}}"];',
  ),
  6 =>
  array (
    0 => 'window.dialogData[5] = ["Table Builder","{\\"open\\": function () { dialogTableOpen(area_id,this); },\\n                        \\"width\\": 320, \\"buttons\\": { \\"Cancel\\": function() { dialogSharedClose(area_id,this); },\\"Insert\\": function() { dialogTableInsert(area_id,this); }}}"];',
  ),
);
