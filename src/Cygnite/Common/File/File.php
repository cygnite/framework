<?php
/**
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cygnite\Common\File;

if (!defined('CF_SYSTEM')) {
    exit('External script access not allowed');
}

class File
{

    private $_mimeType;
    private $filePath;
    private $rootDir;

    /**
     * @param null $rootPath
     */
    public function setRootDirectory($rootPath)
    {
        $this->rootDir = $rootPath.DS;
    }

    /*
    |---------------------------
    | Get file information from file path
    |
    | @access private
    | @false  string $file_name
    | @return array - array
    |---------------------------
    */

    public function download($filePath)
    {
        $urlParts = parse_url($filePath);
        $rootDir = null;

        if (!is_null($this->rootDir)) {
            $rootDir = $this->rootDir;
        } else {
            $rootDir = CYGNITE_BASE.DS;
        }

        $filePath = $rootDir . str_replace('/', DS, str_replace('/' . ROOTDIR . '/', '', $urlParts['path']));

        if (is_null($this->filePath) && $filePath != "") {
            $this->filePath = $filePath;
        }

        $isSetFileType = $this->setMimeType($this->filePath);

        if ($isSetFileType) {
            $this->setHeaders();
        }
        //$this->filePath
    }

    /*
    |---------------------------
    | This function is to get mime type
    |
    | @access public
    | @false  null
    | @return string
    |---------------------------
    */

    private function setMimeType($file = "")
    {
        $ext = explode(".", $file);

        switch ($ext[sizeof($ext) - 1]) {
            case 'jpeg':
                $this->_mimeType = "image/jpeg";
                break;
            case 'jpg':
                $this->_mimeType = "image/jpg";
                break;
            case "gif":
                $this->_mimeType = "image/gif";
                break;
            case "png":
                $this->_mimeType = "image/png";
                break;
            case "pdf":
                $this->_mimeType = "application/pdf";
                break;
            case "txt":
                $this->_mimeType = "text/plain";
                break;
            case 'jad':
                $this->_mimeType = "text/vnd.sun.j2me.app-descriptor";
                break;
            case 'jar':
                $this->_mimeType = "application/java-archive";
                break;
            case 'zip':
                $this->_mimeType = "application/zip";
                break;
            case "doc":
                $this->_mimeType = "application/msword";
                break;
            case "docx":
                $this->_mimeType = "application/msword";
                break;
            case "xls":
                $this->_mimeType = "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";
                break;
            case "ppt":
                $this->_mimeType = "application/vnd.ms-powerpoint";
                break;
            case "wbmp":
                $this->_mimeType = "image/vnd.wap.wbmp";
                break;
            case "wmlc":
                $this->_mimeType = "application/vnd.wap.wmlc";
                break;
            case "mp4s":
                $this->_mimeType = "application/mp4";
                break;
            case "ogg":
                $this->_mimeType = "application/ogg";
                break;
            case "pls":
                $this->_mimeType = "application/pls+xml";
                break;
            case "asf":
                $this->_mimeType = "application/vnd.ms-asf";
                break;
            case "swf":
                $this->_mimeType = "application/x-shockwave-flash";
                break;
            case "mp4":
                $this->_mimeType = "video/mp4";
                break;
            case "m4a":
                $this->_mimeType = "audio/mp4";
                break;
            case "m4p":
                $this->_mimeType = "audio/mp4";
                break;
            case "mp4a":
                $this->_mimeType = "audio/mp4";
                break;
            case "mp3":
                $this->_mimeType = "audio/mpeg";
                break;
            case "m3a":
                $this->_mimeType = "audio/mpeg";
                break;
            case "m2a":
                $this->_mimeType = "audio/mpeg";
                break;
            case "mp2a":
                $this->_mimeType = "audio/mpeg";
                break;
            case "mp2":
                $this->_mimeType = "audio/mpeg";
                break;
            case "mpga":
                $this->_mimeType = "audio/mpeg";
                break;
            case "wav":
                $this->_mimeType = "audio/wav";
                break;
            case "m3u":
                $this->_mimeType = "audio/x-mpegurl";
                break;
            case "bmp":
                $this->_mimeType = "image/bmp";
                break;
            case "ico":
                $this->_mimeType = "image/x-icon";
                break;
            case "3gp":
                $this->_mimeType = "video/3gpp";
                break;
            case "3g2":
                $this->_mimeType = "video/3gpp2";
                break;
            case "mp4v":
                $this->_mimeType = "video/mp4";
                break;
            case "mpg4":
                $this->_mimeType = "video/mp4";
                break;
            case "m2v":
                $this->_mimeType = "video/mpeg";
                break;
            case "m1v":
                $this->_mimeType = "video/mpeg";
                break;
            case "mpe":
                $this->_mimeType = "video/mpeg";
                break;
            case "mpeg":
                $this->_mimeType = "video/mpeg";
                break;
            case "mpg":
                $this->_mimeType = "video/mpeg";
                break;
            case "mov":
                $this->_mimeType = "video/quicktime";
                break;
            case "qt":
                $this->_mimeType = "video/quicktime";
                break;
            case "avi":
                $this->_mimeType = "video/x-msvideo";
                break;
            case "midi":
                $this->_mimeType = "audio/midi";
                break;
            case "mid":
                $this->_mimeType = "audio/mid";
                break;
            case "amr":
                $this->_mimeType = "audio/amr";
                break;
            default:
                $this->_mimeType = "application/force-download";
        }

        return true;
    }

    /*
    |---------------------------
    | This function is to set mime type of requested file
    |
    | @access private
    | @false  string $file
    | @return boolean
    |---------------------------
    */

    private function setHeaders()
    {
        /*Execution Time unlimited*/
        set_time_limit(0);

        $fileSize = filesize($this->filePath);

        if ($fileSize === false) {
            throw new \Exception("Invalid path exception");
        }

        $_mimeType = $this->getMimeType();

        ob_start();
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $_mimeType);
        //header("Content-type: ".mime_content_type($value));
        header('Content-Disposition: attachment; filename=' . rawurlencode(basename($this->filePath)));
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . $fileSize);
        ob_clean();
        ob_end_flush();
        readfile($this->filePath);
        exit;
    }

    public function getMimeType()
    {
        if (is_null($this->_mimeType) || $this->_mimeType == "") {
            throw new \InvalidArgumentException("Empty argument passed to " . __FUNCTION__);
        }

        if (isset($this->_mimeType)) {
            return $this->_mimeType;
        }
    }

    /*
    |----------------------------------------------------------
    | This function is to set headers for file download
    |
    | @access private
    | @false  string $file
    | @return void
    |---------------------------------------------------------
    */

    private function getFileInfo($filePath)
    {
        $pathInfo = array();
        $pathInfo = pathinfo($filePath);
        //$pathInfo['dirname'];$pathInfo['basename'];$pathInfo['extension'];$pathInfo['filename'];
        return $pathInfo;
    }
}
