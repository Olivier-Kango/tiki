/**
 * Wrapper for tiki wikiplugins in Toast UI Editor
 */


/**
 * Process a tiki wiki plugin
 *
 * @returns array of nodes
 */
function tikiPlugin(eventEmitter)
{
    const reTikiPlugin = /\{(\w{2,})[ \(].*}/i;      // regex for tiki plugins (inline)

    const thisEditor = eventEmitter.instance;

    const generateId = function () {
        return `tiki-${Math.random().toString(36).substr(2, 10)}`;
    }

    const pluginParse = function (syntax) {

    }

    const toHTMLRenderers = {
        tiki(node)
        {
            const id = generateId();
            const matches = node.literal.match(reTikiPlugin);

            if (matches) {
                const pluginName = matches[1];

                setTimeout(function () {

                    $.getJSON(
                        $.service("plugin", "render"),
                        {
                            markup: node.literal,
                        },
                        function (data) {

                            const $pluginDiv = $("#" + id);

                            // disable the hrefs so the popup form shows wherever you click
                            $pluginDiv.html(data.html).find("a").attr("href", "#");
                            $pluginDiv.click(function () {
                                const plugin = data.plugins[0];
                                const $this = $(this);
                                let index = 0;

                                // calculate the current index
                                $this.parents(".toastui-editor-contents")
                                    .find(".tiki_plugin." + pluginName).each(function () {
                                    if ($this.attr("id") === $(this).attr("id")) {
                                        return false;
                                    } else {
                                        index++;
                                    }
                                });

                                popupPluginForm(
                                    thisEditor.options.domId,
                                    pluginName,
                                    index,
                                    data.pageName,
                                    plugin.arguments,
                                    true,
                                    plugin.body,
                                    false,
                                    "",
                                    node.literal
                                );
                            });
                        },
                        "json"
                    );
                }, 100);

                return [
                    {
                        type: 'openTag',
                        tagName: 'div',
                        outerNewLine: false,
                        classNames: [`tui-widget tiki_plugin ${pluginName}`],
                        attributes: {
                            id: id,
                            dataPlugin: node.literal,
                        }
                    },
                    {type: 'html', content: node.literal},
                    {type: 'closeTag', tagName: 'div', outerNewLine: false}
                ];
            } else {
                return node;    // not matched with a plugin?
            }
        },
    }

    return {toHTMLRenderers}
}

