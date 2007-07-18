<?php 
/**
 * LBYC sailing template.  
 * @package SSC
 * @copyright Copyright (c) Scott Thomas
 * @licence GNU GPL
 */
 
/**
 * Check for legit call
 */
defined('_VALID_SSC') or die('Restricted access');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <title><?php echo $sscConfig_siteName, " | " , ucwords($_GET['cont']);?></title>
    <meta http-equiv="keywords" content="<?php echo $sscConfig_metaKeywords;?>" />
    <meta http-equiv="description" content="<?php echo $sscConfig_metaDesc;?>" />

    <style type="text/css" media="screen, projection">
/*<![CDATA[*/
@import "<?php echo $sscConfig_themeRel;?>/style.css";
<?php if($_GET['file'] == "admin") {echo '@import "'.$sscConfig_themeRel.'/style_admin.css";'; }?>
/*]]>*/
</style>
<style type="text/css" media="print">
/*<![CDATA[*/
@import "<?php echo $sscConfig_themeRel;?>/style_print.css";
/*]]>*/
</style>
<!--[if lte IE 6]>
<style type="text/css" media="screen, projection">
/*<![CDATA[*/
@import "<?php echo $sscConfig_themeRel;?>/style_ie6.css.php";
/*]]>*/
</style>
<![endif]-->
<!--[if IE 7]>
<style type="text/css" media="screen, projection">
/*<![CDATA[*/
@import "<?php echo $sscConfig_themeRel;?>/style_ie7.css";
/*]]>*/
</style>
<![endif]-->


</head>
<body> 
<div id="container"> 
    <div id="header">
        <a href="<?php echo $sscConfig_webPath; ?>">
            <img id="logo" src="<?php echo $sscConfig_themeRel;?>/logo.jpg" alt="" />
        </a>
        <img src="<?php echo $sscConfig_themeRel;?>/header.jpg" alt="" />
    </div> 
    <div id="main_body"> 
        <div id="content_wrap"> 
            <div id="content"> 
                <?php sscPlaceContent();?> 
            </div> 
        </div> 
        <div id="navigation"> 
            <?php sscPlaceNavigation(true); ?> 
        </div> 
    </div> 
    <div id="footer"> 
        <?php sscPlaceFooter();?> 
    </div> 
</div> 
</body>
</html>
