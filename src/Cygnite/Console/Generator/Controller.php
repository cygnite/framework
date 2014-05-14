<?php
namespace Cygnite\Console\Generator;

use Cygnite\Inflector;

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
 * @Package           :  Console
 * @Filename          :  Controller.php
 * @Description       :  This class is used to create your controller code using cygnite console
 * @Author            :  Sanjoy Dey
 * @Copyright         :  Copyright (c) 2013 - 2014,
 * @Link	          :  http://www.cygniteframework.com
 * @Since	          :  Version 1.0.6
 * @File Source
 *
 */
class Controller
{
    /**
     * Controller template replacement
     *  #controllerName
	 *	#modelName
	 *	#%model Columns%
     *  #%StaticModelName%
     *  #%$validate->addRule%
     */
    private $inflector;

    private $columns = array();

    private $controller;

    private $controllerTemplatePath;

    private $model;

    private $form;

    private $dbCode;

    private $validationCode;

    private $replacedContent;

    private $applicationDir;

    private $filePointer;


    /*
     * Since constructor is private you cannot create object
     * for this class directly
     *
     * @access private
     * @param $inflect instance of Inflector
     * @param $columns array of columns
     * @return void
     */
    private function __construct(Inflector $inflect, $columns = array())
    {
        if ($inflect instanceof Inflector) {
            $this->inflector = $inflect;
        }
        $this->columns = $columns;
    }

    public function setControllerTemplatePath($path)
    {
        $this->controllerTemplatePath = $path;
    }

    public function getControllerTemplatePath()
    {
        return (isset($this->controllerTemplatePath)) ?
            $this->controllerTemplatePath :
            null;
    }

    public function setControllerName($name)
    {
        $this->controller = $name;
    }

    public function setModelName($name)
    {
        $this->model = $name;
    }

    private function buildFormOpen()
    {
        return "\t".'$form = Form::instance()
                            ->open(
                                "'.$this->controller.'",
                                array(
                                    "method" => "post",
                                    "action" => Url::sitePath("'.
                                strtolower(
                                    str_replace('Controller', '', $this->controller)
                                ).'/type/$id/$pageNumber"),
                                    "id"     => "uniform",
                                    "role"   => "form"
                                )
                            )';
    }

    private function generateFormElements($value)
    {
        $form = '';
        $form .= "\t\t".'->addElement("label", "'.$this->inflector->underscoreToSpace($value->column_name).'",
                                      array( "class" => "col-sm-2 control-label",))'.PHP_EOL;
        $form .= "\t\t".'->addElement("text", "'.$value->column_name.'", array(
                                      "value" => (isset($'.
            strtolower(
                str_replace("Controller", "", $this->controller)
            ).'->'.$value->column_name.')) ? $'.
            strtolower(str_replace("Controller", "", $this->controller)).'->'.$value->column_name.' : "",
                            "class" => "form-control",
                            )
                        )'.PHP_EOL;

        return $form;
    }

    private function buildFormCloseTags()
    {
        $form = '';
        $form .= "\t\t".'->addElement("submit", "btnSubmit", array(
                    "value" => "Save",
                    "class" => "btn btn-primary",
                    "style" => "margin-top:15px;"
                )
                )'.PHP_EOL;

        $form .= "\t\t".'->close()'.PHP_EOL;
        $form .= "\t\t".'->createForm();'.PHP_EOL;

        return $form;
    }

    private function generateDbCode($value)
    {
        $code = '';
        $code .=
            "\t \t".'$'.$this->model.'->'.$value->column_name.' = $postArray["'.$value->column_name.'"];'.PHP_EOL;

        return $code;
    }

    private function generateValidator($value)
    {
        $validationCode = '';
        $validationCode .= "\t \t"."->addRule('".$value->column_name."', 'required|min:5')".PHP_EOL;

        return $validationCode;
    }


    public function updateTemplate()
    {

        $codeDb = $validationCode = $form = '';

        $form = $this->buildFormOpen();

        foreach ($this->columns as $key=> $value) {

            if ($value->column_name !== 'id') {

                $codeDb .= $this->generateDbCode($value);
                $validationCode .= $this->generateValidator($value);
                $form .= $this->generateFormElements($value);

            }
        }

        $form .= $this->buildFormCloseTags();

        $this->setForm($form);
        $this->setDbCode($codeDb);
        $this->setValidationCode($validationCode.';');
    }

    private function setForm($form)
    {
        $this->form = $form;
    }


    private function getForm()
    {
        return (is_string($this->form) && $this->form !== '') ?
            $this->form :
            null;
    }

    private function setDbCode($code)
    {
        $this->dbCode = $code;
    }


    private function getDbCode()
    {
        return (is_string($this->dbCode) && $this->dbCode !== '') ?
            $this->dbCode :
            null;
    }

    private function setValidationCode($code)
    {
        $this->validationCode = $code;
    }


    private function getValidationCode()
    {
        return (is_string($this->validationCode) && $this->validationCode !== '') ?
            $this->validationCode :
            null;
    }

    private function replaceControllerName($content)
    {
        $content = str_replace(
            'class %controllerName%',
            'class '.$this->inflector->covertAsClassName($this->controller),
            $content
        );
        $content = str_replace(
            '%controllerName%',
            strtolower(
                str_replace("Controller", "", $this->controller)
            ),
            $content
        );

        return $content;
    }

    private function replaceModelName($content)
    {
        $newContent = '';
        $content = str_replace(
            'new %modelName%',
            'new '.$this->inflector->covertAsClassName($this->model),
            $content
        );


        $content = str_replace(
            '%StaticModelName%',
            $this->inflector->covertAsClassName($this->model),
            $content
        );

        $newContent = str_replace('%modelName%', $this->model, $content);

        return $newContent;
    }


    private function controllerTemplateName()
    {
        return basename( __FILE__ );
    }

    public function generateControllerTemplate()
    {
        $file = $this->getControllerTemplatePath().$this->controllerTemplateName();

        file_exists($file) or die("Controller Template not Exists");
        //file_exists($modelFl ) or die("No Model Exists");

        /*read operation ->*/
        $this->filePointer = fopen($file, "r");
        $content=fread($this->filePointer,filesize($file));
        //fclose($tmp);

        $content = $this->replaceControllerName($content);


        $content = str_replace('%model Columns%', $this->getDbCode().PHP_EOL, $content);
        $content = str_replace('%addRule%', $this->getValidationCode().PHP_EOL, $content);
        $content = str_replace('{%formElements%}', $this->getForm().PHP_EOL, $content);
        $newContent = $this->replaceModelName($content);

        $this->replacedContent = $newContent;
    }

    public function setApplicationDirectory($dir)
    {
        $this->applicationDir = $dir;
    }

    public function generate()
    {
        /*write operation ->*/
        $writeTmp =fopen(
            $this->applicationDir.DS.'controllers'.DS.$this->inflector->covertAsClassName($this->controller).'.php',
            "w"
        ) or die('Unable to generate controller');

        $contentAppendWith = '<?php '.PHP_EOL;

        fwrite($writeTmp, $contentAppendWith .$this->replacedContent);
        fclose($writeTmp);
        fclose($this->filePointer);
    }

    public static function __callStatic($method, $arguments = array())
    {
        if ($method == 'instance') {
            return new self($arguments[0], $arguments[1]);
        }
    }
}