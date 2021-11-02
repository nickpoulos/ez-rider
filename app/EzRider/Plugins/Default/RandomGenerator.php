<?php

namespace App\EzRider\Plugins\Default;

use Illuminate\Support\Str;
use App\EzRider\Plugins\Plugin;

class RandomGenerator extends Plugin
{
    /**
     * Regex used to determine if an environment var
     * contains a 'random' annotation
     */
    public const RANDOM_ANNOTATION_REGEX = '/(?<=random\:)[A-Za-z]+\(?.*,?.*\)?/';

    /**
     *
     */
    public const RANDOM_ANNOTATION_PREFIX = 'random:';

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
        $input = Str::after($environmentVarValue, self::RANDOM_ANNOTATION_PREFIX);
        $command = strtolower(Str::before($input, '('));
        $args = explode(',', Str::between($input, '(', ')'));
        $args = $args === [$command] ? []:$args;

        return match ($command) {
            'int' => $this->randomInt(...$args),
            'string' => $this->randomString(...$args),
            'array' => $this->randomArray($args),
        };
    }


    /**
     * @param string|int $length
     * @return string
     * @throws \Exception
     */
    protected function randomString(string|int $length = 1) : string
    {
        $secret = "";
        $charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_-+=`~,<>.[]: |';
        for ($x = 1; $x <= random_int( 1, 10 ); $x++){
            $charset = str_shuffle($charset);
        }
        for ($s = 1; $s <= $length; $s++) {
            $secret .= $charset[random_int(0, 86)];
        }
        return $secret;
    }

    /**
     * @param array $possibleValues
     * @return mixed
     */
    protected function randomArray(array $possibleValues) : mixed
    {
        return $possibleValues[array_rand($possibleValues, 1)];
    }

    /**
     * @throws \Exception
     */
    protected function randomInt(string|int $min = 1, string|int $max = 1000) : int
    {
        return random_int((int) $min, (int) $max);
    }
}
