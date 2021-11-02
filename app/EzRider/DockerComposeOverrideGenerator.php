<?php

namespace App\EzRider;

use App\EzRider\Plugins\Plugin;
use App\EzRider\Plugins\PluginLoader;
use Wilderborn\Partyline\Facade as Partyline;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class DockerComposeOverrideGenerator
{
    public function __construct(
        protected DockerComposeParser $dockerComposeParser,
        protected PluginLoader $pluginLoader,
    ) {}

    /**
     * Generate an override file for a given mapping
     *
     * @param array $inputOutputMap
     * @return int|bool
     */
    public function generateOverrideFile(array $inputOutputMap, array $plugins) : int|bool
    {
        ["input" => $inputFilePath, "output" => $outputFilepath] = $inputOutputMap;

        Partyline::info('Generating ' . $outputFilepath . ' from ' . $inputFilePath . '...');

        try {
            $dockerComposeConfig = $this->dockerComposeParser->loadDockerComposeFile($inputFilePath);
        } catch (FileNotFoundException $e) {
            Partyline::error($e->getMessage() . ' (' . $inputFilePath . ') : Skipping');
            return true; // === continue;
        }

        $servicesWithEnvironmentVariables = $this->dockerComposeParser->getEnvironmentVariablesByService($dockerComposeConfig);

        if (!$servicesWithEnvironmentVariables) {
            Partyline::error('No Valid Services/Environment Vars In Docker Compose File (' . $inputFilePath . '): Skipping!');
            return true; // === continue;
        }

        $environmentVariablesByService = $servicesWithEnvironmentVariables->map(\Closure::fromCallable([$this->dockerComposeParser, 'mapEnvironmentVariablesFromServiceData']));

        $mappedEnvironmentVariablesByServicePerPlugin = collect($plugins)->map(function(string $pluginClass) use ($environmentVariablesByService) {
            /** @var Plugin $plugin */
            $plugin = app()->make($this->pluginLoader->load($pluginClass));
            return $plugin->mapServicesEnvironmentVariables($environmentVariablesByService);
        })->toArray();

        $dockerComposeOverrideConfig = [
            'services' => array_merge_recursive(...$mappedEnvironmentVariablesByServicePerPlugin)
        ];

        $baseYaml = yaml_emit($dockerComposeOverrideConfig);
        $metaData = $this->generateMetaData();

        return file_put_contents($outputFilepath, $metaData . $baseYaml);
    }

    /**
     * Generate some meta-data about the override operation
     *
     * @return string
     */
    protected function generateMetaData() : string
    {
        return '---' . PHP_EOL .
            'generator: ' . config('app.name') . ' (' . app('git.version') . ')' . PHP_EOL .
            'created_at: ' . date('Y-m-d H:i:s') . PHP_EOL;
    }
}
