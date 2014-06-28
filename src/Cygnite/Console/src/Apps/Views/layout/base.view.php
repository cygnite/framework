<?php
use Cygnite\AssetManager\Asset;
use Cygnite\AssetManager\AssetCollection;
use Cygnite\Foundation\Application;
use Cygnite\Common\UrlManager\Url;

$asset =  AssetCollection::make(function($asset)
    {
        $asset->add('style', array('path' => 'assets/twitter/bootstrap/css/bootstrap-theme.min.css'))
              ->add('style', array('path' => 'assets/twitter/bootstrap/css/bootstrap.min.css', 'media' => '', 'title' => ''))
              ->add('style', array('path' => 'assets/css/cygnite/table.css'))
              ->add('style', array('path' => 'assets/js/tablesorter/css/theme.default.css'))//Pick a theme, load the plugin & initialize plugin
              ->add('style', array('path' => 'assets/css/cygnite/style.css'))
              ->add('script', array('path' => 'assets/js/cygnite/jquery.js'))
              ->add('script', array('path' => 'assets/js/custom.js'))
              ->add('script', array('path' => 'assets/twitter/bootstrap/js/bootstrap.js'))
              ->add('script', array('path' => 'assets/js/tablesorter/js/jquery.tablesorter.min.js'));

        return $asset;
    });
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $this->title; ?></title>
    <?php $asset->dump('style');// Style block ?>
</head>
<body>

<div class='container'>
    <?php echo $yield;//your content block ?>
</div>
<?php
//Script block. Scripts will render here
$asset->dump('script');
?>
<style type="text/css">
    tr:hover { background-color: #4DC7EB !important; }
</style>
</body>
</html>