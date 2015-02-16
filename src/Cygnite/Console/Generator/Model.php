<?php
namespace Cygnite\Console\Generator;

use Cygnite\Helpers\Inflector;

/**
 *  Cygnite Framework
 *
 *  An open source application development framework for PHP 5.3 or newer
 *
 *   License
 *
 *   This source file is subject to the MIT license that is bundled
 *   with this package in the file LICENSE.txt.
 *   http://www.cygniteframework.com/license.txt
 *   If you did not receive a copy of the license and are unable to
 *   obtain it through the world-wide-web, please send an email
 *   to sanjoy@hotmail.com so that I can send you a copy immediately.
 *
 * @Package            :  Console
 * @Filename           :  Model.php
 * @Description        :  This class is used to generate your model code using cygnite console
 * @Author             :  Sanjoy Dey
 * @Copyright          :  Copyright (c) 2013 - 2014,
 * @Link	           :  http://www.cygniteframework.com
 * @Since	           :  Version 1.0.6
 * @File Source
 *
 */
class Model
{
    private $command;

    const EXTENSION = '.php';

    private $modelTemplatePath;

    private $filePointer;

    private $replacedContent;

    /*
     * Since constructor is private you cannot create object
     * for this class directly
     *
     * @access private
     * @param $inflect instance of Inflector
     * @param $columns array of columns
     * @return void
     */
    private function __construct(Inflector $inflect, $command = null)
    {
        if ($inflect instanceof Inflector) {
            $this->inflector = $inflect;
        }
        $this->command = $command;

    }

    public static function __callStatic($method, $arguments = array())
    {
        if ($method == 'instance') {
            return new self($arguments[0], $arguments[1]);
        }
    }

    public function setModelTemplatePath($path)
    {
        $this->modelTemplatePath = $path;
    }

    public function getModelTemplatePath()
    {
        return (isset($this->modelTemplatePath)) ?
            $this->modelTemplatePath :
            null;
    }

    private function modelTemplateName()
    {
        return 'Model'.self::EXTENSION;
    }

    public function updateTemplate()
    {
        $file = $this->getModelTemplatePath().$this->modelTemplateName();
        //file_exists($file) or die("Model Template File doesn't exists");

        /*read operation ->*/
        $this->filePointer = fopen($file, "r");
        $content = fread($this->filePointer, filesize($file));
        $content = $this->replaceModelTemplate($content);

        $this->replacedContent = $content;
    }

    private function replaceModelTemplate($content)
    {
        $content = str_replace('%StaticModelName%',
            $this->command->model,
            $content
        );

        $primaryKey = $this->command->getPrimaryKey();
        $content = str_replace('{%Apps%}', ucfirst(APP_PATH), $content);
        $content = str_replace('{%primaryKey%}', $primaryKey, $content);
        $content = str_replace('%modelName%', $this->inflector->tabilize($this->command->model), $content);
        $content = str_replace('%databaseName%', $this->command->database, $content);

        return $content;

    }

    public function generate()
    {
        $filePath = '';
        $filePath =  $this->command->applicationDir.
            DS.'models'.
            DS.
            $this->command->model
            .'.php';

        /*write operation ->*/
        $writeTmp =fopen(
            $filePath,
            "w"
        ) or die('Unable to generate model');

        $contentAppendWith = '<?php '.PHP_EOL;

        fwrite($writeTmp, $contentAppendWith .$this->replacedContent);
        fclose($writeTmp);
        fclose($this->filePointer);
    }

}