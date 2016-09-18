<?php
namespace Cygnite\Foundation\Bootstrappers;

use Cygnite\Helpers\Config;
use Cygnite\Bootstrappers\Paths;
use Cygnite\Common\UrlManager\Url;
use Cygnite\Container\ContainerAwareInterface;
use Cygnite\Bootstrappers\BootstrapperInterface;

/**
 * Class ApplicationBootstraper.
 * @package Cygnite\Foundation\Bootstrappers
 */
class ApplicationBootstraper implements BootstrapperInterface
{
    private $container;

    protected $paths;

    public function __construct(ContainerAwareInterface $container, Paths $paths)
    {
        $this->container = $container;
        $this->paths = $paths;
    }

    /**
     * @throws \Exception
     */
    public function run()
    {
        define('APP', str_replace('src/', 'src'.DS, APPPATH));

        $this->container->singleton('url', function () {
            $manager = new \Cygnite\Common\UrlManager\Manager($this->container);
            //Set URL base path.
            $manager->setBase(
                (Config::get('global.config', 'base_path') == '') ?
                    $this->container['router']->getBaseUrl() :
                    Config::get('global.config', 'base_path')
            );

            return new Url($manager);
        });

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
        if (is_null(Config::get('global.config', 'default.controller'))) {
            throw new \Exception('You must set default controller in '.APPPATH.'/Configs/application.php');
        }
    }
}
