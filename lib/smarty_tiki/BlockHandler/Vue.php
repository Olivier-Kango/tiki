<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\BlockHandler;

use Smarty\BlockHandler\Base;
use Smarty\Template;

/**
 * \brief Smarty {vue} block handler to contain a vue.js component
 *
 * Usage:
{vue}
<template>
    <p>{{ greeting }} World!</p>
</template>

<script>
    export default {
        data: function () {
            return {
                greeting: 'Hello'
            }
        }
    }
</script>

<style scoped>
    p {
        font-size: 2em;
        text-align: center;
    }
</style>
{/vue}
 *
 * Examples:
 *
 */
/**
 * @param $params     array  [ app = n|y, name = string ]
 * @param $content    string body of the Vue componenet
 * @param $template     \Smarty\Template
 * @param $repeat     boolean
 *
 * @return string
 * @throws Exception
 */
class Vue extends Base
{
    public function handle($params, $content, Template $template, &$repeat)
    {
        global $prefs;
        $headerlib = \TikiLib::lib('header');

        if ($repeat || empty($content)) {
            return '';
        }

        if ($prefs['vuejs_enable'] === 'n') {
            \Feedback::error(tr('Vue.js is not enabled.'));
            return '';
        }

        //The js loader will deduplicate if necessary
        $headerlib->add_jsfile_cdn("vendor_bundled/vendor/npm-asset/vue/dist/{$prefs['vuejs_build_mode']}");

        // all ready? then we shall begin

        $app = ! (empty($params['app']) || $params['app'] === 'n');
        $name = ! isset($params['name']) ? '' : $params['name'];

        return \TikiLib::lib('vuejs')->processVue($content, $name, $app);
    }
}
