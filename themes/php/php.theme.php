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

if(false){header("Content-type:application/xhtml+xml");?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="<?php echo ssc_lang(); ?>" xml:lang="<?php echo ssc_lang(); ?>">
<?php }else{ 
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html lang="<?php echo ssc_lang(); ?>">
<?php } ?>
<head><?php theme_do_render('meta', 1); ?>
<style type="text/css" media="all">
	@import url("<?php echo $theme_url; ?>/site.css");
</style>
<style type="text/css" media="print">
	@import url("<?php echo $theme_url; ?>/print.css");
</style>
</head>
<body><div id="header"><?php theme_do_render('title', 1); ?></div>
<div id="bar"><?php theme_do_render('title', 2); theme_do_render('header', 1); ?></div>

<div id="container">
  <div id="container-help">
    
  	<div id="left"><?php theme_do_render('side', 1); ?></div>
    <div id="right"><?php theme_do_render('side', 2); ?></div>
    <div id="body"><?php theme_do_render('body', 1); ?></div>
    <div class="clear"></div>
  </div>
</div>
<div id="foot-bar"><?php theme_do_render('footer', 1); ?></div>
<div id="footer"><?php theme_do_render('footer', 2);?></div></body>
</html>
