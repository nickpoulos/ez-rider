<?php

namespace App\EzRider\Plugins;

use Composer\Autoload\ClassMapGenerator;

class PluginLoader
{
    public const PLUGIN_FOLDERS = [
        __DIR__
    ];

    public array $plugins;

    public function __construct(?array $pluginFolders = null)
    {
        $pluginFolders = $pluginFolders ?
            array_merge(self::PLUGIN_FOLDERS, $pluginFolders):self::PLUGIN_FOLDERS;
        $this->plugins = $this->findPlugins($pluginFolders);
    }

    public function load(string $pluginName) : string
    {
        return current(preg_grep("/$pluginName/", array_keys($this->plugins)));
    }

    protected function findPlugins(?array $pluginFolders)
    {
        return array_reduce($pluginFolders ?? self::PLUGIN_FOLDERS, static function(array $result, string $pluginPath) {
            return array_merge($result, ClassMapGenerator::createMap( $pluginPath));
        }, []);
    }
}
