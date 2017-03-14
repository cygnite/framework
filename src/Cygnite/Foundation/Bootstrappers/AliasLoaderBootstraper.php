<?php
namespace Cygnite\Foundation\Bootstrappers;

use Cygnite\Alias\Manager;
use Cygnite\Helpers\Config;
use Cygnite\Bootstrappers\Paths;
use Cygnite\Container\ContainerAwareInterface;
use Cygnite\Bootstrappers\BootstrapperInterface;

/**
 * Class AliasLoaderBootstraper.
 *
 * @package Cygnite\Foundation\Bootstrappers
 */
class AliasLoaderBootstraper implements BootstrapperInterface
{
    /** @var ContainerAwareInterface  */
    private $container;

    /** @var Paths  */
    protected $paths;

    private $alias;

    /**
     * AliasLoaderBootstraper constructor.
     *
     * @param ContainerAwareInterface $container
     * @param Paths $paths
     */
    public function __construct(ContainerAwareInterface $container, Paths $paths)
    {
        $this->container = $container;
        $this->paths = $paths;
    }

    /**
     * Register Log instance into Container.
     */
    public function run()
    {
        $config = Config::get('global.config', 'aliases');
        $alias = new Manager($config['classes']);
        $alias->register();
        $this->container->set('alias', $alias);
    }
}
