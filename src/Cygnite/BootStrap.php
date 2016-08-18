<?php
use Cygnite\Helpers\Config;
use Cygnite\Common\UrlManager\Url;

define('APP', str_replace('src/', 'src'.DS, APPPATH));

//Set URL base path.
Url::setBase(
    (Config::get('global.config', 'base_path') == '') ?
        $app['router']->getBaseUrl() :
        Config::get('global.config', 'base_path')
);

/* --------------------------------------------------
 *  Set application encryption key
 * ---------------------------------------------------
 */
if (!is_null(Config::get('global.config', 'encryption.key'))) {
    define('CF_ENCRYPT_KEY', Config::get('global.config', 'encryption.key'));
}

/*
 * ----------------------------------------------------
 * Throw Exception is default controller
 * has not been set in configuration
 * ----------------------------------------------------
 */
if (is_null(Config::get('global.config', "default.controller"))) {
    throw new \Exception("You must set default controller in ".APPPATH."/Configs/application.php");
}
