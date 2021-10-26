<?php

namespace App\EzRider\Plugins\Default;

use Illuminate\Support\Str;
use App\EzRider\Plugins\Plugin;

class RandomGenerator extends Plugin
{
    public const RANDOM_ANNOTATION_REGEX = '/(?<=random\:)[A-Za-z]+\(?.*,?.*\)?/';
    public const RANDOM_ANNOTATION_PREFIX = 'random:';

    protected function filter(mixed $environmentVarValue) : bool
    {
        if (!is_string($environmentVarValue)) {
            return false;
        }
        return preg_match(self::RANDOM_ANNOTATION_REGEX, $environmentVarValue);
    }

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

    protected function randomArray(array $possibleValues) : mixed
    {
        return array_rand($possibleValues, 1);
    }

    /**
     * @throws \Exception
     */
    protected function randomInt(string|int $min = 1, string|int $max = 1000) : int
    {
        return random_int((int) $min, (int) $max);
    }
}
