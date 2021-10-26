<?php

namespace App\EzRider\Plugins\Vault;

use Illuminate\Support\Str;
use App\EzRider\Plugins\Plugin;
use Wilderborn\Partyline\Facade as Partyline;
use App\EzRider\Plugins\PluginInterface;

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

    public function map(mixed $environmentVarValue) : mixed
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
        return $vaultData['data']['data'][$secretPathAndKey[1]];
    }
}
