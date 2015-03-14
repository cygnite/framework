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
 * @Filename           :  Form.php
 * @Description        :  This class is used to generate your Form code using cygnite console
 * @Author             :  Sanjoy Dey
 * @Copyright          :  Copyright (c) 2013 - 2014,
 * @Link	           :  http://www.cygniteframework.com
 * @Since	           :  Version 1.0.6
 * @File Source
 *
 */
class Form
{
    private $formCommand;

    const EXTENSION = '.php';

    private $formTemplatePath;

    /*
     * Since constructor is private you cannot create object
     * for this class directly
     *
     * @access public
     * @param $columns array of columns
     * @return void
     */
    public function __construct($controller, $formCommand = null)
    {
        $this->controller = $controller;
        $this->formCommand = $formCommand;
    }

    /**
     * Set form template path
     * @param $path
     */
    public function setFormTemplatePath($path)
    {
        $this->formTemplatePath = $path;
    }

    /**
     * Get form Template path
     * @return null
     */
    public function getFormTemplatePath()
    {
        return (isset($this->formTemplatePath)) ?
            $this->formTemplatePath :
            null;
    }

    /**
     * Form template name
     * @return string
     */
    private function formTemplateName()
    {
        return 'Form'.self::EXTENSION;
    }

    /**
     * Generate Form
     */
    public function generate()
    {
        $filePath = '';
        $formName = Inflector::classify($this->formCommand->table);

        if (file_exists($this->getFormTemplatePath().'Form.php')) {
           //We will generate Form Component
            $formContent = file_get_contents($this->getFormTemplatePath().'Form.php');
        } else{
           die("Form template doesn't exists in ".$this->getFormTemplatePath().'Form.php'." directory.");
        }

        $this->controller->isFormGenerator = true;
        $this->controller->updateTemplate();
        $this->controller->controller = $formName;
        $this->controller->applicationDir = BASE_PATH.DS.APP_PATH;
        $formContent = str_replace('%controllerName%', $formName, $formContent);
        $formContent = str_replace('{%formElements%}', $this->controller->getForm().PHP_EOL, $formContent);
        $this->controller->generateFormComponent($formContent);
    }
}
