<?php
namespace Cygnite\Foundation\Bootstrappers;

use Cygnite\Helpers\Config;
use Cygnite\Bootstrappers\Paths;
use Cygnite\Common\UrlManager\Url;
use Cygnite\Container\ContainerAwareInterface;
use Cygnite\Bootstrappers\BootstrapperInterface;
use Cygnite\Mvc\View\ViewFactory;
use Cygnite\Mvc\View\Widget;

/**
 * Class ViewBootstraper.
 *
 * @package Cygnite\Foundation\Bootstrappers
 */

class ViewBootstraper implements BootstrapperInterface
{
    private $container;

    protected $paths;

    /**
     * View bootstrapper constructor.
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
        // Configure view & widget class.
        ViewFactory::make(\Cygnite\Mvc\View\View::class, $this->container, function ($v) {
            $v->setContainer($this->container);

            // Configure widget and register into container.
            $widget = new \Cygnite\Mvc\View\Widget($this->paths, $v->getOutput());
            $this->container->set('widget', $widget);
        });
    }
}
