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

/**
 * Image Thumbnail.
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * @example
 * <code>
 *    Example:
 *    $image = new \Cygnite\Common\File\Thumbnail\Image(CYGNITE_BASE, [
 *          'imagePath' => 'public/upload/image-name.png',
 *          'fixedHeight' => 160,
 *          'fixedWidth' => 130,
 *          'thumbPath' => 'public/upload/thumbnails/',
 *          'thumbName' => 'new-thumb-name',
 *    ]);
 *    $image->resize();
 * </code>
 */
class Image
{
    public $rootDir;
    //Set valid types of images to convert to thumb
    protected $imageTypes = ['jpg', 'png', 'jpeg', 'gif'];

    protected $imagePath;
    protected $fixedWidth;
    protected $fixedHeight;
    protected $thumbPath;
    protected $thumbName;
    //Set valid type of properties to avoid exceptions
    protected $validProperties = ['imagePath', 'fixedWidth', 'fixedHeight', 'thumbPath', 'thumbName'];

    /**
     * Image constructor.
     *
     * @param null $rootDir Set Root directory of the image.
     * @param array $properties Array of property value to be set.
     */
    public function __construct($rootDir = null, $properties = [])
    {
        if (!is_null($rootDir)) {
            $this->setRootDir($rootDir);
        }

        if (is_array($properties) && !empty($properties)) {
            $this->setProperties($properties);
        }
    }

    /**
     * Set all the properties from given array values.
     *
     * @param array $properties
     * @throws \RuntimeException
     * @throws \Exception
     */
    public function setProperties(array $properties = [])
    {
        foreach ($properties as $property => $value) {
            $method = 'set'.ucfirst($property);
            if (!method_exists($this, $method)) {
                throw new \RuntimeException("Undefined method $method.");
            }

            if (!in_array($property, $this->validProperties)) {
                throw new \Exception('You are trying to set invalid properties. Please check valid properties.');
            }

            $this->{$method}($value);
        }
    }

    /**
     * Set Image path, will be used to create thumbnail from.
     *
     * @param $path
     */
    public function setImagePath($path)
    {
        $this->imagePath = $path;
    }

    /**
     * Get the image path.
     *
     * @return string
     */
    public function getImagePath()
    {
        return $this->imagePath;
    }

    /**
     * Set fixed width for thumbnail image.
     *
     * @param $width
     */
    public function setFixedWidth($width)
    {
        $this->fixedWidth = $width;
    }

    /**
     * Get the thumbnail image width.
     *
     * @return string
     */
    public function getFixedWidth()
    {
        return $this->fixedWidth;
    }

    /**
     * Set the fixed height for thumbnail.
     *
     * @param $height
     */
    public function setFixedHeight($height)
    {
        $this->fixedHeight = $height;
    }

    /**
     * Get the height of thumbnail.
     *
     * @return string
     */
    public function getFixedHeight()
    {
        return $this->fixedHeight;
    }

    /**
     * Set the new thumbnail image path.
     * @param $thumbPath
     */
    public function setThumbPath($thumbPath)
    {
        $this->thumbPath = $thumbPath;
    }

    /**
     * Get the thumb path.
     *
     * @return string
     */
    public function getThumbPath()
    {
        return $this->thumbPath;
    }

    /**
     * Set the thumbnail name.
     *
     * @param $thumbName
     */
    public function setThumbName($thumbName)
    {
        $this->thumbName = $thumbName;
    }

    /**
     * Get the thumbnail name;
     * @return string
     */
    public function getThumbName()
    {
        return $this->thumbName;
    }

    /**
     * Set the root directory of image.
     *
     * @param bool $rootPath
     * @return $this
     */
    public function setRootDir($rootPath = false)
    {
        if (!$rootPath) {
            $this->rootDir = getcwd().DS;
        }

        $this->rootDir = $rootPath.DS;

        return $this;
    }

    /**
     * Resize image as given configurations.
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function resize()
    {
        $src = $this->rootDir.DS.str_replace(['/', '\\'], DS, $this->imagePath);   /* read the source image */

        if (!file_exists($src)) {
            throw new \Exception("File not found in given path $src.");
        }

        $info = getimagesize($src); // get the image size
        $path = pathinfo($src);
        $extension = strtolower($path['extension']);

        if (!in_array($extension, $this->imageTypes)) {
            throw new \Exception('File type not supported!');
        }

        $thumbName = is_null($this->thumbName)
            ? $path['basename']
            : $this->thumbName.'.'.$path['extension'];

        $sourceImage = $this->imageCreateFrom(($extension == 'gif') ? 'jpeg' : $extension, $src);
        $thumbImg = $this->changeDimensions($sourceImage, $this->fixedWidth, $this->fixedHeight);
        $this->image($extension, $thumbImg, $thumbName);

        return true;
    }

    /**
     * @param      $type type of the image
     * @param      $src  image source
     *                   $param null function name to build dynamically
     * @param null $func
     *
     * @return source image
     */
    protected function imageCreateFrom($type, $src, $func = null)
    {
        $func = strtolower('imageCreateFrom'.$type);

        return (is_callable($func)) ? $func($src) : null;
    }

    /**
     * Create/Resize image.
     *
     * @param $type
     * @param $thumb
     * @param $name
     * @param null $func
     * @throws \Exception
     */
    protected function image($type, $thumb, $name, $func = null)
    {
        $func = strtolower(__FUNCTION__.$type);
        $rootPath = $this->rootDir.DS.str_replace(['/', '\\'], DS, $this->thumbPath).$name;
        /** @var $func TYPE_NAME */
        //if (is_callable($func)) {
        if (!$func($thumb, $rootPath)) {
            throw new \Exception('Unknown Exception while generating thumb image');
        }

        chmod($rootPath, 0777);
    }

    /**
     * Change dimension of the image.
     *
     * @param $sourceImage
     * @param $desiredWidth
     * @param $desiredHeight
     *
     * @return thumbImage
     */
    public function changeDimensions($sourceImage, $desiredWidth, $desiredHeight)
    {
        $temp = '';
        // find the height and width of the image
        if (imagesx($sourceImage) >= imagesy($sourceImage)
            && imagesx($sourceImage) >= $this->fixedWidth
        ) {
            $temp = imagesx($sourceImage) / $this->fixedWidth;
            $desiredWidth = imagesx($sourceImage) / $temp;
            $desiredHeight = imagesy($sourceImage) / $temp;
        } elseif (imagesx($sourceImage) <= imagesy($sourceImage)
            && imagesy($sourceImage) >= $this->fixedHeight
        ) {
            $temp = imagesy($sourceImage) / $this->fixedHeight;
            $desiredWidth = imagesx($sourceImage) / $temp;
            $desiredHeight = imagesy($sourceImage) / $temp;
        } else {
            $desiredWidth = imagesx($sourceImage);
            $desiredHeight = imagesy($sourceImage);
        }

        // create a new image
        $thumbImg = imagecreatetruecolor($desiredWidth, $desiredHeight);
        $imgAllocate = imagecolorallocate($thumbImg, 255, 255, 255);
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
}
