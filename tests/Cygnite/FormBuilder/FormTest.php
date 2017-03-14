<?php
use PHPUnit\Framework\TestCase;
use Cygnite\Common\UrlManager\Url;
use Cygnite\FormBuilder\Form;
use Cygnite\Container\Container;
use Cygnite\Tests\Container\ContainerDependency;
use Cygnite\Helpers\Config;

class FormTest extends TestCase
{
    private $container;

    public function setUp()
    {
        $containerDependency = new ContainerDependency();
        $this->container = new Container(
            $containerDependency->getInjector(),
            $containerDependency->getDefinitiions(),
            $containerDependency->getControllerNamespace()
        );
        $this->container['request'] = \Cygnite\Http\Requests\Request::createFromGlobals();
        $this->container['router'] = $this->container->make(\Cygnite\Router\Router::class);
        $this->container['router']->setContainer($this->container);
        $this->container['url'] = new \Cygnite\Common\UrlManager\Url(new Cygnite\Common\UrlManager\Manager($this->container));
        $this->container['request']->server->add('REQUEST_URI', '/hello/user/');
        $this->container['request']->server->add('HTTP_HOST', 'localhost');
        $configuration = [
            'global.config' => [
                'encoding' => 'utf-8',
            ],
        ];

        Config::$config = $configuration;

        Url::setBase('/cygnite/'); //$app['router']->getBaseUrl()
    }

    public function testCreateFormInstance()
    {
        $form = Form::make();
        $this->assertInstanceOf('Cygnite\FormBuilder\Form', $form);
    }

    public function testCreateFormClouserInstance()
    {
        $form = Form::make(function ($form) {
            return $form;
        });

        $this->assertInstanceOf('Cygnite\FormBuilder\Form', $form);
    }

    public function testCreateFormLabelAndTextBox()
    {
        $form = Form::make()
                    ->open(
                        'contact',
                        [
                            'method' => 'post',
                            'action' => Url::sitePath('contact/add'),
                            'role'   => 'form',
                        ]
                    )->addElement('text', 'user_name',
                        [
                            'value' => '',
                            'class' => 'form-control',
                        ]
                    )
                    ->close()
                    ->createForm();

        $formString = "<form name='contact' method='post' action='http://localhost/cygnite/contact/add' role='form' >
                    <input name='user_name' value='' class='form-control' type='text'  />
                    </form>";
        $this->assertEquals(preg_replace("/\r\n|\r|\n|\t/", '', trim(preg_replace('/\s+/', ' ', $formString))), preg_replace("/\r\n|\r|\n|\t/", '', trim(preg_replace('/\s+/', ' ', $form->getForm()))));
    }


    public function testCreateFormTextAreaAndFileAndCheckBoxAndRadioElements()
    {
        $form = Form::make()
                    ->open(
                        'contact',
                        [
                            'method' => 'post',
                            'action' => Url::sitePath('contact/add'),
                            'role'   => 'form',
                            'style'  => 'width:500px;margin-top:35px;float:left;',
                        ]
                    )->addElement('label', 'User Name',
                        [
                            'class' => 'col-sm-2 control-label',
                            'style' => 'width:37.667%;',
                        ]
                    )->addElement('textarea', 'description',
                        [
                            'value' => '',
                            'class' => 'form-control',
                        ]
                    )->addElement('file', 'photo',
                        [
                            'value' => '',
                            'class' => 'form-control',
                        ]
                    )->addElement('checkbox', 'Useful',
                        [
                            'value' => '',
                            'class' => 'form-control',
                        ]
                    )->addElement('radio', 'Male',
                        [
                            'value' => '',
                            'class' => 'form-control',
                        ]
                    )
                    ->close()
                    ->createForm();

        $formString = preg_replace("/\r\n|\r|\n|\t/", '', trim(preg_replace('/\s+/', ' ', "<form name='contact' method='post' action='http://localhost/cygnite/contact/add' role='form' style='width:500px;margin-top:35px;float:left;' >
                        <label for='User Name' class='col-sm-2 control-label' style='width:37.667%;' >User Name</label>
                        <textarea for='description' class='form-control' ></textarea>
                        <input name='photo' value='' class='form-control' type='file'  />
                        <input name='Useful' value='' class='form-control' type='checkbox'  />
                        <input name='Male' value='' class='form-control' type='radio'  />
                        </form>")));

        $this->assertEquals($formString, preg_replace("/\r\n|\r|\n|\t/", '', trim(preg_replace('/\s+/', ' ', $form->getForm()))));
    }

    public function testCustomTagsByFormBuilder()
    {
        $form = Form::make()
                    ->open(
                        'contact',
                        [
                            'method' => 'post',
                            'action' => Url::sitePath('contact/add'),
                            'role'   => 'form',
                            'style'  => 'width:500px;margin-top:35px;float:left;',
                        ]
                    )->addElement('custom', 'dl',
                        [
                            'name'  => 'Custom Tag',
                            'class' => 'col-sm-2 control-label',
                            'style' => 'width:37.667%;',
                        ]
                    )->createForm()
                    ->close();

        $formString = preg_replace("/\r\n|\r|\n|\t/", '', trim(preg_replace('/\s+/', ' ', "<form name='contact' method='post' action='http://localhost/cygnite/contact/add' role='form' style='width:500px;margin-top:35px;float:left;' >
                    <dl for='dl' name='Custom Tag' class='col-sm-2 control-label' style='width:37.667%;' >Custom Tag</dl>
                    </form>")));

        $this->assertEquals($formString, preg_replace("/\r\n|\r|\n|\t/", '', trim(preg_replace('/\s+/', ' ', $form->getForm()))));
    }

    public function testDivOrSpanElementForFormBuilder()
    {
        $form = Form::make()
                    ->open(
                        'contact',
                        [
                            'method' => 'post',
                            'action' => Url::sitePath('contact/add'),
                            'role'   => 'form',
                            'style'  => 'width:500px;margin-top:35px;float:left;',
                        ]
                    )
                       ->addElement('openTag', 'div_1', ['style' => 'border:1px solid red;height:40px;'])
                        ->addElement('text', 'I am Inside Div 1', ['class' => 'col-sm-2 control-label', 'style' => 'width:100%;'])
                    ->addElement('closeTag', 'div_1')

                    ->addElement('openTag', 'div_2', ['style' => 'border:1px solid red;height:40px;'])
                        ->addElement('text', 'I am Inside Div 2', ['class' => 'col-sm-2 control-label', 'style' => 'width:100%;'])
                    ->addElement('closeTag', 'div_2')

                    ->createForm()
                    ->close();

        $formString = preg_replace("/\r\n|\r|\n|\t/", '', trim("<form name='contact' method='post' action='http://localhost/cygnite/contact/add' role='form' style='width:500px;margin-top:35px;float:left;' >
            <div name='div_1_1331' style='border:1px solid red;height:40px;'  />
            <input name='I am Inside Div 1' class='col-sm-2 control-label' style='width:100%;' type='text'  />
            </div>
            <div name='div_2_1224' style='border:1px solid red;height:40px;'  />
            <input name='I am Inside Div 2' class='col-sm-2 control-label' style='width:100%;' type='text'  />
            </div>
            </form>"));

        // It works though But we cannot Test it because it generate random name for Div or Span Element
        // For Example [name='div_2_1224' or name='div_1_1331']

        $this->assertEquals(preg_replace("/\r\n|\r|\n|\t/", '', trim("<form name='contact' method='post' action='http://localhost/cygnite/contact/add' role='form' style='width:500px;margin-top:35px;float:left;' >
            <div name='div_1_1331' style='border:1px solid red;height:40px;'  />
            <input name='I am Inside Div 1' class='col-sm-2 control-label' style='width:100%;' type='text'  />
            </div>
            <div name='div_2_1224' style='border:1px solid red;height:40px;'  />
            <input name='I am Inside Div 2' class='col-sm-2 control-label' style='width:100%;' type='text'  />
            </div>
            </form>")), $formString);
    }
}
