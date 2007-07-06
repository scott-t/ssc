<?php defined('_VALID_SSC') or die('Restricted access'); global $sscConfig_webPath;?><h1>Hi</h1>
<h2>Home page</h2>
<?php if($_SERVER['SERVER_NAME'] == "scott-t"){
echo '<a href="./admin">Admin</a> ';
}?><h3>Welcome</h3>
Welcome to the current homepage of SSC - Smooth Sailing CMS.  Initially aimed for use at my local sailing club, this has evolved into something much larger and flexible.<br />Here is the current to-do list I am working on.
<h3>To-Do List</h3>
<ul> 
    <li>Change all &apos;destructive&apos; commands to hacked posts <img style="vertical-align:middle" src="<?php echo $sscConfig_webPath?>/themes/admin/done.png" alt="done" /></li> 
    <li>Convert most operations into actual funtions</li> 
    <li>Write proper JavaDoc comments for the functions</li> 
    <li>Use defined("id") or die("incorrect usage") <img  style="vertical-align:middle" src="<?php echo $sscConfig_webPath?>/themes/admin/done.png" alt="done" /></li> 
    <li>Table prefixes  <img  style="vertical-align:middle" src="<?php echo $sscConfig_webPath?>/themes/admin/done.png" alt="done" /></li> 
	<li>Complete module add code</li> 
	<li>Complete module remove code</li>
	<li>Create navigation bar rearrangement admin page <img  style="vertical-align:middle" src="<?php echo $sscConfig_webPath?>/themes/admin/done.png" alt="done" /></li>
	<li>Write a few modules...</li>
</ul>
<br />
