<?php

use Mockery as m;

class ApplicationTest extends PHPUnit_Framework_TestCase {
  public function tearDown($value='')
  {
    m::close();
  }

  public function testInstance()
  {
  	$inflector = m::mock('Cygnite\Inflector');
  	$loader = m::mock("Cygnite\Autoloader");

  	$application = Cygnite\Application::getInstance($inflector,$loader);

  	$this->assertInstanceOf('Cygnite\Application',$application);
  }
}
