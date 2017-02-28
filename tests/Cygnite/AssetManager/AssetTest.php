<?php

use Cygnite\AssetManager\Asset;
use Cygnite\AssetManager\AssetCollection;
use Cygnite\Common\UrlManager\Url;
use Cygnite\Foundation\Application;
use Cygnite\Helpers\Config;
use PHPUnit\Framework\TestCase;

class AssetTest extends TestCase
{
    private $container;

    private $asset;

    public function setUp()
    {
        $containerDependency = new \Cygnite\Tests\Container\ContainerDependency();
        $this->container = new \Cygnite\Container\Container(
            $containerDependency->getInjector(),
            $containerDependency->getDefinitiions(),
            $containerDependency->getControllerNamespace()
        );
        $this->setUpAssetConfig();
        $this->asset = new Asset($this->container);
    }

    public function testCreateAssetInstance()
    {
        $this->assertInstanceOf('Cygnite\AssetManager\Asset', $this->asset);
    }

    private function setUpAssetConfig()
    {
        $this->container['request'] = \Cygnite\Http\Requests\Request::createFromGlobals();
        $this->container['router'] = $this->container->make(\Cygnite\Router\Router::class);
        $url = new \Cygnite\Common\UrlManager\Url(new \Cygnite\Common\UrlManager\Manager($this->container));
        $configuration = [
            'global.config' => [
                'encoding' => 'utf-8',
            ],
        ];
        Config::$config = $configuration;
        $url->setBase('localhost/cygnite/');
    }

    public function testStaticCallToJsScript()
    {
        $js = $this->asset->js('public/assets/jquery.js');
        $this->assertEquals(trim('<script type="text/javascript" src="http://localhost/cygnite/public/assets/jquery.js" type="static"></script>'), trim($js));
    }


    public function testStaticCallToCss()
    {
        $css = $this->asset->style('public/assets/cygnite.css', 'static');
        $this->assertEquals(trim(preg_replace('/\s+/',' ', '<link rel="stylesheet" type="text/css" static 
                                    title= "" href="http://localhost/cygnite/public/assets/cygnite.css" />')), trim(preg_replace('/\s+/',' ', $css)));
    }


    public function testStaticCallToAnchorTag()
    {
        $anchor = $this->asset->anchor('user/add', 'Add User');

        $this->assertEquals('<a href="http://localhost/cygnite/user/add" type="static">Add User</a>', $anchor);
    }

    public function testAssetCollectionClouserInstance()
    {
        $asset = AssetCollection::make($this->container, function ($collection) {
            $asset = $collection->asset();
            $asset->where('header')
                  ->add('style', ['path' => 'public/assets/css/bootstrap.min.css']);
            $asset->where('footer')
                  ->add('script', ['path' => 'public/assets/js/cygnite/jquery/1.10.1/jquery.min.js']);
            $asset->where('sidebar')
                  ->add('link', ['path' => 'home/index', 'name' => 'Welcome to Cygnite Framework']);

            return $asset;
        });

        $this->assertInstanceOf('Cygnite\AssetManager\Asset', $asset);
    }

    public function testAssetCollectionDump()
    {
        $asset = AssetCollection::make($this->container, function ($collection) {
            $asset = $collection->asset();
            $asset->where('header')
                  ->add('style', ['path' => 'public/assets/css/bootstrap/css/bootstrap.min.css']);
            $asset->where('footer')
                  ->add('script', ['path' => 'public/assets/js/cygnite/jquery/1.10.1/jquery.min.js']);
            $asset->where('sidebar')
                  ->add('link', ['path' => 'home/index', 'name' => 'Welcome to Cygnite Framework']);

            return $asset;
        });

        ob_start();
        $asset->where('header')->dump('style');
        $this->assertEquals('<link rel="stylesheet" type="text/css" title= "" href="http://localhost/cygnite/public/assets/css/bootstrap/css/bootstrap.min.css" />',
                         trim(ob_get_contents()));
        ob_clean();
        // Cleanup
        ob_end_clean();

        // We will test script dumping
        ob_start();
        $asset->where('footer')->dump('script');

        $this->assertEquals('<script type="text/javascript" src="http://localhost/cygnite/public/assets/js/cygnite/jquery/1.10.1/jquery.min.js"></script>', trim(ob_get_contents()));
        ob_clean();
        // Cleanup
        ob_end_clean();


        ob_start();
        $asset->where('sidebar')->dump('link');
        $this->assertEquals('<a href="http://localhost/cygnite/home/index" >Welcome to Cygnite Framework</a>', trim(ob_get_contents()));
        ob_clean();
        // Cleanup
        ob_end_clean();
    }
}
