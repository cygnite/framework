<?php

/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\Common\File\Upload;

use InvalidArgumentException;

/**
 * Upload.
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 * @author Balamathan Kumar
 */
class Upload implements FileUploadInterface
{
    /**
     * Set the file types to upload files.
     *
     * @to do : Is it like you are setting file ext type
     *     to restrict user to upload files. If so then we need to give
     *     provision to user to set file extensions type not here.
     *
     * @var array
     */
    protected $fileInfo = [
        'ext' => [
            'jpeg',
            'png',
            'jpg',
            'gif',
            'pdf',
            'doc',
            'docx',
            'txt',
            'xlsx',
            'xls',
            'ppt',
            'pptx',
        ],
        'params' => [],
        'file'   => [],
    ];

    /**
     * @var array
     */
    private $_prefix = ['byte', 'kb', 'mb', 'gb', 'tb', 'pb'];

    /**
     * @description array validations
     *
     * @var : array
     */
    private $_validationArray = [
        'size' => [
            'func' => 'is_string',
            'msg'  => 'Size must be valid string',
        ],
        'file' => [
            'func' => 'is_array',
            'msg'  => 'File must be valid array',
        ],
        'params' => [
            'func' => 'is_array',
            'msg'  => 'Array values only valid',
        ],
        'ext' => [
            'func' => 'is_array',
            'msg'  => 'File Extensions must be valid array',
        ],
    ];

    private $rootDir;

    private $filePathInfo;

    private $error = [];

    /**
     * File Upload Constructor.
     * Set the max upload size to the configuration.
     */
    public function __construct()
    {
        $this->fileInfo['size'] = ini_get('upload_max_filesize');
        $this->setRootDir();
    }

    /**
     * This function is used to set the maximum file upload size.
     *
     * @param int $size check file size
     *
     * @throws InvalidArgumentException
     * @false   integer upload size in mb
     *
     * @return void
     */
    public function setUploadSize($size)
    {
        if (is_null($size)) {
            throw new \InvalidArgumentException('Cannot pass null argument to '.__FUNCTION__);
        }
        ini_set('upload_max_filesize', $size);
    }

    public function setRootDir($rootPath = false)
    {
        if ($rootPath) {
            $this->rootDir = $rootPath;
        } else {
            $this->rootDir = CYGNITE_BASE.DS;
        }
    }

    public function getRootDir()
    {
        return isset($this->rootDir) ? $this->rootDir : null;
    }

    private function getFileName($options)
    {
        if (isset($options['fileName']) && !is_null($options['fileName'])) {
            return $options['fileName'].'.'.$this->filePathInfo['extension'];
        }

        return $this->fileInfo['file']['name'];
    }

    private function setPathInfo()
    {
        $this->filePathInfo = pathinfo($this->fileInfo['file']['name']);
    }

    /**
     * $status = Upload::process( function($upload)
     *  {
     *    // Your code goes here
     *    $upload->file = 'document';
     *     $upload->ext = array("JPG");
     *     $upload->size = '32092';
     *    //"multiUpload"=>true
     *    if ( $upload->save(array("destination"=> "upload",  "fileName"=>"file_new_name")) ) {
     *       // Upload Success
     *    } else {
     *       // Error catch here
     *    }
     *  });.
     */
    public static function process(\Closure $callback)
    {
        return $callback(new self());
    }

    /**
     * @param $options
     *
     * @return bool
     */
    protected function uploadFile($options)
    {
        // If upload path not specified InvalidArgumentException will throw.
        if (!(isset($this->fileInfo['params']['destination'])
            && !empty($this->fileInfo['params']['destination']))
        ) {
            $this->error[] = 'Upload path required';

            return false;
        }

        if (isset($this->fileInfo['file']['name'])) {
            $this->setPathInfo();
        }

        if (isset($this->filePathInfo['extension'])) {

            // If invalid file uploaded return collect error.
            if (
            !in_array(
                strtolower($this->filePathInfo['extension']),
                array_map('strtolower', $this->fileInfo['ext'])
            )
            ) {
                $this->error[] = 'Invalid file format. Valid file format: '.implode(',', $this->fileInfo['ext']);

                return false;
            }
        }

        if (isset($this->fileInfo['file']['size']) &&
            $this->fileInfo['file']['size'] <= $this->getNumericfileSize($this->fileInfo['size'])
        ) {
            $path = str_replace(
                '/',
                DS,
                $this->getRootDir().DS.$this->fileInfo['params']['destination'].DS.$this->getFileName($options)
            );
            
            if (!is_writable($path)) {
            	$this->error[] = "$path should exists and must be writable.";

                return false;
            }

            try {
                if (move_uploaded_file($this->fileInfo['file']['tmp_name'], $path) === true) {
                    return true;
                }
            } catch (\ErrorException $e) {
                // If file was not uploaded we will catch error.
                $this->error[] = $e->getMessage();

                return false;
            }
        } else {
            // If file size was too large  OutofRange exception will throw.
            $this->error[] =
                $this->fileInfo['file']['name'].' is too large.Exceeds upload limit '.$this->fileInfo['size'];

            return false;
        }
    }

    public function __set($key, $value)
    {
        if (!isset($this->fileInfo[$key])) {
            throw new \InvalidArgumentException('Invalid : undefined variable '.__CLASS__.'::$'.$key);
        }

        if (!isset($_FILES) || !is_array($_FILES)) {
            return;
        }

        if ($key == 'file') {
            $value = $_FILES[$value];
        } else {
            if ($key == 'size') {
                $this->setUploadSize($value);
            }
        }

        if (!call_user_func($this->_validationArray[$key]['func'], $value)) {
            $this->error[] = 'Invalid type : '.__CLASS__.'::$'.$key.' '.$this->_validationArray[$key]['msg'];

            return false;
        }

        $this->fileInfo[$key] = $value;
    }

    public function __call($function, $arguments)
    {
        if ($function !== 'save') {
            throw new \ErrorException('Undefined function call :'.__CLASS__."::{$function} function name undefined");
        } elseif (empty($arguments)) {
            throw new \ErrorException('Empty argument passed to method: '.__CLASS__."::{$function}");
        }

        $this->setFileOptions('params', !isset($arguments[0]) ?: $arguments[0]);
        $this->setFileOptions('file', !isset($arguments[1]) ?: $arguments[1]);
        $this->setFileOptions('ext', !isset($arguments[2]) ?: $arguments[2]);
        $this->setFileOptions('size', !isset($arguments[3]) ?: $arguments[3]);

        $tempArguments = $this->fileInfo['file'];

        if (isset($arguments[0]) && isset($arguments[0]['multiUpload']) === true) {
            foreach ($tempArguments as $key => $value) {
                $this->fileInfo['file'] = $value;
                $status[] = call_user_func_array([$this, 'uploadFile'], []);
            }
        } else {
            return call_user_func_array([$this, 'uploadFile'], $arguments);
        }

        return $status;
    }

    private function setFileOptions($key, $value)
    {
        if (isset($value) && is_array($value) && count($value) > 0) {
            $this->fileInfo[$key] = $value;
        }
    }

    /**
     * @return string the original name of the file being uploaded
     */
    public function getName()
    {
        return $this->fileInfo['file']['name'];
    }

    /**
     * @return string the path of the uploaded file on the server.
     *                Note, this is a temporary file which will be automatically deleted by PHP
     *                after the current request is processed.
     */
    public function getTempName()
    {
        return $this->fileInfo['file']['tmp_name'];
    }

    /**
     * @return string the MIME-type of the uploaded file (such as "image/gif").
     */
    public function getType()
    {
        return $this->fileInfo['file']['type'];
    }

    /**
     * @return int the actual size of the uploaded file in bytes
     */
    public function getSize()
    {
        return $this->fileInfo['file']['size'];
    }

    public function getFileExtension()
    {
        return $this->filePathInfo['extension'];
    }

    /**
     * Return number of errors as array.
     */
    public function getError()
    {
        return $this->error;
    }

    /*
    * Has valid error
    * @return bool true/false
    */
    public function hasError()
    {
        return !empty($this->error) ? true : false;
    }

    /**
     * This function to change numeric value to it binary string
     * and to get the file size.
     *
     * @false  integer
     *
     * @param  $fileSize
     *
     * @return unknown
     *--------------------------------------------------------
     */
    private function fileSize($fileSize)
    {
        if (isNumeric($fileSize)) {
            $decr = 1024;
            $step = 0;

            while (($fileSize / $decr) > 0.9) {
                $fileSize = $fileSize / $decr;
                $step++;
            }

            return round($fileSize, 2).' '.strtoupper($this->_prefix[$step]);
        } else {
            return 'NaN';
        }
    }

    /**
     * --------------------------------------------------
     * This function is to change binary string to it numeric value.
     *
     * @todo : please change the regular expression
     *       matching in a efficient way
     *
     * @param string $str
     *
     * @return int
     *--------------------------------------------------
     */
    private function getNumericfileSize($str)
    {
        $bytes = 0;
        $str = strtoupper($str);
        $bytesArray = [
            'B'  => 0,
            'K'  => 1,
            'M'  => 2,
            'G'  => 3,
            'T'  => 4,
            'P'  => 5,
            'KB' => 1,
            'MB' => 2,
            'GB' => 3,
            'TB' => 4,
            'PB' => 5,
        ];

        $bytes = floatval($str);

        if (preg_match('#([KMGTP]?.)$#si', $str, $matches)
            && !empty($bytesArray[$matches[1]])
        ) {
            $bytes *= pow(1024, $bytesArray[$matches[1]]);
        }

        $bytes = intval(round($bytes, 2));

        return $bytes;
    }

    /**
     * Cleans up the loaded Upload instances.
     * This method is mainly used by test scripts to set up a fixture.
     */
    public function reset()
    {
        $this->fileInfo = [];
    }
}
