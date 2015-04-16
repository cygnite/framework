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
 * Cygnite View Generator
 *
 * This class used to generate views
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 */
class View
{
    private $command;

    private $viewTemplatePath;

    private $filePointer;

    private $replacedContent;

    const TEMP_EXTENSION = '.html.stub';

    public $layoutType = 'php';

    // Plain php layout extension
    const EXTENSION = '.view.stub';

    // Twig layout extension
    const TWIG_EXTENSION = '.html.twig';

    private $views = array('index', 'create', 'update', 'show');

    private $fields = array();

    /*
     * Since constructor is private you cannot create object
     * for this class directly
     *
     * @access private
     * @param $columns array of columns
     * @return void
     */
    private function __construct($command = null)
    {
        $this->command = $command;
    }

    public static function __callStatic($method, $arguments = array())
    {
        if ($method == 'instance') {
            return new self($arguments[0]);
        }
    }

    public function setTableColumns($columns)
    {
        $this->fields = $columns;
    }

    /**
     * Return table columns
     * @return array|null
     */
    public function getTableColumns()
    {
        return (isset($this->fields)) ?
            $this->fields :
            null;
    }

    public function setViewTemplatePath($path)
    {
        $this->viewTemplatePath = $path;
    }

    public function getViewTemplatePath()
    {
        return (isset($this->viewTemplatePath)) ?
            $this->viewTemplatePath :
            null;
    }

    /**
     * We will set the type of layout
     * to generate either php / twig view pages
     *
     * @param $type
     */
    public function setLayoutType($type)
    {
        $this->layoutType = $type;
    }

    private function viewLayoutName()
    {
        return ($this->layoutType == 'twig') ? 'base.html.stub': 'base.view.stub';
    }

    /**
     * Check has directory or we will create directory
     * @param $directory
     * @return bool
     */
    private function hasDirectory($directory)
    {
        return is_dir($directory) || mkdir($directory);
    }

    /**
     * We will get the layout and generate into the application
     * directory
     *
     * @param $layout
     * @return bool
     */
    public function generateLayout($layout)
    {
        $layout = Inflector::toDirectorySeparator($layout);

        $file = $this->getViewTemplatePath().$layout.DS.$this->viewLayoutName();
        $appViewPath = '';
        $appViewPath = $this->command->applicationDir.DS.'views'.DS;

        $this->hasDirectory($appViewPath);
        $this->hasDirectory($appViewPath.'layout');
        $this->hasDirectory($appViewPath.DS.$layout);

        file_exists($file) or die("Base layout stub file doesn't exists in Cygnite Core");

        $layoutFile = $appViewPath.$layout.DS.$this->viewLayoutName();

        if ($this->layoutType == 'php') {
            $layoutFile = str_replace('.stub', EXT, $layoutFile);
        } else {
            $layoutFile = str_replace('.stub', '.twig', $layoutFile);
        }

        if (file_exists($layoutFile)) {
            echo "\n Layout file already exists in $layoutFile directory \n";
            return true;
        }

        /*read operation ->*/
        // Open the file to get existing content
        $fileContent = file_get_contents($file);

        $handle = null;

        $handle = fopen($layoutFile, 'w') or die('Cannot open file:  '.$layoutFile);

        fwrite($handle, $fileContent);
        fclose($handle);

    }

    /**
     * Read template view contents
     *
     * @param        $view
     * @param string $page
     */
    private function readContents($view, $page = '')
    {
        file_exists($view) or die("View Template File doesn't exists");

        $content = '';
        /*read operation ->*/
        $this->filePointer = fopen($view, "r");
        $content = fread($this->filePointer, filesize($view));

        switch ($page) {
            case 'index':
                $content = $this->replaceIndexTemplateContents($content);
                break;
            case 'create':
            case 'update':
                $content = $this->replaceCreateOrUpdateTemplateContents($content);
                break;
            case 'show':
                $content = $this->replaceViewTemplateContents($content);
                break;
        }

        $this->replacedContent = $content;
    }

    /**
     * generate views into application directory
     */
    public function generateViews()
    {
        $viewPath  = $viewExtension = '';
        $viewDir = $this->command->applicationDir.DS.'views'.DS.strtolower(str_replace("Controller", "", $this->command->controller));
        $this->hasDirectory($viewDir);

        $viewExtension = ($this->layoutType == 'php') ? self::EXTENSION : self::TWIG_EXTENSION;
        $viewType = ($this->layoutType == 'php') ? 'Php'.DS : '';

        foreach ($this->views as $key => $view) {

            $viewPath = $this->viewTemplatePath.'controller'.DS.$viewType.$view.self::TEMP_EXTENSION;
            $this->readContents($viewPath, $view);
            $this->generate($view.$viewExtension);
        }

    }

    /**
     * Replace the index content with template content
     * @param $content
     * @return mixed
     */
    private function replaceIndexTemplateContents($content)
    {
        /* Index View Page */
        #replace table headers - <th> {%tableColumns%}</th>
        #replace table td - {%controllerColumns%}

        $content = str_replace('#controllerName#',
            strtolower(str_replace("Controller", "", $this->command->controller)),
            $content
        );

        $content = str_replace(
            '#ControllerName#',
            Inflector::classify(str_replace("Controller", "", $this->command->controller)),
            $content
        );

        $content = str_replace('{%primaryKey%}',
            $this->command->getPrimaryKey(),
            $content
        );

        $column = '';

        $column = $this->replaceTableElements('th');
        $content = str_replace('{#thColumns#}', $column.PHP_EOL, $content);

        $column = $this->replaceTableElements('td');
        $content = str_replace('{#tdColumns#}', $column.PHP_EOL, $content);

        return $content;

    }

    /**
     * Replace table content with database columns
     * @param string $type
     * @return string
     */
    private function replaceTableElements($type = 'th')
    {
        $column = '';

        foreach ($this->getTableColumns() as $key=> $value) {

            if ($value->column_name !== 'id') {

                if ($type == 'th') {
                    $tableHead = Inflector::underscoreToSpace($value->column_name);
                    $column .= "\t\t\t".'<'.$type.'>'.$tableHead.'</'.$type.'>'.PHP_EOL;
                } else{
                    $rowType = '';
                    if ($this->layoutType == 'php') {
                        $rowType = '<?php echo $row->'.$value->column_name.'; ?>';
                    } else {
                        $rowType = '{{row.'.$value->column_name.'}}';
                    }
                    $column .= "\t\t\t".'<'.$type.'>'.$rowType.'</'.$type.'>'.PHP_EOL;
                }

            }
        }

        return $column;
    }


    private function replaceCreateOrUpdateTemplateContents($content)
    {
        /* Create View Page */
        # replace controller name - #controllerName#
        /* Update View Page */
        # replace controller name - #controllerName#

        $controller = str_replace("Controller", "", $this->command->controller);
        $content = str_replace('#controllerName#', strtolower($controller), $content);
        $content = str_replace('#Controller#', Inflector::classify($controller), $content);

        return $content;
    }

    private function replaceViewTemplateContents($content)
    {
        /* Show View Page */
        # replace controller name - #controllerName#
        #replace with table columns - {%recordDivElements%}

        $column = '';
        foreach ($this->getTableColumns() as $key=> $value) {

            if ($value->column_name !== 'id') {

                if ($this->layoutType == 'php') {
                    $rowType = '<?php echo $record->'.$value->column_name.'; ?>';
                } else {
                    $rowType = '{{ record.'.$value->column_name.' }}';
                }

                $column .=
                "\t\t\t".'<div class="form-group">
                    <label class="col-sm-2 control-label">'.
                    Inflector::underscoreToSpace($value->column_name).
                    '</label>
                    <div class="col-sm-10">
                        <p class="form-control-static"><span>'.$rowType.'</span></p>
                    </div>
                </div>'.PHP_EOL;
            }
        }

        $content = str_replace('#controllerName#',
            strtolower(str_replace("Controller", "", $this->command->controller)),
            $content
        );

        $content = str_replace('{#recordDivElements#}',
            $column,
            $content
        );

        return $content;
    }

    private function getApplicationViewPath()
    {
        return $this->command->applicationDir.DS.'views'.DS.
        strtolower(str_replace("Controller", "", $this->command->controller)).DS;
    }

    /**
     * Generate views
     * @param $viewName
     */
    public function generate($viewName)
    {
        $filePath = '';
        $appViewPath = $this->getApplicationViewPath();

        if ($this->layoutType == 'php') {
           $viewName = str_replace('.stub', EXT, $viewName);
        } else {
           $viewName = str_replace('.stub', '.twig', $viewName);
        }

        $filePath =  $appViewPath.strtolower($viewName);
        $this->hasDirectory($appViewPath);

        /*write operation ->*/
        $writeTmp =fopen(
            $filePath,
            "w"
        ) or die('Unable to generate model');

        fwrite($writeTmp, $this->replacedContent);
        fclose($writeTmp);
        fclose($this->filePointer);
        $this->replacedContent = '';
    }
}