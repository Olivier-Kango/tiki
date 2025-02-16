<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class WikiParser_PluginRepository
{
    private $folders = [];
    private $pluginsFound = [];

    public function addPluginFolder($folder)
    {
        $this->folders[] = $folder;
    }

    public function getInfo($pluginName)
    {
        if (! $this->pluginExists($pluginName)) {
            return null;
        }

        $pluginName = strtolower($pluginName);
        $location = $this->pluginsFound[$pluginName];

        $functionName = "wikiplugin_$pluginName";
        $infoName = "wikiplugin_{$pluginName}_info";

        include_once "{$location}/$functionName.php";

        if (! function_exists($functionName)) {
            $this->pluginsFound[ $pluginName ] = false;
            return null;
        }

        if (! function_exists($infoName)) {
            return null;
        }

        return new WikiParser_PluginDefinition($this, $infoName());
    }

    public function pluginExists($pluginName)
    {
        $pluginName = strtolower($pluginName);

        if (isset($this->pluginsFound[ $pluginName ])) {
            return false !== $this->pluginsFound[ $pluginName ];
        }

        foreach ($this->folders as $folder) {
            if ($this->pluginExistsIn($pluginName, $folder)) {
                $this->pluginsFound[ $pluginName ] = $folder;
                return true;
            }
        }

        $this->pluginsFound[ $pluginName ] = false;
        return false;
    }

    private function pluginExistsIn($pluginName, $folder)
    {
        $file = $folder . '/wikiplugin_' . $pluginName . '.php';

        return file_exists($file);
    }

    public function getList()
    {
        $real = [];

        foreach ($this->folders as $folder) {
            foreach (glob($folder . '/wikiplugin_*.php') as $file) {
                $base = basename($file);
                $plugin = substr($base, 11, -4);

                $real[] = $plugin;
            }
        }

        return $real;
    }
}
