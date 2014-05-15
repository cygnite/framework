namespace Apps\Controllers;

use Cygnite\Application;
use Cygnite\Input;
use Cygnite\Libraries\Form;
use Cygnite\Libraries\Validator;
use Cygnite\Helpers\Url;
use Cygnite\Helpers\Assets;
use Apps\Models\%StaticModelName%;
use Cygnite\AbstractBaseController;

class %controllerName% extends AbstractBaseController
{
    /**
    * --------------------------------------------------------------------------
    * The %controllerName% Controller
    *--------------------------------------------------------------------------
    *  This controller respond to uri beginning with %controllerName% and also
    *  respond to root url like "%controllerName%/index"
    *
    * Your GET request of "%controllerName%/form" will respond like below -
    *
    *      public function formAction()
    *     {
    *            echo "Cygnite : Hello ! World ";
    *     }
    * Note: By default cygnite doesn't allow you to pass query string in url, which
    * consider as bad url format.
    *
    * You can also pass parameters into the function as below-
    * Your request to  "%controllerName%/index/2134" will pass to
    *
    *      public function indexAction($id = ")
    *      {
    *             echo "Cygnite : Your user Id is $id";
    *      }
    * In case if you are not able to access parameters passed into method
    * directly as above, you can also get the uri segment
    *  echo Url::segment(3);
    *
    * That's it you are ready to start your awesome application with Cygnite Framework.
    *
    */

    //protected $layout = 'layout.users';

    protected $templateEngine = true;

    //protected $templateExtension = '.html.twig';

    protected $autoReload = true;

    protected $twigDebug = true;

    /*
    * Your constructor.
    * @access public
    *
    */
    public function __construct()
    {
        parent::__construct();
    }

    /**
    * Default method for your controller. Render welcome page to user.
    * @access public
    *
    */
    public function indexAction()
    {
        $%controllerName% = array();
        $%controllerName% = %StaticModelName%::all(
            array(
                'orderBy' => 'id desc',
                /*'paginate' => array(
                    'limit' => Url::segment(3)
                )*/
            )
        );

        $this->render('index')->with(
            array(
                'records' => $%controllerName%,
                'links' => %StaticModelName%::createLinks(),
                'search' => 'search functionality goes here',
                'baseUrl' => Url::getBase(),
                'pageNumber' => Url::segment(3),
                'styles' => array(
                              'table' => Assets::addStyle('webroot/css/cygnite/table.css'),

                            ),
                'buttonAttributes' => array(
                                'primary' => array('class' => 'btn btn btn-info'),
                                'delete' => array('class' => 'btn btn-danger'),
                ),
            )
        );

    }

    public function typeAction($id = null)
    {

        $input = Input::getInstance(
            function ($instance) {
                return $instance;
            }
        );


        $errors = '';
        if ($input->hasPost('btnSubmit') == true) {

            $validator = null;
            $validator = Validator::instance(
                $input,
                function ($validate) {

                    $validate%addRule%

                    return $validate;
                }
            );

            if ($validator->run()) {

                if ($id == null || $id == '') {
                    $%modelName% = new %modelName%();
                } else {
                    $%modelName% = %StaticModelName%::find($id);
                }

                $postArray = $input->except('btnSubmit')->post();

				%model Columns%

                if ($%modelName%->save()) {
                    Url::redirectTo('%controllerName%/index/'.Url::segment(4));
                } else {
                    //echo "Error occured while saving data.";
                    Url::redirectTo('%controllerName%/index/'.Url::segment(4));
                }

            } else {
                //validation error here
                $errors = $validator->getErrors();
            }
        }

        $form = null;

        if (isset($id) && $id !== null) {
            $%controllerName% = array();
            $%controllerName% = %StaticModelName%::find($id);
            $form = $this->generateForm($%controllerName%, Url::segment(4));
            $form->errors = $errors;
            $this->editProduct($id, $form);
        } else {
            $form = $this->generateForm();
            $form->errors = $errors;
            $this->addProduct($form);
        }
    }

    private function editProduct($id, $form)
    {
        // Since our all all logic is in controller
        // You can also use same view page for create and update
        $this->render('update')->with(array(
                'updateForm' => $form->getForm(),
                'validation_errors' => $form->errors,
                'baseUrl' => Url::getBase(),
                'buttonAttributes' => array(
                    'primary' => array('class' => 'btn primary', 'style' => 'border:1px solid #888;'),
                    'delete' => array('class' => 'btn danger'),
                ),
            )
        );
    }

    public function showAction($id)
    {
        $%modelName% = %StaticModelName%::find($id);

        $this->render('view')->with(array(
                'record' => $%modelName%,
                'pageNumber' => Url::segment(4),
                'baseUrl' => Url::getBase(),
                'buttonAttributes' => array(
                    'primary' => array('class' => 'btn primary', 'style' => 'border:1px solid #888;'),
                    'delete' => array('class' => 'btn danger'),
                ),
            )
        );
    }

    private function addProduct($form)
    {
        // Since our all all logic is in controller
        // You can also use same view page for create and update
        $this->render('create')->with(array(
                'createForm' => $form->getForm(),
                'validation_errors' => $form->errors,
                'baseUrl' => Url::getBase(),
                'buttonAttributes' => array(
                    'primary' => array('class' => 'btn primary', 'style' => 'border:1px solid #888;'),
                    'delete' => array('class' => 'btn danger'),
                ),
            )
        );

    }

    private function generateForm($%controllerName% = array(), $pageNumber = '')
    {
        $id = (isset($%controllerName%->id)) ? $%controllerName%->id : '';

        {%formElements%}

        return $form;

    }

    public function deleteAction($id)
    {
        $%controllerName% = new %modelName%();
        if ($%controllerName%->trash($id) == true) {
            Url::redirectTo('%controllerName%/');
            exit;
        } else {
            echo "Error Occured";exit;
        }
    }

}//End of your %controllerName% controller
