<?php
namespace Cygnite\Helpers;

/**
 * Class String
 *
 * @package Cygnite\Helpers
 */
class Str
{
    public static $alpha = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * Generate random key based on type
     *
     * @param string $type
     * @param int    $length
     * @return string
     */
    public static function random($type = 'alnum', $length = 16)
    {
        switch ($type) {
            case 'normal':
                $key = mt_rand();
                break;
            case 'unique':
                $key = md5(uniqid(mt_rand()));
                break;
            case 'sha1':
                $key = sha1(uniqid(mt_rand(), true));;
                break;
            case 'alnum':
                $key = '0123456789'.static::$alpha;
                break;
            case 'alpha':
                $key = static::$alpha;
                break;
        }

        $random = '';
        for ($i=0; $i < $length; $i++) {
            $random .= substr($key, mt_rand(0, strlen($key) -1), 1);
        }

        return $random;
    }
}
