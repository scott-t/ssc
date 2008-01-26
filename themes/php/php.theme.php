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

if(false)header("Content-type:application/xhtml+xml");?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="<?php echo $lang; ?>" xml:lang="<?php echo $lang; ?>">

<head><?php echo $meta ?>
</head>
<body><div id="header"><?php echo "logo" ?></div>
<div id="bar"><?php echo "bread" ?></div>

<div id="container">
  <div id="container-help">
    
  	<div id="left"><?php echo $side[0] ?></div>
    <div id="right"><?php echo $side[1] ?></div>
    <div id="body"><?php echo $body ?></div>
    <div class="clear"></div>
  </div>
</div>
<div id="foot-bar"><?php echo $foot ?></div>
<div id="footer"><?php echo $side[3] ?></div></body>
</html>
