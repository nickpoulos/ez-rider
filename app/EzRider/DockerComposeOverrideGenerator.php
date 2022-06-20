<?php

namespace App\EzRider;

use App\EzRider\Plugins\Plugin;
use Wilderborn\Partyline\Facade as Partyline;
use App\EzRider\Plugins\RSA\RSAKeyGenerator;
use App\EzRider\Plugins\Vault\VaultRetriever;
use App\EzRider\Plugins\Default\RandomGenerator;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use App\EzRider\Plugins\Laravel\LaravelApplicationKeyGenerator;

class DockerComposeOverrideGenerator
{
    public const DOCKER_COMPOSE_SYNTAX_VERSION = '3.3';

    public const PLUGINS = [
        RandomGenerator::class,
        VaultRetriever::class,
        RSAKeyGenerator::class,
        LaravelApplicationKeyGenerator::class,
    ];

    public function __construct(
        protected DockerComposeParser $dockerComposeParser,
    ) {}

    /**
     * Generate an override file for a given mapping
     *
     * @param array $inputOutputMap
     * @return bool
     */
    public function generateOverrideFile(array $inputOutputMap) : bool
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

        $mappedEnvironmentVariablesByServicePerPlugin = collect(self::PLUGINS)->map(function(string $pluginClass) use ($environmentVariablesByService) {
            /** @var Plugin $plugin */
            $plugin = app()->make($pluginClass);
            return $plugin->mapServicesEnvironmentVariables($environmentVariablesByService);
        });

        $mappedEnvironmentVariablesByService = collect(array_merge_recursive(...$mappedEnvironmentVariablesByServicePerPlugin));

        $dockerComposeOverrideConfig = $mappedEnvironmentVariablesByService->reduce(function(array $dockerComposeOverrideConfig, array $mappedEnvironmentVariables, string $serviceName) use ($environmentVariablesByService) {
            $dockerComposeOverrideConfig['services'][$serviceName]['environment'] = $mappedEnvironmentVariables;
            return $dockerComposeOverrideConfig;
        }, ['version' => self::DOCKER_COMPOSE_SYNTAX_VERSION,]);

        return yaml_emit_file($outputFilepath, $dockerComposeOverrideConfig);
    }
}
