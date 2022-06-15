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
     * @return int|bool
     */
    public function generateOverrideFile(array $inputOutputMap) : int|bool
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
