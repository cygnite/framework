<?php
use Cygnite\AssetManager\Asset;
use Cygnite\AssetManager\AssetCollection;
use Cygnite\Common\UrlManager\Url;
use Cygnite\Base\Router\Router;
use Cygnite\Foundation\Application;
use Cygnite\Helpers\Config;
use Mockery as m;

class AssetTest extends PHPUnit_Framework_TestCase
{
    public function testCreateAssetInstance()
    {
        $asset = Asset::create();

        $this->assertInstanceOf('Cygnite\AssetManager\Asset', $asset);
    }

    private function setUpAssetConfig()
    {
        $app = Application::instance();
        $app['router'] = new \Cygnite\Base\Router\Router;

        $_SERVER['REQUEST_URI'] = '/hello/user';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $configuration = [
            'global.config' => [
                'encoding' => 'utf-8'
            ]
        ];

        Config::$config = $configuration;

        Url::setBase('/cygnite/');//$app['router']->getBaseUrl()
    }

    public function testStaticCallToJsScript()
    {
        $this->setUpAssetConfig();

        $js = Asset::js('public/assets/jquery.js');
        $this->assertEquals(trim('<script type="text/javascript" src="http://localhost/cygnite/public/assets/jquery.js" type="static"></script>'), trim($js));
    }

    public function testStaticCallToCss()
    {
        $this->setUpAssetConfig();

        $css = Asset::css('public/assets/cygnite.css');
        
        $this->assertEquals(
            trim('<link rel="stylesheet" type="text/css" static
                   title= "" href="http://localhost/cygnite/public/assets/cygnite.css" />'
            ), $css);
    }

    public function testStaticCallToAnchorTag()
    {
        $this->setUpAssetConfig();

        $anchor = Asset::anchor('user/add', 'Add User');
        
        $this->assertEquals('<a href="http://localhost/cygnite/user/add" type="static">Add User</a>', $anchor);
    }

    public function testAssetCollectionClouserInstance()
    {
        $this->setUpAssetConfig();

        $asset = AssetCollection::make(function ($asset) {
            $asset->where('header')
                  ->add('style', ['path' => 'public/assets/css/bootstrap/css/bootstrap.min.css']);

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
        $this->setUpAssetConfig();

        $asset = AssetCollection::make(function ($asset) {
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

    public function tearDown()
    {
        m::close();
    }
}
