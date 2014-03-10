<?php $this->renderPartial('@header'); ?>
<div class="container">
    <div class="container-body" style="margin-left:100px;">
        <?php 
          $this->renderPartial('@content'); ?>
    </div>
</div>
<?php
$this->renderPartial($this->footer);