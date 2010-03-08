<?php
/**
 * PHP theme block template
 * @package SSC
 * @subpackage Theme
 */

?><div class="widget">
	<?php if (!empty($title)) { ?><div class="widget-title"><?php echo $title; ?></div> <?php } ?>
	<div class="widget-body"><?php echo $body; ?></div>
</div>
