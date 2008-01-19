<?php
/**
 * PHP based theme
 * @package SSC
 * @subpackage Theme
 */ 

/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="<?php echo $SSC_SETTINGS['lang']['tag'] ?>" xml:lang="<?php echo $SSC_SETTINGS['lang']['tag'] ?>">
<head><?php theme_meta(); ?>
<style type="text/css" media="all">
	@import url("<?php echo $SSC_SETTINGS['theme']['url']; ?>/site.css");
</style>
<style type="text/css" media="print">
	@import url("<?php echo $SSC_SETTINGS['theme']['url']; ?>/print.css");
</style>
</head>
<body><div id="header"><?php theme_title(1); ?></div>
<div id="bar"><?php theme_title(2); theme_header(1); ?></div>

<div id="container">
  <div id="container-help">
    
  	<div id="left"><?php theme_side(1); ?></div>
    <div id="right"><?php theme_side(2); ?></div><div id="body"><?php theme_body(); ?></div>
  </div>
</div>
<div id="foot-bar"><?php theme_footer(1); ?></div>
<div id="footer"><?php theme_footer(2); ?></div></body>
</html>
