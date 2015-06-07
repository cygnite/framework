<?php
use Cygnite\Helpers\Config;
use Cygnite\Common\UrlManager\Url;

/**
 * Set Environment for Application
 * Example:
 * <code>
 * define('DEVELOPMENT_ENVIRONMENT', 'development');
 * define('DEVELOPMENT_ENVIRONMENT', 'production');
 * </code>
 */
define('ENV', Config::get('global.config', 'environment'));

if (ENV == 'development') {
    ini_set('display_errors', -1);
    error_reporting(E_ALL);
} else {
    ini_set('display_error', 0);
    error_reporting(0);
}

//Set URL base path.
Url::setBase(
    (Config::get('global.config', 'base_path') == '') ?
        $app['router']->getBaseUrl() :
        Config::get('global.config', 'base_path')
);

/* --------------------------------------------------
 *  Set Cygnite user defined encryption key
 * ---------------------------------------------------
 */
if (!is_null(Config::get('global.config', 'cf_encryption_key')) ||
    in_array('encrypt', Config::get('config.autoload', 'helpers')) == true
) {
    define('CF_ENCRYPT_KEY', Config::get('global.config', 'cf_encryption_key'));
}

/*------------------------------------------------------------------
 * Throw Exception is default controller
 * has not been set in configuration
 * ------------------------------------------------------------------
 */
if (is_null(Config::get('global.config', "default_controller"))) {
    throw new \Exception("Set Default Controller in ".APPPATH."/configs/application.php");
}