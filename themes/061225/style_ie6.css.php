<?php
header('Content-type: text/css');
//header('Cache-control: max-age=0, must-revalidate');
//header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 604800) . ' GMT');	//3600 * 24 * 7 = 1 week
require_once('../../conf/config.vars.php');
echo "*{background-color:transparent;}#navigation ul a:hover span{text-decoration:underline;}
* html #navigation ul a img{display:none;}
#navigation ul a:hover, #navigation ul a:active{background-color:#F9E38A}	
* html #navigation ul a{filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='".$sscConfig_themeWeb."/nav.png', sizingMethod='scale');background-color:#8AC3FA;}

	/*.panel{overflow-x:hidden;border:none;background-color:transparent;}*/
.panel *{position:relative;}

/* Admin fixes */
				.login-text{width:44%;margin-left:5px;float:left;}
				.login-form{width:44%;margin-right:5px;float:right;}
button {cursor:hand;} /* alternate cursor style for ie */";?>