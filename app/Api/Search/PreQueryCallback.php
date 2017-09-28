<?php
/**
 * Created by PhpStorm.
 * User: seb
 * Date: 08/08/2017
 * Time: 15:17
 */

namespace Wordery\Search\src;


use Illuminate\Support\Collection;

class PreQueryCallback
{
    /** @var  Collection */
    protected $item;
    /** @var  array */
    protected $config;

    /**
     * @param array $item
     */
    public static function alter($item)
    {
        if (
            is_array($item) &&
            in_array(key($item), config('search.query_callbacks')) &&
            $function = self::functionName(key($item))
        ) {
            return self::$function(reset($item));
        }

        return $item;
    }

    private static function functionName($key)
    {
        $function = preg_replace_callback('/(_|\s)([a-z])/i', function($matches) {
            return strtoupper($matches[1]);
        }, $key);

        if ( ! method_exists(PreQueryCallback::class, $function)) {
            \Log::info('According to your config there should be a function called "'. $function.'" but it does not appear to have been written yet.');

            return false;
        }

        return $function;
    }

    private function publicationDate($item)
    {
        return $item;
    }

    private function rank($item)
    {
        return ['rank' => ['gte' => $item]];
    }

    private function interestAge($item)
    {
        return $item;
    }

}