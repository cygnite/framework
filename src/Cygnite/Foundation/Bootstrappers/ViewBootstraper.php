<?php
namespace Cygnite\Foundation\Bootstrappers;

use Cygnite\Helpers\Config;
use Cygnite\Bootstrappers\Paths;
use Cygnite\Common\UrlManager\Url;
use Cygnite\Container\ContainerAwareInterface;
use Cygnite\Bootstrappers\BootstrapperInterface;
use Cygnite\Mvc\View\ViewFactory;

/**
 * Class ViewBootstraper.
 * @package Cygnite\Foundation\Bootstrappers
 */
class ViewBootstraper implements BootstrapperInterface
{
    private $container;

    protected $paths;

    /**
     * View bootstrapper constructor
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
     * Create, configure view and store view instance
     * in container
     */
    public function run()
    {
        ViewFactory::make(\Cygnite\Mvc\View\View::class, $this->container, function ($v) {
            $v->setContainer($this->container);
        });
    }
}
