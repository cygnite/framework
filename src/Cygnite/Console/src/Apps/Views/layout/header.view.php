<?php
use Cygnite\Helpers\Url;
use Cygnite\Helpers\Assets;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" >
        <title><?php echo $this->title; ?> </title>
        <link rel="shortcut icon" href="<?php echo Url::getBase(); ?>webroot/img/cygnite/fevicon.png" > </link>
        <?php echo Assets::addStyle('webroot/css/cygnite/style.css'); ?>
    </head>
    <body>

        <div class="header" align="center"><?php echo $this->header_title; ?> </div>