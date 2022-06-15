<?php

namespace App\EzRider\Plugins\Laravel;

use App\EzRider\Plugins\Plugin;

class LaravelApplicationKeyGenerator extends Plugin
{
    /**
     * Regex used to determine if an environment var
     * contains a 'laravel:app-key' annotation
     */
    public const RANDOM_ANNOTATION_REGEX = '/laravel\:app-key/';


    /**
     * @param mixed $environmentVarValue
     * @return bool
     */
    protected function filter(mixed $environmentVarValue) : bool
    {
        if (!is_string($environmentVarValue)) {
            return false;
        }
        return preg_match(self::RANDOM_ANNOTATION_REGEX, $environmentVarValue);
    }

    /**
     * @param mixed $environmentVarValue
     * @return mixed
     * @throws \Exception
     */
    protected function map(mixed $environmentVarValue) : mixed
    {
        return 'base64:'.base64_encode(random_bytes(32));
    }
}
