<?php
namespace Cygnite\Libraries;

use InvalidArgumentException;

if (defined('CF_SYSTEM') === false) {
    exit('No External script access allowed');
}
/**
 *  Cygnite Framework
 *
 *  An open source application development framework for PHP 5.3x or newer
 *
 *   License
 *
 *   This source file is subject to the MIT license that is bundled
 *   with this package in the file LICENSE.txt.
 *   http://www.cygniteframework.com/license.txt
 *   If you did not receive a copy of the license and are unable to
 *   obtain it through the world-wide-web, please send an email
 *   to sanjoy@hotmail.com so I can send you a copy immediately.
 *
 * @package                    :  Packages
 * @sub packages               :  Library
 * @filename                   :  Upload
 * @description                :  This library used to handle all errors
 *                                or exceptions of Cygnite Framework.
 * @author                     :  Balamathan Kumar
 * @author                     :  Sanjoy Dey <sanjoy09@hotmail.com>
 * @copyright                  :  Copyright (c) 2013 - 2014,
 * @link	                   :  http://www.cygniteframework.com
 * @since	                   :  Version 1.0
 * @filesource
 * @warning                    :  Any changes in this library can cause
 * abnormal behaviour of the framework
 *
 *
 */

class Upload
{

    /**
     * Set the file types to upload files
     * @to do : Is it like you are setting file ext type
     *  to restrict user to upload files. If so then we need to give
     *  provision to user to set file extensions type not here.
     * @type array
     */
    private $_fileRequirements = array(
                                  'ext'    => array(
                                               'jpeg','png','jpg',
                                               'gif','pdf','doc',
                                               'docx','txt','xlsx',
                                               'xls','ppt','pptx',
                                              ),
                                  'params' => array(),
                                  'file'   => array()
                                 );

    /**
     * @access private
     * @type    array
     */
    private $_prefix = array(
                        'byte','kb','mb','gb','tb','pb',
                       );

    /**
     * @description array validations
     * @access  :  private
     * @type    :  array
     */
    private $_validationArray = array(
        'size'   => array(
                  'func' => 'is_string',
                  'msg'  => 'String values only valid',
                 ),
        'file'   => array(
                  'func' => 'is_array',
                  'msg'  => 'Array values only valid',
                 ),
        'params' => array(
                  'func' => 'is_array',
                  'msg'  => 'Array values only valid',
                 ),
        'ext'    => array(
                  'func' => 'is_array',
                  'msg'  => 'Array values only valid',
                 )
        );


    /**
     * File Upload Constructor.
     * Set the max upload size to the configuration
     *
     * @access public
     */
    public function __construct()
    {
        $this->_fileRequirements['size'] = ini_get('upload_max_filesize');
    }

    /**
     * This function is used to set the maximum file upload size
     *
     * @access  public
     * @param integer $size check file size
     * @throws  InvalidArgumentException
     * @false   integer upload size in mb
     * @return  void
     */
    public function setUploadSize($size)
    {
        if (is_null($size)) {
            throw new \InvalidArgumentException(
                'Cannot pass null argument to '.__FUNCTION__
            );
        }

        ini_set('upload_max_filesize', $size);

    }//end setUploadSize()


    /**
     *
     *
     * @throws \ErrorException
     * @throws \OutOfRangeException
     * @throws \InvalidArgumentException
     * @return bool
     */

    private function uploadFile()
    {
        // If upload path not specified InvalidArgumentException will throw.
        if (!(isset($this->_fileRequirements['params']['upload_path'])
            && !empty($this->_fileRequirements['params']['upload_path']))
        ) {

            throw new \InvalidArgumentException('Upload path required');
        }
        $pathArray = array();
        show($this->_fileRequirements);
        if (isset($this->_fileRequirements['file']['name'])) {
            $pathArray = pathinfo($this->_fileRequirements['file']['name']);
        }

        if (isset($pathArray['extension'])) {
        // If invalid file uploaded InvalidArgumentException will throw.
        /** @var $this TYPE_NAME */
        if (in_array(
            strtolower(
                $pathArray['extension']
            ),
            $this->_fileRequirements['ext']
        ) === false
        ) {
            throw new \InvalidArgumentException(
                "<span style='color: #D8000C;' >
                Invalid file upload: Following formats only allowed
                ".implode(
                ',',
                $this->_fileRequirements['ext']
                )."</span>"
            );
        }
    }


        if (isset($this->_fileRequirements['file']['size']) && $this->_fileRequirements['file']['size'] <=
            $this->getNumericfileSize($this->_fileRequirements['size'])
        ) {
            $path = getcwd().$this->_fileRequirements['params']['upload_path'].'/'.$this->_fileRequirements['file']['name'];
            if (move_uploaded_file(
                    $this->_fileRequirements['file']['tmp_name'],
                    $path
                )
                === true
            ) {
                return true;
            } else {
                // If file was not uploaded successfully  ErrorException will throw.
                throw new \ErrorException(
                    '<span style="color: #00B050;">
                    '.$this->_fileRequirements["file"]["name"].'
                    was not uploaded successfully</span>'
                );
            }
        } else {
            echo "sasssa";
            // If file size was too large  OutofRange exception will throw.
            throw new \OutOfRangeException(
                '<span style="color: #D8000C;" >
                '.$this->_fileRequirements['file']['name'].'
                was too large exceeds upload limit
                '.$this->_fileRequirements['size'].'
                </span>'
            );
        }//end if

    }//end uploadFile()


    public function __set($key, $value)
    {
        if (!isset($this->_fileRequirements[$key])) {
            throw new \InvalidArgumentException(
                'Invalid : undefined variable '.__CLASS__.'::$'.$key
            );
        }

        if (!call_user_func($this->_validationArray[$key]['func'], $value)) {
            throw new \InvalidArgumentException(
                'Invalid type : '.__CLASS__.'::$'.$key.'
                 '.$this->_validationArray[$key]['msg']
            );
        }

        $this->_fileRequirements[$key] = $value;

    }//end __set()


    public function __call($function, $arguments)
    {
        if ($function !== 'upload') {
            throw new \ErrorException(
                'Undefined function call :
                '.__CLASS__."::{$function} function name undefined"
            );
        }


        if (isset($arguments[0])
            && is_array($arguments[0])
            && count($arguments[0]) > 0
        ) {
            $this->_fileRequirements['params'] = $arguments[0];
        }

        if (isset($arguments[1])
            && is_array($arguments[1])
            && count($arguments[1]) > 0
        ) {
            $this->_fileRequirements['file'] = $arguments[1];
        }


        if (isset($arguments[2])
            && is_array($arguments[2])
            && count($arguments[2]) > 0
        ) {
            $this->_fileRequirements['ext'] = $arguments[2];
        }


        if (isset($arguments[3]) && is_array($arguments[3])&& count($arguments[3]) > 0) {
            $this->_fileRequirements['size'] = $arguments[3];
        }


        $temp_arguments = $this->_fileRequirements['file'];

        if (isset($arguments[0]) && isset($arguments[0]['multi_upload']) === true) {
            foreach ($temp_arguments as $key => $value) {
                $this->_fileRequirements['file'] = $value;
                call_user_func_array(array($this, 'uploadFile'), array());
            }
        } else {
            call_user_func_array(array($this, 'uploadFile'), array());
        }
    }


    /**
     *
     * This function to change numeric value to it binary string
     * and to get the file size
     *
     * @access private
     * @false  integer
     * @param  $fileSize
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
     * This function is to change binary string to it numeric value
     * @todo : please change the regular expression
     * matching in a efficient way
     * @param string $str
     * @return int
     *--------------------------------------------------
     *
     */
    private function getNumericfileSize($str)
    {

        $bytes = 0;
        $str = strtoupper($str);
        $bytesArray = array(
                        'B' => 0,
                           'K' => 1,
                           'M' => 2,
                           'G' => 3,
                           'T' => 4,
                           'P' => 5,
                        'KB' => 1,
                           'MB' => 2,
                           'GB' => 3,
                           'TB' => 4,
                           'PB' => 5,
                        );

        $bytes = floatval($str);

        if (preg_match('#([KMGTP]?.)$#si', $str, $matches)
            && !empty($bytesArray[$matches[1]])
        ) {
            $bytes *= pow(1024, $bytesArray[$matches[1]]);
        }

        $bytes = intval(round($bytes, 2));

        return $bytes;

    }

    public function __destruct()
    {
        unset($this->_fileRequirements);
        unset($this->_prefix);
        unset($this->_validationArray);
    }
}
