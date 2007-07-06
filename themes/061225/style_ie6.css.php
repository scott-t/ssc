<?php
header('Content-type: text/css');
header('Cache-control: max-age=0, must-revalidate');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() - 604800) . ' GMT');	//3600 * 24 * 7 = 1 week
require_once('../../conf/config.vars.php');
echo "*{background-color:transparent;}#navigation a:hover span{text-decoration:underline;}
* html #main_body {height: 80%;} 
* html #navigation a img{display:none;}
#navigation a:hover, #navigation a:active{background-color:#F9E38A}	
* html #navigation a{filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='".$sscConfig_themeWeb."/nav.png', sizingMethod='scale');background-color:#8AC3FA;}
* html #navigation #logo{filter:none}
	/************* IE Mac CSS Only  works for Win too **************/
* html div#content_wrap {margin: 0 -100% 0 0;}
* html div#navigation {margin: 0;}


/* Admin fixes */
				.login-text{width:44%;margin-left:5px;float:left;}
				.login-form{width:44%;margin-right:5px;float:right;}
button {cursor:hand;} /* alternate cursor style for ie */";?>

		
