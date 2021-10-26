<?php

namespace App\EzRider\Plugins;

use Illuminate\Support\Collection;

abstract class Plugin
{
    abstract protected function filter(mixed $environmentVarValue): bool;
    abstract protected function map(mixed $environmentVarValue): mixed;

    public function mapServicesEnvironmentVariables(Collection $environmentVariablesForAllServices) : array
    {
        return $environmentVariablesForAllServices
            ->map(\Closure::fromCallable([$this, 'mapServiceEnvironmentVariables']))
            ->filter(\Closure::fromCallable([$this, 'serviceRequiresOverrideFile']))
            ->toArray();
    }

    protected function serviceRequiresOverrideFile(array $mappedEnvironmentVariablesForService) : bool
    {
        return count($mappedEnvironmentVariablesForService) > 0;
    }

    protected function mapServiceEnvironmentVariables(Collection $environmentVariablesForSingleService)  : array
    {
        return $environmentVariablesForSingleService
            ->filter(\Closure::fromCallable([$this,'filter']))
            ->map(\Closure::fromCallable([$this, 'map']))
            ->toArray();
    }
}
