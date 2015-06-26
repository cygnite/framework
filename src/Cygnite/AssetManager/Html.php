<?php
/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\AssetManager;

use Cygnite\Helpers\Config;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

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
        return htmlentities($value, ENT_QUOTES, Config::get('global.config', 'encoding'), false);
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
        return html_entity_decode($value, ENT_QUOTES, Config::get('global.config', 'encoding'));
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

        return htmlspecialchars($value, ENT_QUOTES, Config::get('global.config', 'encoding'), false);
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