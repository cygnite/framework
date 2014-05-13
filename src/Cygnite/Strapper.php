<?php
namespace Cygnite;

use Tracy\Debugger;
use Cygnite\Helpers\Config;
use Cygnite\Helpers\Profiler;
use Cygnite\Exception\Handler;

if (defined('CF_SYSTEM') === false) {
    exit('External script access not allowed');
}

/**
* Cygnite Framework
*
* An open source application development framework for PHP 5.3x or newer
*
* License
*
* This source file is subject to the MIT license that is bundled
* with this package in the file LICENSE.txt.
* http://www.cygniteframework.com/license.txt
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to sanjoy@hotmail.com so I can send you a copy immediately.
*
* @Package               :  Cygnite Framework BootStrap file
* @Filename              :  Strapper.php
* @Description           :  Bootstrap file to auto load core libraries initially.
* @Author                :  Sanjoy Dey
* @Copyright             :  Copyright (c) 2013 - 2014,
* @Link	                 :  http://www.cygniteframework.com
* @Since	             :  Version 1.0
* @FilesSource
*
*/

class Strapper
{
    /**
     * Initialize and do all configuration then start booting
     */
    public function initialize()
	{
        /**
         * Set Environment for Application
         * Example:
         * <code>
         * define('DEVELOPMENT_ENVIRONMENT', 'development');
         * define('DEVELOPMENT_ENVIRONMENT', 'production');
         * </code>
         */
        define('MODE', Config::get('global_config', 'environment'));

        global $event;

        if (MODE == 'development') {
            ini_set('display_errors', 1);
            error_reporting(E_ALL);
        } else {
            ini_set('display_error', 0);
            error_reporting(0);
        }
        $event->trigger("exception");

	   /**
		 *--------------------------------------------------
		 * Turn on benchmarking application if profiling is on
		 * in configuration
		 *--------------------------------------------------
		 */

		if (Config::get('global_config', 'enable_profiling') == true) {
			  Profiler::start();
		}

		/** --------------------------------------------------
		 *  Set Cygnite user defined encryption key
		 * ---------------------------------------------------
		*/
		 if (!is_null(Config::get('global_config', 'cf_encryption_key')) ||
			in_array('encrypt', Config::get('autoload_config', 'helpers')) == true ) {
			define('CF_ENCRYPT_KEY', Config::get('global_config', 'cf_encryption_key'));
		}

		/**----------------------------------------------------------------
		 * Get Session config and set it here
		 * ----------------------------------------------------------------
		 */
		define('SECURE_SESSION', Config::get('session_config', 'cf_session'));

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
		if (is_null(Config::get('global_config', "default_controller"))) {
			trigger_error(
				"Default controller not found ! Please set the default
							controller in configs/application".EXT
			);
		}
	}

    /**
     * @return bool
     */
    public function terminate()
	{
		/**-------------------------------------------------------------------
		 * Check if it is running via cli and return false
		 * -------------------------------------------------------------------
		 */
		$filename = preg_replace('#(\?.*)$#', '', $_SERVER['REQUEST_URI']);

		if (php_sapi_name() === 'cli-server' && is_file($filename)) {
			return false;
		}

		Application::import('apps.routes');
    }
}
