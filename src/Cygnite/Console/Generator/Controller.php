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
 * Cygnite Controller Generator
 *
 * This class used to generate controller code
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 */
class Controller
{
    private $columns = array();

    public $controller;

    private $controllerTemplatePath;

    private $model;

    private $form;

    private $dbCode;

    private $validationCode;

    private $replacedContent;

    public $applicationDir;

    private $filePointer;

    private $viewType;

    private $formPath;

    private $controllerCommand;

    public $isFormGenerator = false;

    /*
     * Since constructor is private you cannot create object
     * for this class directly
     *
     * @access private
     * @param $inflect instance of Inflection
     * @param $columns array of columns
     * @return void
     */
    private function __construct($columns = array(), $viewType = null, $generator = null)
    {
        $this->columns = $columns;
        $this->viewType = $viewType;
        $this->controllerCommand = $generator;
    }

    /**
     * Set controller template path
     * @param $path
     */
    public function setControllerTemplatePath($path)
    {
        $this->controllerTemplatePath = $path;
    }

    /**
     * Get controller template path
     * @return null
     */
    public function getControllerTemplatePath()
    {
        return (isset($this->controllerTemplatePath)) ?
            $this->controllerTemplatePath :
            null;
    }

    /**
     * @param $name
     * @return void
     */
    public function setControllerName($name)
    {
        $this->controller = $name;
    }

    /**
     * @param $name
     */
    public function setModelName($name)
    {
        $this->model = $name;
    }

    /**
     * Generate form open tag
     * @return string
     */
    private function buildFormOpen()
    {
        return '$this->open("'.$this->controller.'", array(
        "method" => "post", "id"     => "uniform", "role"   => "form", "action" => Url::sitePath("'.
        strtolower(
            str_replace('Controller', '', $this->controller)
        ).'/$this->action/$id/"),
        "style" => "width:500px;margin-top:35px;float:left;" )
        )';
    }

    /**
     * Generate Form Elements
     *
     * @param $value
     * @return string
     */
    private function generateFormElements($value)
    {
        $form = $label = '';
        $label = Inflector::underscoreToSpace($value->column_name);
        $form .= "\t\t".'->addElement("label", "'.$label.'", array("class" => "col-sm-2 control-label","style" => "width:100%;"))'.PHP_EOL;

        $form .= "\t\t".'->addElement("text", "'.$value->column_name.'", array("value" => (isset($this->model->'.$value->column_name.')) ? $this->model->'.$value->column_name.' : "", "class" => "form-control"))'.PHP_EOL;
        return $form;
    }

    /**
     * Build Form closing tags
     * @return string
     */
    private function buildFormCloseTags()
    {
        $form = '';
        $form .= "\t\t".'->addElement("submit", "btnSubmit", array("value" => "Save", "class" => "btn btn-primary", "style" => "margin-top:15px;" ))'.PHP_EOL;

        $form .= "\t\t".'->close()'.PHP_EOL;
        $form .= "\t\t".'->createForm();'.PHP_EOL;

        return $form;
    }

    /**
     * Generate database code
     * @param $value
     * @return string
     */
    private function generateDbCode($value)
    {
        $code = '';
        $code .=
            "\t".'$'.Inflector::tabilize($this->model).'->'.$value->column_name.' = $postArray["'.$value->column_name.'"];'.PHP_EOL;

        return $code;
    }

    /**
     * Generate form validation code
     * @param $value
     * @return string
     */
    private function generateValidator($value)
    {
        $validationCode = '';
        $validationCode .= "\t\t->addRule('".$value->column_name."', 'required|min:5')".PHP_EOL;

        return $validationCode;
    }

    /**
     * Update the template code
     *
     */
    public function updateTemplate()
    {

        $codeDb = $validationCode = $form = '';

        $form = $this->buildFormOpen();

        foreach ($this->columns as $key=> $value) {

            if ($value->column_name !== 'id') {
                if ($this->isFormGenerator == false) {
                    $codeDb .= $this->generateDbCode($value);
                }
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


    /**
     * Get the form
     * @return null|string
     */
    public function getForm()
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

    /**
     * Set validation code
     * @param $code
     */
    private function setValidationCode($code)
    {
        $this->validationCode = $code;
    }

    /**
     * Get validation code
     * @return null|string
     */
    private function getValidationCode()
    {
        return (is_string($this->validationCode) && $this->validationCode !== '') ?
            $this->validationCode :
            null;
    }

    /**
     * Replace the controller name with original name
     * @param $content
     * @return mixed
     */
    private function replaceControllerName($content)
    {
        $content = str_replace(
            'class %ControllerName%',
            'class '.Inflector::classify(str_replace("Controller", "", $this->controller)),
            $content
        );
        $content = str_replace(
            '%ControllerName%',
            Inflector::classify(str_replace("Controller", "", $this->controller)),
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

    /**
     * Replace the model name with original model name
     *
     * @param $content
     * @return mixed
     */
    private function replaceModelName($content)
    {
        $newContent = '';
        $content = str_replace(
            'new %modelName%',
            'new '.$this->model,
            $content
        );

        $content = str_replace(
            '%StaticModelName%',
            $this->model,
            $content
        );

        $content = str_replace(
            'new %StaticModelName%()',
            $this->model,
            $content
        );

        $newContent = str_replace('%modelName%', Inflector::tabilize($this->model), $content);

        return $newContent;
    }


    private function controllerTemplateName()
    {
        return basename( __FILE__ );
    }

    /**
     * Generate Controller template with original content
     *
     */
    public function generateControllerTemplate()
    {
        $controllerTemplate = ($this->viewType == 'php') ?
            'Php'.DS.'Controller.php' :
            $this->controllerTemplateName();
        $file = $this->getControllerTemplatePath().$controllerTemplate;

        $this->formPath = str_replace(
                'Controllers\\',
                '',
                $this->getControllerTemplatePath()
            ).'Components'.DS.'Form'.DS;

        file_exists($file) or die("Controller Template not Exists");
        //file_exists($modelFl ) or die("No Model Exists");

        /*read operation ->*/
        $this->filePointer = fopen($file, "r");
        $content=fread($this->filePointer,filesize($file));
        //fclose($tmp);

        $content = $this->replaceControllerName($content);
        $content = str_replace('{%Apps%}', ucfirst(APP_PATH), $content);
        $primaryKey = $this->controllerCommand->getPrimaryKey();

        $content = str_replace('{%primaryKey%}', $primaryKey, $content);
        $content = str_replace('%modelColumns%', $this->getDbCode().PHP_EOL, $content);
        $content = str_replace('%addRule%', $this->getValidationCode().PHP_EOL, $content);
        $controllerNameOnly = Inflector::classify(str_replace('Controller', '', $this->controller));
        $content = str_replace('%ControllerName%Form', $controllerNameOnly.'Form', $content);

        $formTemplatePath = null;
        $formTemplatePath = str_replace('Controllers', '', $this->formPath);

        if (file_exists($formTemplatePath.'Form.php')) {
            //We will generate Form Component
            $formContent = file_get_contents($formTemplatePath.'Form.php', true);
        } else{
            die("Form template doesn't exists in ".$formTemplatePath.'Form.php'." directory.");
        }

        $formContent = str_replace('%controllerName%', $controllerNameOnly, $formContent);
        $formContent = str_replace('{%formElements%}', $this->getForm().PHP_EOL, $formContent);
        $this->generateFormComponent($formContent);

        $newContent = $this->replaceModelName($content);
        $content = null;

        $this->replacedContent = $newContent;
    }

    /**
     * Generate form component
     * @param $formContent
     * @return bool
     */
    public function generateFormComponent($formContent)
    {

        /*write operation ->*/
        $writeTmp =fopen(
            $this->applicationDir.DS.'components'.DS.'form'.DS.Inflector::classify(
                str_replace('Controller', '', $this->controller)
            ).'Form.php',
            "w"
        ) or die('Unable to generate controller');

        $contentAppendWith = '';
        $contentAppendWith = '<?php '.PHP_EOL;
        $formContent = str_replace('{%Apps%}', ucfirst(APP_PATH), $formContent);
        fwrite($writeTmp, $contentAppendWith .$formContent);
        fclose($writeTmp);

        return true;
    }

    /**
     * Set application directory
     * @param $dir
     */
    public function setApplicationDirectory($dir)
    {
        $this->applicationDir = $dir;
    }

    /**
     * Generate the controller with updated template
     */
    public function generate()
    {
        /*write operation ->*/
        $writeTmp =fopen(
            $this->applicationDir.DS.'controllers'.DS.$this->controller.'.php',
            "w"
        ) or die('Unable to generate controller');

        $contentAppendWith = '<?php '.PHP_EOL;

        fwrite($writeTmp, $contentAppendWith .$this->replacedContent);
        fclose($writeTmp);
        fclose($this->filePointer);
    }

    /**
     * @param       $method
     * @param array $arguments
     * @return Controller
     */
    public static function __callStatic($method, $arguments = array())
    {
        if ($method == 'instance') {
            return new self($arguments[0], $arguments[1], $arguments[2]);
        }
    }

    /**
     * @return bool
     */
    public function makeController()
    {
        // We will check command type before generating controller class
        // If --resource set then we will generate resource controller
        if ($this->controllerCommand->getControllerType()) {
            return $this->makeResourceController();
}

        // generate basic controller
        return $this->generateBasicController();
    }

    /**
     * @return bool
     * @throws \Exception
     */
    private function generateBasicController()
    {
        $controllerClass = $this->applicationDir.DS.'controllers'.DS.$this->controller.'.php';

        if (file_exists($controllerClass)) {
            throw new \Exception("$controllerClass already exists!!");
        }

        /*write operation ->*/
        $filePointer = fopen($controllerClass, "w") or die('Unable to generate controller');

        $controllerContent = $this->getControllerTemplate();
        $content = $this->getIndexStub();
        $content = $this->replaceTemplate('{%methods%}', $content, $controllerContent);
        return $this->writeContentToClass($filePointer, $content);
    }

    /**
     * @param $filePointer
     * @param $content
     * @return bool
     */
    private function writeContentToClass($filePointer, $content)
    {
        fwrite($filePointer, $content);
        fclose($filePointer);
        return true;
    }

    /**
     * @return mixed
     */
    private function getControllerTemplate()
    {
        $this->getControllerTemplatePath();
        $content = file_get_contents($this->getControllerTemplatePath().'controller.tpl.stub', true);
        $content = $this->replaceTemplate('{%Apps%}', ucfirst(APP_PATH), $content);
        return $this->replaceTemplate('{%ControllerName%}', $this->controller, $content);
    }

    /**
     * @return string
     */
    private function getIndexStub()
    {
        return file_get_contents($this->getControllerTemplatePath().'index.tpl.stub', true);
    }

    /**
     * @param $key
     * @param $replace
     * @param $content
     * @return mixed
     */
    private function replaceTemplate($key, $replace, $content)
    {
        return str_replace($key, $replace, $content);
    }

    /**
     * @return bool
     * @throws \Exception
     */
    private function makeResourceController()
    {
        $stubs = array('index', 'new', 'create', 'show', 'edit', 'update', 'delete');
        $controllerClass = $this->applicationDir.DS.'controllers'.DS.$this->controller.'.php';

        if (file_exists($controllerClass)) {
            throw new \Exception("$controllerClass already exists!!");
        }

        /*write operation ->*/
        $filePointer = fopen($controllerClass, "w") or die('Unable to generate controller');

        $controllerContent = $this->getControllerTemplate();

        $resourceContent = "";
        foreach ($stubs as $key => $template) {
            $resourceContent .= file_get_contents($this->getControllerTemplatePath().$template.'.tpl.stub', true).PHP_EOL;
        }

        $content = $this->replaceTemplate('{%methods%}', $resourceContent, $controllerContent);
        return $this->writeContentToClass($filePointer, $content);
    }
}