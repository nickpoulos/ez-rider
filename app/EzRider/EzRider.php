<?php

namespace App\EzRider;

use Exception;
use App\EzRider\Plugins\Plugin;
use Illuminate\Support\Facades\File;
use App\EzRider\Plugins\PluginLoader;
use Wilderborn\Partyline\Facade as Partyline;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class EzRider
{
    protected const DEFAULT_CONFIG_FILENAME = 'ezrider.json';

    public function __construct(
        protected DockerComposeParser $dockerComposeParser,
        protected PluginLoader $pluginLoader,
        protected PluginToEnvironmentVariableMapper $pluginToEnvironmentVariableMapper,
        protected ?array $config = null
    ) {}

    /**
     * @throws Exception
     */
    public function loadConfig(string $configFilePath) : void
    {
        if (!$configFilePath && !file_exists($this->defaultConfigFilePath())) {
            $this->generateInitialConfig();
            $configFilePath = $this->defaultConfigFilePath();
        }

        if (!file_exists($configFilePath)) {
            throw new FileNotFoundException('Config file cannot be located (' . $configFilePath . ')');
        }

        $this->config = json_decode(file_get_contents($configFilePath), true, 512, JSON_THROW_ON_ERROR);
    }

    public function generateOverrideFiles()  : void
    {
        collect($this->getMappings())->each(function (array $map) {

            ["input" => $inputFilePath, "output" => $outputFilepath] = $map;

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

            $mappedEnvironmentVariablesByService = collect($this->getPlugins())->map(function(string $pluginClass) use ($environmentVariablesByService) {

                /** @var Plugin $plugin */
                $plugin = app()->make($this->pluginLoader->load($pluginClass));
                return $plugin->mapServicesEnvironmentVariables($environmentVariablesByService);

            })->toArray();

            $dockerComposeOverrideConfig = [
                'services' => $mappedEnvironmentVariablesByService
            ];

            yaml_emit_file($outputFilepath, $dockerComposeOverrideConfig);
        });
    }

    /**
     * @throws \JsonException
     */
    protected function generateInitialConfig() : void
    {
        $defaults = [
            'plugin_paths' => [],
            'plugins' => ['VaultRetriever', 'RandomGenerator'],
            'map' => [
                [
                    "input" => './docker-compose.yml',
                    "output" => './docker-compose.override.yml'
                ]
            ]
        ];

        File::put(
            $this->defaultConfigFilePath(),
            json_encode($defaults, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)
        );
    }

    protected function getMappings() : ?array
    {
        return $this->config['map'];
    }

    protected function getPlugins() : ?array
    {
        return $this->config['plugins'];
    }

    protected function defaultConfigFilePath() : string
    {
        return getcwd() . '/' . self::DEFAULT_CONFIG_FILENAME;
    }
}
