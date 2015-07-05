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

    private $templatePath;

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

    public static function instance($arguments = [])
    {
        return new self($arguments);
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

        $file =  $this->getTemplatePath().$template.EXT;

        file_exists($file) or die("Base template doesn't exists");

        /*read operation ->*/
        // Open the file to get existing content
        $fileContent = file_get_contents($file);

        $content = str_replace('{%className%}',
            Inflector::classify(strtolower($this->command->argumentName)),
            $fileContent
        );

        $contentAppendWith = '';

        $contentAppendWith .= '<?php '.PHP_EOL;

        $this->replacedContent = $contentAppendWith.$content;
    }

    private function getAppMigrationDirPath()
    {
        return $this->command->getMigrationPath();
    }

    public function generate(\DateTime $date)
    {
        $filePath = $appMigrationPath = '';
        $date->setTimezone(new \DateTimeZone(SET_TIME_ZONE));
        $appMigrationPath = $this->getAppMigrationDirPath();

        $this->hasDirectory($appMigrationPath);

        $file = strtolower(Inflector::changeToLower(
                    $date->format('YmdHis').'_'.$this->command->argumentName.EXT
                ));

        $filePath =  $appMigrationPath.$file;

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

        return $file;
    }

    public function getLatestMigration()
    {
        try {
            $files = $this->files($this->getAppMigrationDirPath());
        } catch (\Exception $e) {
            throw new \Exception(sprintf("Invalid migration directory %s.", $this->getAppMigrationDirPath()));
        }

        $this->latestFile = reset($files);

        return $this;
    }

    /**
     * We will scan directory and return only files with .php extension
     *
     * @param $directory
     * @return array
     */
    private function files($directory)
    {
        return preg_grep('~\.(php)$~', scandir($directory, SCANDIR_SORT_DESCENDING));
    }

    /**
     * Return file extension
     *
     * @param $file
     * @return string
     */
    private function getFileExt($file)
    {
        return strtolower(pathinfo($file, PATHINFO_EXTENSION));
    }

    /**
     * @param string $file
     * @throws \Exception
     */
    public function setMigrationClassName($file = '')
    {
        if ($this->getFileExt($this->latestFile) !== 'php') {
            throw new \Exception(APP_NS."/Resources/Database/Migrations/ must have {xxxx_table_name.php} file types");
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

        $file = $this->getAppMigrationDirPath().$this->getVersion().$this->getMigrationClass();

        if (is_readable($file.EXT)) {
            include_once $file.EXT;
            $class = Inflector::classify($this->getMigrationClass());
        }

        if (trim($type) !== 'down') {
            $type = 'up';
        }

        call_user_func_array([new $class, $type], []);

        $this->updateMigrationTable();

        $this->command->info("Migrated: $file OK!");
    }
    
    public function updateMigrationTable()
    {
        $this->command->table()->updateMigrationVersion($this);
    }
}
