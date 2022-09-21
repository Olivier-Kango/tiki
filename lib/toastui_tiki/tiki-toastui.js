/**
 * Tiki support js for toast ui and markdown wiki syntax
 */

function tikiToastEditor(options)
{
    if (!options.domId) {
        console.error("No element id 'domId' option provided to tikiToast init");
        return;
    }

    const reTikiPlugin = /\{(\w{2,}) .*}/,      // regex for tiki plugins (inline)
        Editor = toastui.Editor,                // global toastui Editor factory
        inputDomId = options.domId,             // the hidden input to hold a copy of the markdown
        editorDomId = inputDomId + "_editor";   // div to contain the toast ui editor
    let thisEditor;

    if (typeof window.tuiEditors !== 'object') {
        window.tuiEditors = {};
    }

    const execAutoSave = delayedExecutor(500, function () {
        auto_save(inputDomId);
    });

    let tuiOptions = $.extend(true, {}, {
        el: document.querySelector("#" + editorDomId),
        events: {
            change: function () {
                // update the hidden input with the markdown content
                document.querySelector("#" + inputDomId).value = thisEditor.getMarkdown();
                execAutoSave();
            },
            load: function (editor) {
                // maybe some more init here?
            },
        },
        plugins: [tikiPlugin],
    }, options);

// create the editor
    thisEditor = new Editor(tuiOptions);
    window.tuiEditors[inputDomId] = thisEditor;

}