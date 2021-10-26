<?php

namespace App\EzRider;

use App\EzRider\Plugins\PluginInterface;

class PluginToEnvironmentVariableMapper
{
    public function map(PluginInterface $plugin, mixed $environmentVariableValue) : mixed
    {
        var_dump($plugin->filter($environmentVariableValue), $environmentVariableValue);
        if ($plugin->filter($environmentVariableValue)) {
            return $plugin->map($environmentVariableValue);
        }

        return $environmentVariableValue;
    }

    public function mapPluginToService(string $plugin, string $service, array $serviceEnvironmentVariables)
    {

    }
}
