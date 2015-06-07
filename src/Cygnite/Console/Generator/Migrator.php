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

use Cygnite\Database\Configurations;
use Cygnite\Database\Connection;
use Cygnite\Helpers\Inflector;

/*
 * Console Migration
 *
 * Handle database migrations
 *
 * @author Sanjoy Dey <dey.sanjoy0@gmail.com>
 */
class Migrator
{
    private $command;

    private $content;

    private $default;

    private $templatePath;

    private $filePointer;

    private $replacedContent;

    private $migrationVersion;

    private $migrationClass;

    private $latestFile;

    private $migrationDir;

    /*
     * Since constructor is private you cannot create object
     * for this class directly
     *
     * @access private
     * @param $inflect instance of Inflector
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


    public function setTemplateDir($path)
    {
        $this->templatePath = $path;
    }

    public function getTemplatePath()
    {
        return (isset($this->templatePath)) ?
            $this->templatePath :
            null;
    }

    private function hasDirectory($directory)
    {
        return is_dir($directory) || mkdir($directory);
    }

    /**
     * @param string $template
     */
    public function replaceTemplateByInput($template = 'Migration')
    {
        #replace with table name - {%className%}

        $file =  str_replace(
                ['apps', 'database'],
                ['Apps', 'Database'],
                $this->getTemplatePath()
            ).$template.EXT;

        file_exists($file) or die("Base template doesn't exists");

        /*read operation ->*/
        // Open the file to get existing content
        $fileContent = file_get_contents($file);

        $content = str_replace('{%className%}',
            Inflector::classify(strtolower($this->command->input)),
            $fileContent
        );

        $contentAppendWith = '';

        $contentAppendWith .= '<?php '.PHP_EOL;

        $this->replacedContent = $contentAppendWith.$content;
    }

    private function getAppMigrationDirPath()
    {
        return $this->command->appDir;
    }

    public function generate(\DateTime $date)
    {
        $filePath = $appMigrationPath = '';
        $date->setTimezone(new \DateTimeZone(SET_TIME_ZONE));
        $appMigrationPath = $this->getAppMigrationDirPath().DS.'database'.DS.'migrations'.DS;

        $this->hasDirectory($appMigrationPath);

        $filePath =  $appMigrationPath.strtolower(
                Inflector::changeToLower(
                    $date->format('YmdHis').'_'.$this->command->input.EXT
                )
            );

        /*write operation ->*/
        $writeTmp =fopen(
            $filePath,
            "w"
        ) or die("Unable to generate migration on $filePath");

        try {
            fwrite($writeTmp, $this->replacedContent);
        } catch (\Exception $ex) {
            echo 'Caught exception: ',  $ex->getMessage(), "\n";
        }

        fclose($writeTmp);
        $this->replacedContent = '';

        return $filePath;
    }

    public function getLatestMigration($directory)
    {
        $this->migrationDir = $directory;

        $files = scandir($directory, SCANDIR_SORT_DESCENDING);

        $this->latestFile = $files[0];

        return $this;
    }

    public function setMigrationClassName($file = '')
    {
        if (pathinfo($this->latestFile, PATHINFO_EXTENSION) !== 'php') {
            throw new \Exception(APPPATH."/database/migrations/ must contain only {xxxx_table_name.php} file types");
        }

        $file = str_replace(EXT, '',$this->latestFile);
        $exp = '';
        $exp =  preg_split('((\d+|\D+))', $file, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);

        $this->migrationVersion = $exp[0];
        $this->migrationClass = $exp[1];
    }

    public function getVersion()
    {
        return $this->migrationVersion;
    }

    public function getMigrationClass()
    {
        return $this->migrationClass;
    }

    /**
     * Call migration and do update
     *
     * @param string $type
     */
    public function updateMigration($type = 'up')
    {
        $file = $class = null;

        $file = $this->migrationDir.$this->getVersion().$this->getMigrationClass().EXT;

        if (is_readable($file)) {
            include_once $file;
            $class = Inflector::classify($this->getMigrationClass());
        }

        if (trim($type) !== 'down') {
            $type = 'up';
        }

        call_user_func_array([new $class, $type], []);

        $this->updateMigrationTable();

    }
    
    public function updateMigrationTable()
    {
        $this->command->table->updateMigrationVersion($this);
    }
}
