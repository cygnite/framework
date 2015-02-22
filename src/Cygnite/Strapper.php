<?php
/**
 * This file is part of the Cygnite package.
 * Bootstrap file to auto load core libraries initially.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite;

use Cygnite\Exception\Handler;
use Cygnite\Foundation\Application;
use Cygnite\Helpers\Config;
use Cygnite\Helpers\Profiler;
use Tracy\Debugger;

if (defined('CF_SYSTEM') === false) {
    exit('External script access not allowed');
}

class Strapper
{
    /**
     * Initialize and do all configuration then start booting
     */
    public function initialize($app)
    {
        /**
         * Set Environment for Application
         * Example:
         * <code>
         * define('DEVELOPMENT_ENVIRONMENT', 'development');
         * define('DEVELOPMENT_ENVIRONMENT', 'production');
         * </code>
         */
        define('MODE', Config::get('global.config', 'environment'));

        if (MODE == 'development') {
            ini_set('display_errors', 1);
            error_reporting(E_ALL);
        } else {
            ini_set('display_error', 0);
            error_reporting(0);
        }

        $app['app.event']()->trigger("exception");

        /** --------------------------------------------------
         *  Set Cygnite user defined encryption key
         * ---------------------------------------------------
         */
        if (!is_null(Config::get('global.config', 'cf_encryption_key')) ||
            in_array('encrypt', Config::get('config.autoload', 'helpers')) == true
        ) {
            define('CF_ENCRYPT_KEY', Config::get('global.config', 'cf_encryption_key'));
        }

        /**----------------------------------------------------------------
         * Get Session config and set it here
         * ----------------------------------------------------------------
         */
        define('SECURE_SESSION', Config::get('config.session', 'cf_session'));
    }

    /**
     * @return bool
     */
    public function terminate()
    {
        /**----------------------------------------------------------------
         * Auto load Session library based on user configurations
         * ----------------------------------------------------------------
         */
        if (SECURE_SESSION === true) {
            Session::instance();
        }

        /**------------------------------------------------------------------
         * Throw Exception is default controller
         * has not been set in configuration
         * ------------------------------------------------------------------
         */
        if (is_null(Config::get('global.config', "default_controller"))) {
            trigger_error(
                "Default controller not found ! Please set the default
                            controller in configs/application" . EXT
            );
        }

        Application::import(APPPATH . '.routes');
    }
}
