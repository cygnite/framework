<?php
namespace Cygnite\Console\Generator;

use Cygnite\Inflector;

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
        return 'model'.self::EXTENSION;
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
            $this->inflector->covertAsClassName($this->command->model),
            $content
        );
        $content = str_replace('%modelName%', $this->command->model, $content);
        $content = str_replace('%databaseName%', $this->command->database, $content);

        return $content;

    }

    public function generate()
    {
        $filePath = '';
        $filePath =  $this->command->applicationDir.
            DS.'models'.
            DS.
            $this->inflector->covertAsClassName($this->command->model)
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