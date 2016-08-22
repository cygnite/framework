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
 * This class is used to generate your model code using cygnite console
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 *
 */
class Model
{
    private $command;

    private $modelTemplatePath;

    private $filePointer;

    private $replacedContent;

    private $validationRule;

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

    public static function __callStatic($method, $arguments = [])
    {
        if ($method == 'instance') {
            return new self($arguments[0]);
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
        return 'Model'.EXT;
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
            $this->command->getModel(),
            $content
        );

        $primaryKey = $this->command->table()->getPrimaryKey();
        $content = str_replace('{%Apps%}', APP_NS, $content);
        $content = str_replace('{%primaryKey%}', $primaryKey, $content);
        $content = str_replace('%modelName%', Inflector::tabilize($this->command->getModel()), $content);
        $content = str_replace('%databaseName%', $this->command->getDatabase(), $content);
        $content = str_replace('{%rules%}', $this->replaceValidationRules(), $content);

        return $content;
    }

    /**
     * Set validation code
     * @param $code
     * @return $this
     */
    private function setValidationRules($code)
    {
        $this->validationRule = $code;

        return $this;
    }

    /**
     * Get validation code
     * @return null|string
     */
    private function getValidationRules()
    {
        return (is_string($this->validationRule) && $this->validationRule !== '') ?
            $this->validationRule :
            null;
    }

    /**
     * Generate form validation code
     * @param $value
     * @return string
     */
    private function generateValidatorRules($value)
    {
        $rule = '';
        $rule .= "\t\t'".$value['COLUMN_NAME']."' => 'required|min:5',".PHP_EOL;

        return $rule;
    }

    public function replaceValidationRules()
    {
        $validationCode = '';
        foreach ($this->command->getColumns() as $key=> $value) {
            if ($value['COLUMN_NAME'] !== 'id') {
                $validationCode .= $this->generateValidatorRules($value);
            }
        }

        return $this->setValidationRules($validationCode."\t")
             ->getValidationRules();
    }

    public function generate()
    {
        $filePath = '';
        $filePath =  $this->command->applicationDir.DS.'Models'.DS.$this->command->getModel().EXT;
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
