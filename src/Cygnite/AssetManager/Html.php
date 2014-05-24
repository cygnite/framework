<?php
namespace Cygnite\AssetManager;

use Cygnite\Helpers\Config;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}
/**
 *  Cygnite Framework
 *
 *  An open source application development framework for PHP 5.3  or newer
 *
 *   License
 *
 *   This source file is subject to the MIT license that is bundled
 *   with this package in the file LICENSE.txt.
 *   http://www.cygniteframework.com/license.txt
 *   If you did not receive a copy of the license and are unable to
 *   obtain it through the world-wide-web, please send an email
 *   to sanjoy@hotmail.com so I can send you a copy immediately.
 *
 * @Package                    :  Packages
 * @Sub Packages               :  Helper
 * @Filename                   :  Html
 * @Description                :  Used to manage html entities etc.
 *                                Not implemented in current version. May be available on next version.
 * @Author                     :  Cygnite Dev Team
 * @Copyright                  :  Copyright (c) 2013 - 2014,
 * @Link	                   :  http://www.cygniteframework.com
 * @Since	                   :  Version 1.0
 * @Filesource
 * @Warning                    :  Any changes in this library can cause abnormal behaviour of the framework
 *
 *
 */

class Html
{
    /**
     * Convert Html characters to entities.
     *
     * Encoding will be used based  on configuration given in Config file.
     *
     * @false  string  $value
     * @param $value
     * @return string
     */
    public static function entities($value)
    {
        return htmlentities($value, ENT_QUOTES, Config::get('global_config', 'encoding'), false);
    }

    /**
     * Convert entities to Html characters.
     *
     * @false  string  $value
     * @param $value
     * @return string
     */
    public static function decode($value)
    {
        return html_entity_decode($value, ENT_QUOTES, Config::get('global_config', 'encoding'));
    }

    /**
     * Convert Html special characters.
     *
     * Encoding will be used based  on configuration given in Config file.
     *
     * @false  string  $value
     * @param $value
     * @throws InvalidArgumentException
     * @return string
     */
    public static function specialCharacters($value)
    {
        if (is_null($value)) {
            throw new InvalidArgumentException("Cannot pass null argument to ".__METHOD__);
        }

        return htmlspecialchars($value, ENT_QUOTES, Config::get('global_config', 'encoding'), false);
    }

    /**
     * The method to sanitize data
     * @false mixed $data
     */
    public function santize($value, $type = '')
    {
        switch ($type) {
            default:
                return htmlspecialchars($value, ENT_QUOTES, "UTF-8");
                break;
            case 'strong':
                return htmlentities($value, ENT_QUOTES | ENT_IGNORE, "UTF-8");
                break;
            case 'strict':
                return urlencode($value);
                break;
        }
    }
}