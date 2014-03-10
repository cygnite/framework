<?php
// src/Acme/DemoBundle/Command/GreetCommand.php
namespace Cygnite\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;



class GeneratorCommand extends Command
{
	
    protected function configure()
    {
        $this->setName('generate:crud')
            ->setDescription('Generate Crud By Cygnite CLI')
            ->addArgument('name', InputArgument::OPTIONAL, 'Controller Name ?')
			->addArgument('model', InputArgument::OPTIONAL, 'Model Name ?')
            ->addOption('yell', null, InputOption::VALUE_NONE, 'If set, the task will yell in uppercase letters')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
		$model = $input->getArgument('model');
		$output->writeln($name);
		$output->writeln($model);
		
		//$schema = Schema::instance();
		
		//var_dump($schema);
		//exit;
		#controllerName
		#modelName
		#%model Columns%
		
		$controllerFile = 'controller.php';
		$modelFile = 'model.php';
		$controllerDir = dirname(__FILE__).DIRECTORY_SEPARATOR.'apps'.DIRECTORY_SEPARATOR.'controllers'.DIRECTORY_SEPARATOR;
		$modelDir = dirname(__FILE__).DIRECTORY_SEPARATOR.'apps'.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR;
		$fl = $controllerDir.$controllerFile;
		//$modelFl = $modelDir.$modelFile;
		
		file_exists($fl ) or die("No File Exists");
		//file_exists($modelFl ) or die("No Model Exists");
		
		/*read operation ->*/ 
		$tmp = fopen($fl, "r");   
		$content=fread($tmp,filesize($fl)); 
		//fclose($tmp);

		$content = str_replace('class %controllerName%', 'class '.ucfirst($name), $content);
		$content = str_replace('%controllerName%', $name, $content);
		
		echo $newContent = str_replace('%modelName%', $model, $content);
		
		
		// here goes your update
		//$content = preg_replace('/\%controllerName%\"(.*?)\";/', $name, $content);
		
		//echo $content;

		/*write operation ->*/ 
		$writeTmp =fopen($controllerDir.$name.'.php', "w") or die('Unable to generate ');     
		
		$contentAppendWith = '<?php'.PHP_EOL;
		
		
		fwrite($writeTmp, $contentAppendWith .$newContent);   
		 fclose($writeTmp);
		 fclose($tmp);

		
		
		exit;
		
		$holdpwd = dirname(__FILE__);
		$controllerFile = $holdpwd.DIRECTORY_SEPARATOR.'template'.DIRECTORY_SEPARATOR.'controller.php';
		$className = $name;
		$action = $method;
	     $controll = file_get_contents($controllerFile) ;
		 var_dump($controll );
		 
		 $file = $holdpwd.DIRECTORY_SEPARATOR.'template'.DIRECTORY_SEPARATOR.$className .'.php';		
		 file_put_contents($file,  (string) $controll);
		 echo "$className controller has generated successfully !!";
		
		
		
		exit;
		

		
		/*
	    
		 //echo "$holdpwd\n";
		//chdir($parentDirectory2);
		$this->openFileSearchAndReplace($parentDirectory2, $searchFor2, $replaceWith2);
		
		exit;
		*/
        if ($name) {
            $text = 'Hello '.$name;
        } else {
            $text = 'Hello';
        }

        if ($input->getOption('yell')) {
            $text = strtoupper($text);
        }

        $output->writeln($text);
    }
	
		private	function openFileSearchAndReplace($parentDirectory, $searchFor, $replaceWith)
		  {
					/*
					echo "debug here- line 1a\n";
					echo "$parentDirectory\n";
					echo "$searchFor\n";
					echo "$replaceWith\n";
					*/

				if ($handle = opendir("$parentDirectory")) {
					while (false !== ($file = readdir($handle))) {
						if (($file != "." && $file != "..") && !is_dir($file)) {
						  chdir("$parentDirectory"); //to make sure you are always in right directory
						 // echo "$file\n";
						 $holdcontents = file_get_contents($file);
						 $holdcontents2 = str_replace($searchFor, $replaceWith, $holdcontents);
						 file_put_contents($file, $holdcontents2);
						 // echo "debug here- line 1\n";
						 // echo "$file\n";

						}
						if(is_dir($file) && ($file != "." && $file != ".."))
						{
						$holdpwd = getcwd();
						//echo "holdpwd = $holdpwd \n";
						$newdir = "$holdpwd"."/$file";
						//echo "newdir = $newdir \n";  //for recursive call
						$this->openFileSearchAndReplace($newdir, $searchFor, $replaceWith);
						//echo "debug here- line 2\n";
						//echo "$file\n";
						}
					}
					closedir($handle);
				 }
		}

}
