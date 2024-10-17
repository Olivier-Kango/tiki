<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * @param bool $useBaseUrl by default (false) the urls generated only have the URI part, when using baseUrl will also include proto and host (full URL)
 *
 * @return string
 * @throws JsonException
 */
function generateJsImportmapScripts(bool $useBaseUrl = false)
{
    global $tikiroot, $base_url;

    $tikiUrl = $useBaseUrl ? $base_url : $tikiroot;

    $importmap = (object) [
            // NOTE: Keep the list alphabetically sorted.
            "imports" => [
                /* common_externals available in ESM format */
                "@kurkle/color" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@kurkle/color/dist/color.esm.js",
                "@popperjs/core" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/@popperjs/core/dist/esm/index.js",
                "animejs" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/anime/dist/anime.es.js",
                "@shoelace/color-picker" => $tikiUrl . JS_ASSETS_PATH . "/color-picker.js",
                "bootstrap" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/bootstrap/dist/js/bootstrap.esm.min.js",
                "chartjs" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/chart.js/dist/chart.js",
                "clipboard" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/clipboard/dist/clipboard.min.js",
                "dompurify" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/dompurify/dist/purify.es.js",
                "driver.js" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/driver.js/dist/driver.js.mjs",
                "jquery" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/jquery/dist/jquery.js",
                // We can't add jquery-validation because it's not available as ESM
                "mermaid" => $tikiUrl . MERMAID_DIST_PATH . "/mermaid.esm.min.mjs",
                "moment" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/moment/dist/moment.js",
                "select2" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/select2/dist/select2.min.js",
                "sortablejs" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/sortablejs/modular/sortable.esm.js",
                "vue" => $tikiUrl . NODE_PUBLIC_DIST_PATH . "/vue/dist/vue.esm-browser.prod.js",

                /* jquery_tiki */
                "@jquery-tiki/plugin-edit" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/plugin-edit/index.js",
                "@jquery-tiki/plugin-edit/buttons" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/plugin-edit/buttons.js",
                "@jquery-tiki/plugins/dialog" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/plugins/dialog.js",
                "@jquery-tiki/plugins/pagetabs" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/plugins/pagetabs.js",
                "@jquery-tiki/tiki-calendar" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tiki-calendar.js",
                "@jquery-tiki/tiki-svgedit_draw" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tiki-svgedit_draw.js",
                "@jquery-tiki/tiki-handle_svgedit" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tiki-handle_svgedit.js",
                "@jquery-tiki/tiki-admin_menu_options" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tiki-admin_menu_options.js",
                "@jquery-tiki/tiki-edit_structure" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/tiki-edit_structure.js",
                "@jquery-tiki/wikiplugin-trackercalendar" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/wikiplugin-trackercalendar.js",
                "@jquery-tiki/fullcalendar_to_pdf" => $tikiUrl . JS_ASSETS_PATH . "/jquery-tiki/fullcalendar_to_pdf.js",

                /* single-spa microfrontends and common files (root and styleguide) */
                "@vue-mf/duration-picker" => $tikiUrl . JS_ASSETS_PATH . "/duration-picker.js",
                "@vue-mf/emoji-picker" => $tikiUrl . JS_ASSETS_PATH . "/emoji-picker.js",
                "@vue-mf/kanban" => $tikiUrl . JS_ASSETS_PATH . "/kanban.js",
                "@vue-mf/root-config" => $tikiUrl . JS_ASSETS_PATH . "/root-config.js",
                "@vue-mf/styleguide" => $tikiUrl . JS_ASSETS_PATH . "/styleguide.js",
                "@vue-mf/tiki-offline" => $tikiUrl . JS_ASSETS_PATH . "/tiki-offline.js",
                "@vue-mf/toolbar-dialogs" => $tikiUrl . JS_ASSETS_PATH . "/toolbar-dialogs.js",

                /* vue widgets */
                "@vue-widgets/datetime-picker" => $tikiUrl . JS_ASSETS_PATH . "/datetime-picker.js",
                "@vue-widgets/element-plus-ui" => $tikiUrl . JS_ASSETS_PATH . "/element-plus-ui.js",

                /* tiki-iot */
                "@tiki-iot/tiki-iot-dashboard-all" => $tikiUrl . JS_ASSETS_PATH . "/tiki-iot/tiki-iot-dashboard-all.js",
                "@tiki-iot/tiki-iot-dashboard" => $tikiUrl . JS_ASSETS_PATH . "/tiki-iot/tiki-iot-dashboard.js",
            ]
        ];
    $importmapJson = json_encode($importmap, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    $esModuleShimsSrc = $tikiUrl . NODE_PUBLIC_DIST_PATH . "/es-module-shims/dist/es-module-shims.js";
    $html = <<<HTML
    <script async src="$esModuleShimsSrc"></script>
    <script type="importmap">
        $importmapJson
    </script>
    <script type="module">
        import "@vue-mf/root-config";
    </script>
    HTML;
    return $html;
}
