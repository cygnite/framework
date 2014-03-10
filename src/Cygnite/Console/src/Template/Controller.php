<?php
/**
 * This is the template for generating a controller class file.
 * The following variables are available in this template:
 * - $className: the class name of the controller
 * - $actions: a list of action names for the controller
 */
?>
<?php echo "<?php\n"; ?>

class <?php echo ucfirst($className); ?> extends Controller
{

	public function <?php echo $action; ?>Action()
	{
		$this->render('<?php echo $action; ?>');
	}
}