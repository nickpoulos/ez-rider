<?php

namespace App\EzRider\Plugins\Vault;

use Illuminate\Support\Str;
use App\EzRider\Plugins\Plugin;
use Wilderborn\Partyline\Facade as Partyline;

class VaultRetriever extends Plugin
{
    public const VAULT_ANNOTATION_REGEX = '/(?<=vault\:)(secret\/data\/.+)(#.+)/';
    public const VAULT_ANNOTATION_PREFIX = 'vault:';
    public const VAULT_KEY_SEPARATOR = '#';

    public function __construct(protected VaultService $vaultService){}

    public function filter(mixed $environmentVarValue) : bool
    {
        if (!is_string($environmentVarValue)) {
            return false;
        }

        return preg_match(self::VAULT_ANNOTATION_REGEX, $environmentVarValue);
    }

    public function map(mixed $environmentVarValue) : string
    {
        return $this->getSecretFromVault(
            $this->parseSecretPath($environmentVarValue)
        );
    }

    protected function parseSecretPath(string $environmentVarValue) : array
    {
        return explode(self::VAULT_KEY_SEPARATOR, Str::after($environmentVarValue, self::VAULT_ANNOTATION_PREFIX));
    }

    protected function getSecretFromVault(array $secretPathAndKey) : string
    {
        $vaultData = $this->vaultService->fetchSecret('/' . $secretPathAndKey[0]);

        if (array_key_exists('errors', $vaultData)) {
            Partyline::error('Vault ' . Str::plural('Error', count($vaultData['errors'])) . ': ' . implode(PHP_EOL, $vaultData['errors']));
            exit(1);
        }

        return $vaultData['data']['data'][$secretPathAndKey[1]];
    }
}
