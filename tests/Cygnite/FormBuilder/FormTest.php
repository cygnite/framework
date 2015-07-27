<?php
use Cygnite\Common\Input;
use Cygnite\FormBuilder\Form;
use Cygnite\FormBuilder\Html\Elements;
use Cygnite\Common\UrlManager\Url;
use Cygnite\Base\Router\Router;
use Cygnite\Foundation\Application;
use Cygnite\Helpers\Config;
use Mockery as m;

class FormTest extends PHPUnit_Framework_TestCase
{
	public function testCreateFormInstance()
	{
		$form = Form::make();
		$this->assertInstanceOf('Cygnite\FormBuilder\Form', $form);

		$formMock = m::mock('Cygnite\FormBuilder\Form');
		$this->assertInstanceOf('Cygnite\FormBuilder\Form', $formMock);

		$formInstance = Form::instance();
		$this->assertInstanceOf('Cygnite\FormBuilder\Form', $formInstance);
	}

	public function testCreateFormClouserInstance()
	{
		$form = Form::make(function($form) 
		{
			return $form;
		});

		$this->assertInstanceOf('Cygnite\FormBuilder\Form', $form);	
	}

	private function setUpAssetConfig()
	{
		$loader = m::mock("Cygnite\Foundation\Autoloader");
        $app = Application::getInstance($loader);
        $app['router'] = m::mock("Cygnite\Base\Router\Router");

		$_SERVER['REQUEST_URI'] = '/hello/user/';
		$_SERVER['HTTP_HOST'] = 'localhost';
		$configuration = [
			'global.config' => [
				'encoding' => 'utf-8'
			]
		];

		Config::$config = $configuration;

		Url::setBase('/cygnite/');//$app['router']->getBaseUrl()
	}

	public function testCreateFormLabelAndTextBox()
	{
		$this->setUpAssetConfig();

		$form = Form::make()
		            ->open(
			            'contact',
			            [
			                'method' => 'post',
			                'action' => Url::sitePath('contact/add'),
			                'role' => 'form',
			                'style' => 'width:500px;margin-top:35px;float:left;'
			            ]
        			)->addElement('label', 'User Name', 
        				[
				            'class' => 'col-sm-2 control-label',
				            'style' => 'width:37.667%;'
				        ]
			        )->addElement('text', 'user_name' , 
        				[
				            'value' => '',
				            'class' => 'form-control'
			            ]
			        )
			        ->close()
        			->createForm();

        $formString = preg_replace("/\r\n|\r|\n|\t/",'', trim("<form name='contact' method='post' action='http://localhost/cygnite/contact/add' role='form' style='width:500px;margin-top:35px;float:left;' >
					<label for='User Name' class='col-sm-2 control-label' style='width:37.667%;' >User Name</label>
					<input name='user_name' value='' class='form-control' type='text'  />
					</form>"));

        $this->assertEquals($formString, preg_replace("/\r\n|\r|\n|\t/",'', trim($form->getForm())));
	}

	public function testCreateFormTextAreaAndFileAndCheckBoxAndRadioElements()
	{
		$this->setUpAssetConfig();

		$form = Form::make()
		            ->open(
			            'contact',
			            [
			                'method' => 'post',
			                'action' => Url::sitePath('contact/add'),
			                'role' => 'form',
			                'style' => 'width:500px;margin-top:35px;float:left;'
			            ]
        			)->addElement('label', 'User Name', 
        				[
				            'class' => 'col-sm-2 control-label',
				            'style' => 'width:37.667%;'
				        ]
			        )->addElement('textarea', 'description' , 
        				[
        					'value' => '',
				            'class' => 'form-control'
			            ]
			        )->addElement('file', 'photo' , 
        				[
        					'value' => '',
				            'class' => 'form-control'
			            ]
			        )->addElement('checkbox', 'Useful' , 
        				[
        					'value' => '',
				            'class' => 'form-control'
			            ]
			        )->addElement('radio', 'Male' , 
        				[
        					'value' => '',
				            'class' => 'form-control'
			            ]
			        )
			        ->close()
        			->createForm();

        $formString = preg_replace("/\r\n|\r|\n|\t/",'', trim("<form name='contact' method='post' action='http://localhost/cygnite/contact/add' role='form' style='width:500px;margin-top:35px;float:left;' >
						<label for='User Name' class='col-sm-2 control-label' style='width:37.667%;' >User Name</label>
						<textarea for='description' class='form-control' ></textarea>
						<input name='photo' value='' class='form-control' type='file'  />
						<input name='Useful' value='' class='form-control' type='checkbox'  />
						<input name='Male' value='' class='form-control' type='radio'  />
						</form>"));

        $this->assertEquals($formString, preg_replace("/\r\n|\r|\n|\t/",'', trim($form->getForm())));
	}

}
