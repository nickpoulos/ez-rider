<?php

namespace App\EzRider;


use Illuminate\Support\Collection;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class DockerComposeParser
{
    /**
     * @throws FileNotFoundException
     */
    public function loadDockerComposeFile(string $dockerComposeFilepath) : array
    {
        $realPath = realpath($dockerComposeFilepath);

        if (!$realPath) {
            throw new FileNotFoundException('Could Not Locate Docker Compose File');
        }

        return yaml_parse_file($dockerComposeFilepath);
    }

    /**
     * @param array $dockerConfig
     * @todo use new PHP 8.1 syntax soon for first class callables
     * @return Collection|null
     */
    public function getEnvironmentVariablesByService(array $dockerConfig) : ?Collection
    {
        return $this->getServices($dockerConfig)?->filter(\Closure::fromCallable([$this, "filterServicesWithEnvironments"]));
    }

    public function mapEnvironmentVariablesFromServiceData(array $serviceData, string $serviceName) : Collection
    {
        return collect($serviceData['environment']);
    }

    /**
     * @param array $serviceData
     * @param string $serviceName
     * @return bool
     */
    protected function filterServicesWithEnvironments (array $serviceData, string $serviceName) : bool
    {
        return array_key_exists('environment', $serviceData);
    }

    /**
     * @param array $dockerConfig
     * @return Collection|null
     */
    protected function getServices(array $dockerConfig) : ?Collection
    {
        return array_key_exists('services', $dockerConfig) ? collect($dockerConfig['services']):null;
    }
}
