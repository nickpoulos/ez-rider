<?php

namespace App\EzRider;

use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class EzRider
{
    protected const DEFAULT_CONFIG_FILENAME = 'ezrider.json';

    public function __construct(
        protected DockerComposeOverrideGenerator $dockerComposeOverrideGenerator,
        protected ?array $config = null
    ) {}

    /**
     * Load our config file into memory
     *
     * @return string
     * @throws Exception
     */
    public function loadConfig(?string $configFilePath) : string
    {
        if (!$configFilePath) {
            $configFilePath = $this->defaultConfigFilePath();
        }

        if (file_exists($configFilePath)) {
            $this->config = json_decode(file_get_contents($configFilePath), true, 512, JSON_THROW_ON_ERROR);
            return $configFilePath;
        }

        $this->config = json_decode($this->generateInitialConfig(), true, 512, JSON_THROW_ON_ERROR);
        return "Default Configuration";
    }

    /**
     * Generate override files for all the mappings in our config file
     */
    public function generateOverrideFiles()  : void
    {
        collect($this->getMappings())->each(
            fn(array $inputOutputMapping) => $this->dockerComposeOverrideGenerator->generateOverrideFile($inputOutputMapping)
        );
    }

    /**
     * Create config file with the default options
     *
     * @throws \JsonException
     */
    protected function generateInitialConfig() : string
    {
        $defaults = [
            'map' => [
                [
                    "input" => './docker-compose.yml',
                    "output" => './docker-compose.override.yml'
                ]
            ]
        ];

        return json_encode($defaults, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
    }

    /**
     * Fetch mapping options from config
     *
     * @return array|null
     */
    protected function getMappings() : ?array
    {
        return $this->config['map'];
    }

    /**
     * Generate default config filename
     *
     * @return string
     */
    protected function defaultConfigFilePath() : string
    {
        return getcwd() . '/' . self::DEFAULT_CONFIG_FILENAME;
    }
}
