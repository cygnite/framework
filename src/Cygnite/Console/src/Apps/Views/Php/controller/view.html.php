<?php
use Cygnite\AssetManager\Asset;
?>
<div style="float:right;margin-right:47px; margin-bottom: 10px;margin-top: 10px;padding-bottom:30px;">
    <?php echo Asset::link('#controllerName#', 'Back', array('class' => 'btn btn btn-info')); ?>
</div>

<div class="form" style="">
    <h2>Showing #controllerName# #<?php echo $this->record->id; ?></h2>
    {#recordDivElements#}
</div>