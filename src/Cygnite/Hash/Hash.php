<?php
namespace Cygnite\Hash;

use Cygnite\Hash\BCrypt;

/**
 * Class Hash
 *
 * We will access all BCrypt methods and return value
 *
 * @package Cygnite\Hash
 */
class Hash
{
    public static $cache = [];

    /**
     * @return static BCrypt Instance
     */
    public static function instance()
    {
        if (isset(static::$cache['hash']) && is_object(static::$cache['hash'])) {
            return static::$cache['hash'];
        }

        return static::$cache['hash'] = BCrypt::make();
    }

    /**
     * @param       $string
     * @param array $arguments
     * @return mixed
     */
    public static function create($string, array $arguments = [])
    {
        return self::instance()->{__FUNCTION__}($string, $arguments);
    }

    /**
     * @param       $string
     * @param       $hash
     * @param array $arguments
     * @return mixed
     */
    public static function verify($string, $hash, array $arguments = [])
    {
        return self::instance()->{__FUNCTION__}($string, $hash, $arguments);
    }

    /**
     * @param       $hashedString
     * @param array $arguments
     * @return mixed
     */
    public static function needReHash($hashedString, array $arguments = [])
    {
        return self::instance()->{__FUNCTION__}($hashedString, $arguments);
    }
}
