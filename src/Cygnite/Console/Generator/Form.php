<?php
/*
 * This file is part of the Cygnite package.
 *
 * (c) Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cygnite\Console\Generator;

use Cygnite\Helpers\Inflector;

/**
 * Cygnite Model Generator
 *
 * This class is used to generate your Form class using cygnite console
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 */
class Form
{
    private $formCommand;

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
        return 'Form'.EXT;
    }

    /**
     * Generate Form
     */
    public function generate()
    {
        $filePath = '';
        $formName = Inflector::classify($this->formCommand->table);

        if (file_exists($this->getFormTemplatePath().'Form'.EXT)) {
           //We will generate Form Component
            $formContent = file_get_contents($this->getFormTemplatePath().'Form'.EXT);
        } else{
           die("Form template doesn't exists in ".$this->getFormTemplatePath().'Form'.EXT." directory.");
        }

        $this->controller->isFormGenerator = true;
        $this->controller->updateTemplate();
        $this->controller->controller = $formName;
        $this->controller->applicationDir = CYGNITE_BASE.DS.APPPATH;
        $formContent = str_replace('%controllerName%', $formName, $formContent);
        $formContent = str_replace('{%formElements%}', $this->controller->getForm().PHP_EOL, $formContent);
        $this->controller->generateFormComponent($formContent);
    }
}
