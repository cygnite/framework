<?php

/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cygnite\Common\File\Thumbnail;

use Cygnite\Cygnite;
use Cygnite\Inflector;

/**
 * Image Thumbnail.
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * @example
 * <code>
 *    Example:
 *    $thumb = new \Cygnite\Common\File\Thumbnail\Image();
 *    $thumb->setRootDir(CYGNITE_BASE);
 *    $thumb->directory = 'Set your directory path';
 *    $thumb->fixedWidth  = 100;
 *    $thumb->fixedHeight = 100;
 *    $thumb->thumbPath = 'your thumb path';
 *    $thumb->thumbName = 'Your thumb image name';
 *    // Optional. If you doen't want to have custom name then it will generate
 *    thumb as same name of original image.
 *    $thumb->resize();
 * </code>
 */

class Image
{
    //defined thumbs array to hold dynamic properties
    public $thumbs = array();

    //Set valid types of images to convert to thumb
    public $imageTypes = array("jpg","png","jpeg","gif");

    //Set valid type of properties to avoid exceptions
    private $validProperties = array('directory', 'fixedWidth', 'fixedHeight', 'thumbPath', 'thumbName');

    /**
     * @param $key   name of the property
     * @param $value value to set
     * @throws \Exception
     * @return void
     */
    public function __set($key, $value)
    {
        if (in_array($key, $this->validProperties) == false) {
            throw new \Exception('You are not allowed to set invalid properties. Please check guide.');
        }
        $this->thumbs[$key] = $value;
    }

    /**
     * @param $key property name
     * @return string
     */
    public function __get($key)
    {
        if (isset($this->thumbs[$key])) {
              return $this->thumbs[$key];
        }
    }

    public function setRootDir($rootPath = false)
    {
        if ($rootPath) {
            $this->rootDir = $rootPath.DS;
        } else {
            $this->rootDir = getcwd().DS;
        }
    }

    /**
     * Resize image as given configurations
     *
     * @throws \Exception
     * @return boolean
     */
    public function resize()
    {
        $path = array();
        $src = $this->rootDir.DS.str_replace(array('/','\\'), DS, $this->directory);   /* read the source image */


        if (file_exists($src)) {
            $info = getimagesize($src); // get the image size
            $path = pathinfo($src);

            if (!in_array(strtolower($path['extension']), $this->imageTypes)) {
                throw new \Exception("File type not supports");
            }

            $thumbName = ($this->thumbName == null)
                         ? $path['basename']
                         : $this->thumbName.'.'.$path['extension'];


            switch (strtolower($path['extension'])) {

                case 'jpg':
                    $sourceImage =$this->imageCreateFrom('jpeg', $src);
                    $thumbImg = $this->changeDimensions($sourceImage, $this->fixedWidth, $this->fixedHeight);
                    $this->image('jpeg', $thumbImg, $thumbName);

                    break;
                case 'png':

                    $sourceImage =$this->imageCreateFrom('png', $src);
                    $thumbImg=$this->changeDimensions($sourceImage, $this->fixedWidth, $this->fixedHeight);
                    $this->image('png', $thumbImg, $thumbName);

                    break;
                case 'jpeg':

                    $sourceImage =$this->imageCreateFrom('jpeg', $src);
                    $thumbImg=$this->changeDimensions($sourceImage, $this->fixedWidth, $this->fixedHeight);
                    $this->image('jpeg', $thumbImg, $thumbName);

                    break;
                case 'gif':
                    $sourceImage =$this->imageCreateFrom('jpeg', $src);
                    $thumbImg=$this->changeDimensions($sourceImage, $this->fixedWidth, $this->fixedHeight);
                    $this->image('gif', $thumbImg, $thumbName);

                    break;
            }

            return true;

        } else {
              throw new \Exception("404 File not found on given path");
        }
    }

    /**
     * @param      $type type of the image
     * @param      $src  image source
     * $param null function name to build dynamically
     * @param null $func
     * @return source image
     */
    private function imageCreateFrom($type, $src, $func = null)
    {
        $func = strtolower(__FUNCTION__.$type);

        return (is_callable($func))
            ? $func($src)
            : null;

    }

    /**
     * @param      $type type of the image
     * @param      $thumb
     * @param      $name
     * @param null $func
     * @throws \Exception
     * @internal param \Apps\Components\Libraries\image $src source
     * $param null function name to build dynamically
     * @return sourceImage
     */
    private function image($type, $thumb, $name, $func = null)
    {
        $func = strtolower(__FUNCTION__.$type);

        /** @var $func TYPE_NAME */
        //if (is_callable($func)) {
        if ( $func(
                $thumb,
                $this->rootDir.DS.str_replace(
                    array(
                        '/',
                        '\\'
                    ),
                    DS,
                    $this->thumbPath
                ).$name
             )
            ) {
                chmod($this->rootDir.DS.str_replace(array('/', '\\'), DS, $this->thumbPath).$name, 0777);
        } else {
                throw new \Exception("Unknown Exception  while generating thumb image");
        }
    }

    /**
     * Change dimension of the image
     * @param $sourceImage
     * @param $desiredWidth
     * @param $desiredHeight
     * @internal param \Apps\Components\Libraries\type $type of the image
     * @internal param \Apps\Components\Libraries\image $src source
     *
     * @return thumbImage
     */
    public function changeDimensions(
        $sourceImage,
        $desiredWidth,
        $desiredHeight
    ) {
        $temp = "";
        // find the height and width of the image
        if (imagesx($sourceImage) >= imagesy($sourceImage)
            && imagesx($sourceImage) >= $this->fixedWidth
        ) {
            $temp = imagesx($sourceImage) / $this->fixedWidth;
            $desiredWidth  = imagesx($sourceImage)/$temp;
            $desiredHeight = imagesy($sourceImage)/$temp;
        } elseif (imagesx($sourceImage) <= imagesy($sourceImage)
            && imagesy($sourceImage) >=$this->fixedHeight
        ) {
            $temp = imagesy($sourceImage)/$this->fixedHeight;
            $desiredWidth  = imagesx($sourceImage) /$temp;
            $desiredHeight = imagesy($sourceImage)/$temp;
        } else {
            $desiredWidth  = imagesx($sourceImage);
            $desiredHeight = imagesy($sourceImage);
        }

        // create a new image
        $thumbImg = imagecreatetruecolor($desiredWidth, $desiredHeight);
        $imgAllocate =imagecolorallocate($thumbImg, 255, 255, 255);
        imagefill($thumbImg, 0, 0, $imgAllocate);

        //copy source image to resize
        imagecopyresampled(
            $thumbImg,
            $sourceImage,
            0,
            0,
            0,
            0,
            $desiredWidth,
            $desiredHeight,
            imagesx($sourceImage),
            imagesy($sourceImage)
        );

        return $thumbImg;
    }

    public function __destruct()
    {
        unset($this->thumbs);
    }
}
