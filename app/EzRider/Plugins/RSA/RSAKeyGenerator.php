<?php

namespace App\EzRider\Plugins\RSA;

use phpseclib3\Crypt\RSA;
use Illuminate\Support\Str;
use App\EzRider\Plugins\Plugin;
use Illuminate\Support\Facades\Cache;

class RSAKeyGenerator extends Plugin
{
    /**
     * Regex used to determine if an environment var
     * contains a RSA annotation
     */
    public const RSA_ANNOTATION_REGEX = '/(?<=rsa\:)[public|private]+\(?.*,?.*\)?/';

    /**
     *
     */
    public const RSA_ANNOTATION_PREFIX = 'rsa:';

    public const DEFAULT_RSA_KEY_NAME = 'default';
    public const DEFAULT_RSA_KEY_LENGTH = 4096;

    public const CACHE_KEY_PREFIX = 'rsa-key-';

    public const KEY_TYPE_PRIVATE = 'private';

    public const KEY_TYPE_PUBLIC = 'public';


    /**
     * @param mixed $environmentVarValue
     * @return bool
     */
    protected function filter(mixed $environmentVarValue) : bool
    {
        if (!is_string($environmentVarValue)) {
            return false;
        }
        return preg_match(self::RSA_ANNOTATION_REGEX, $environmentVarValue);
    }

    /**
     * @param mixed $environmentVarValue
     * @return mixed
     * @throws \Exception
     */
    protected function map(mixed $environmentVarValue) : mixed
    {
        $input = Str::after($environmentVarValue, self::RSA_ANNOTATION_PREFIX);
        $keyType = strtolower(Str::before($input, '('));
        $args = explode(',', Str::between($input, '(', ')'));
        $args = $args === [$keyType] ? []:array_slice($args, 0, 2);

        [$keyLength, $keyName] = $this->getKeyParametersFromArguments($args);

        $privateKey = Cache::remember(self::CACHE_KEY_PREFIX . $keyName, now()->addMinutes(5), static fn() => RSA::createKey($keyLength));

        return match ($keyType) {
            self::KEY_TYPE_PRIVATE => (string) $privateKey,
            self::KEY_TYPE_PUBLIC => (string) $privateKey->getPublicKey(),
        };
    }

    protected function getKeyParametersFromArguments(array $args) : array
    {
        switch(count($args)) {
            case 1:
                $keyLength = is_numeric($args[0]) ? (int) $args[0]:self::DEFAULT_RSA_KEY_LENGTH;
                $keyName = !is_numeric($args[0]) ? $args[0]:self::DEFAULT_RSA_KEY_NAME;
                break;

            case 2:
                if (is_numeric($args[0])) {
                    $keyLength =  (int) $args[0];
                    $keyName = $args[1];
                } else {
                    $keyLength = (int) $args[1];
                    $keyName = $args[0];
                }
                break;
            default:
                $keyLength = self::DEFAULT_RSA_KEY_LENGTH;
                $keyName = self::DEFAULT_RSA_KEY_NAME;
        }

        return [$keyLength, $keyName];
    }
}
