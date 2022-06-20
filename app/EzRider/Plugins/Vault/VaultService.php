<?php

namespace App\EzRider\Plugins\Vault;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Wilderborn\Partyline\Facade as Partyline;

class VaultService
{
    protected const VAULT_TOKEN_ENV_VAR = 'VAULT_TOKEN';
    protected const VAULT_URL_ENV_VAR = 'VAULT_ADDR';

    protected ?string $vaultUrl;
    protected ?string $vaultToken;

    /**
     */
    public function __construct()
    {
        $this->vaultUrl = $this->fetchVaultUrl();
        $this->vaultToken = $this->fetchVaultToken();
    }

    /**
     * @throws \JsonException
     */
    public function fetchSecret(string $secretPath) : array
    {
        return Cache::get($secretPath, function() use ($secretPath) {
            $response = Http::withHeaders([
                'X-Vault-Token' => $this->vaultToken
            ])->get(Str::finish($this->vaultUrl, '/') .'v1/' . $secretPath);

            return json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
        });
    }

    protected function fetchVaultUrl() : string
    {
        $cache = Cache::get(self::VAULT_URL_ENV_VAR);

        if ($cache) {
            return $cache;
        }

        $env = getenv(self::VAULT_URL_ENV_VAR);

        if ($env !== false) {
            return $env;
        }

       return $this->promptUrl();
    }

    protected function fetchVaultToken() : string
    {
        $cache = Cache::get(self::VAULT_TOKEN_ENV_VAR);

        if ($cache) {
            return $cache;
        }

        $env = getenv(self::VAULT_TOKEN_ENV_VAR);

        if ($env !== false) {
            return $env;
        }

       return $this->promptToken();
    }

    protected function promptUrl() : string
    {
        $url = Partyline::ask('Enter Vault URL');
        Cache::put(self::VAULT_URL_ENV_VAR, $url);
        return $url;
    }

    protected function promptToken() : string
    {
        $token = Partyline::secret('Enter Vault API Token');
        Cache::put(self::VAULT_TOKEN_ENV_VAR, $token);
        return $token;
    }
}
